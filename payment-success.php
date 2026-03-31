<?php
/**
 * Payment Success Handler
 * Sans Digital Work - SDW
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/email_notifications.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// Get payment details from Razorpay
$razorpayPaymentId = $_GET['razorpay_payment_id'] ?? '';
$razorpayOrderId = $_GET['razorpay_order_id'] ?? '';
$razorpaySignature = $_GET['razorpay_signature'] ?? '';
$requestId = (int)($_GET['request_id'] ?? 0);

if (empty($razorpayPaymentId) || empty($razorpayOrderId) || empty($razorpaySignature) || $requestId === 0) {
    die('Invalid payment details');
}

try {
    // Verify payment signature (IMPORTANT for security)
    $generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, RAZORPAY_KEY_SECRET);
    
    if ($generatedSignature !== $razorpaySignature) {
        // Payment verification failed - possible fraud
        header('Location: payment-failed.php?error=signature_mismatch');
        exit();
    }
    
    // Update payment status in database
    $stmt = $db->prepare("
        UPDATE service_requests 
        SET payment_status = 'success', 
            payment_id = ?,
            application_status = 'Pending'
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$razorpayPaymentId, $requestId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        // Get application details for email
        $stmt = $db->prepare("SELECT fees + registration_fees as total_amount FROM service_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $application = $stmt->fetch();
        
        // Send payment confirmation email
        sendPaymentConfirmation($userId, $requestId, $application['total_amount'], $razorpayPaymentId);
        
        // Payment successful
        header('Location: payment-success-confirmed.php?request_id=' . $requestId);
        exit();
    } else {
        // Request not found or already processed
        header('Location: my-applications.php');
        exit();
    }
    
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage());
    header('Location: payment-failed.php?error=processing_error');
    exit();
}
?>
