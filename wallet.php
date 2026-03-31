<?php
/**
 * Wallet Management System
 * SDW SaaS - Balance, Transactions, Commission
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get or create wallet for user
 */
function getOrCreateWallet($userType, $userId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Check if wallet exists
        $stmt = $db->prepare("SELECT id FROM wallets WHERE user_type = ? AND user_id = ?");
        $stmt->execute([$userType, $userId]);
        $wallet = $stmt->fetch();
        
        if ($wallet) {
            return $wallet['id'];
        }
        
        // Create new wallet
        $stmt = $db->prepare("INSERT INTO wallets (user_type, user_id) VALUES (?, ?)");
        $stmt->execute([$userType, $userId]);
        
        return $db->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Get/Create wallet error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get wallet balance
 */
function getWalletBalance($walletId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("SELECT balance FROM wallets WHERE id = ?");
        $stmt->execute([$walletId]);
        $wallet = $stmt->fetch();
        
        return $wallet ? $wallet['balance'] : 0.00;
        
    } catch (Exception $e) {
        error_log("Get balance error: " . $e->getMessage());
        return 0.00;
    }
}

/**
 * Recharge wallet
 */
function rechargeWallet($walletId, $amount, $paymentId = null, $description = 'Wallet recharge') {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Get current balance
        $currentBalance = getWalletBalance($walletId);
        $newBalance = $currentBalance + $amount;
        
        // Update wallet
        $stmt = $db->prepare("
            UPDATE wallets 
            SET balance = ?, total_recharged = total_recharged + ?, last_transaction_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newBalance, $amount, $walletId]);
        
        // Record transaction
        $stmt = $db->prepare("
            INSERT INTO transactions 
            (wallet_id, transaction_type, amount, balance_after, description, reference_type, payment_id)
            VALUES (?, 'credit', ?, ?, ?, 'recharge', ?)
        ");
        $stmt->execute([$walletId, $amount, $newBalance, $description, $paymentId]);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Wallet recharged successfully',
            'new_balance' => $newBalance
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Recharge wallet error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to recharge wallet'
        ];
    }
}

/**
 * Deduct from wallet (for service booking)
 */
function deductFromWallet($walletId, $amount, $serviceRequestId = null, $description = 'Service booking') {
    $db = Database::getInstance()->getConnection();
    
    try {
        $currentBalance = getWalletBalance($walletId);
        
        if ($currentBalance < $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient balance',
                'current_balance' => $currentBalance
            ];
        }
        
        $db->beginTransaction();
        
        $newBalance = $currentBalance - $amount;
        
        // Update wallet
        $stmt = $db->prepare("
            UPDATE wallets 
            SET balance = ?, total_spent = total_spent + ?, last_transaction_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newBalance, $amount, $walletId]);
        
        // Record transaction
        $stmt = $db->prepare("
            INSERT INTO transactions 
            (wallet_id, transaction_type, amount, balance_after, description, reference_type, reference_id)
            VALUES (?, 'debit', ?, ?, ?, 'service_booking', ?)
        ");
        $stmt->execute([$walletId, $amount, $newBalance, $description, $serviceRequestId]);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Amount deducted successfully',
            'new_balance' => $newBalance
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Deduct from wallet error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to deduct from wallet'
        ];
    }
}

/**
 * Add commission to admin wallet
 */
function addCommission($serviceRequestId, $adminId, $commissionAmount, $commissionRate, $serviceFees) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Get or create wallet for admin
        $walletId = getOrCreateWallet('sub_admin', $adminId);
        
        // Calculate platform fees
        $platformFees = $serviceFees - $commissionAmount;
        
        // Add commission to wallet
        $result = rechargeWallet($walletId, $commissionAmount, null, "Commission for service request #{$serviceRequestId}");
        
        if (!$result['success']) {
            throw new Exception("Failed to add commission");
        }
        
        // Record commission
        $stmt = $db->prepare("
            INSERT INTO commissions 
            (service_request_id, admin_id, commission_amount, commission_rate, service_fees, platform_fees, status, credited_at)
            VALUES (?, ?, ?, ?, ?, ?, 'credited', NOW())
        ");
        $stmt->execute([$serviceRequestId, $adminId, $commissionAmount, $commissionRate, $serviceFees, $platformFees]);
        
        // Update service request with commission info
        $stmt = $db->prepare("
            UPDATE service_requests 
            SET commission_deducted = ?, processed_by_sub_admin = ?
            WHERE id = ?
        ");
        $stmt->execute([$commissionAmount, $adminId, $serviceRequestId]);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Commission credited successfully',
            'commission_amount' => $commissionAmount
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Add commission error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to add commission'
        ];
    }
}

/**
 * Get transaction history
 */
function getTransactionHistory($walletId, $limit = 50) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM transactions 
            WHERE wallet_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$walletId, $limit]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Get transaction history error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total commission earned by admin
 */
function getTotalCommissionEarned($adminId, $status = 'credited') {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("SELECT SUM(commission_amount) as total FROM commissions WHERE admin_id = ? AND status = ?");
        $stmt->execute([$adminId, $status]);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0.00;
        
    } catch (Exception $e) {
        error_log("Get total commission error: " . $e->getMessage());
        return 0.00;
    }
}

?>
