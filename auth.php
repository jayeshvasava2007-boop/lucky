<?php
/**
 * Authentication Functions
 * Sans Digital Work - SDW
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * Register new user
 */
function registerUser($name, $email, $phone, $password, $address = '', $aadharFrontPath = '', $aadharBackPath = '') {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Validate phone number format
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            return ['success' => false, 'message' => 'Invalid phone number'];
        }
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Check if phone number already registered
        $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Phone number already registered'];
        }
        
        // Hash password
        $passwordHash = hashPassword($password);
        
        // Insert new user with address and Aadhar details
        $stmt = $db->prepare("INSERT INTO users (name, email, phone, address, aadhar_front, aadhar_back, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $address, $aadharFrontPath, $aadharBackPath, $passwordHash]);
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $db->lastInsertId()
        ];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Check login attempts for brute force protection
 */
function checkLoginAttempts($email) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    
    if ($_SESSION['login_attempts'] >= 5) {
        return false;
    }
    
    return true;
}

/**
 * Login user - Supports both email and phone number
 */
function loginUser($identifier, $password) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Check for too many login attempts
        if (!checkLoginAttempts($identifier)) {
            return ['success' => false, 'message' => 'Too many attempts. Try later.'];
        }
        
        // Determine if identifier is email or phone
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $isPhone = preg_match('/^[0-9]{10}$/', $identifier);
        
        if ($isPhone) {
            // Login by phone number
            $stmt = $db->prepare("SELECT id, name, email, phone, password_hash FROM users WHERE phone = ?");
            $stmt->execute([$identifier]);
        } else {
            // Login by email
            $stmt = $db->prepare("SELECT id, name, email, phone, password_hash FROM users WHERE email = ?");
            $stmt->execute([$identifier]);
        }
        
        $user = $stmt->fetch();
        
        if (!$user) {
            $_SESSION['login_attempts']++;
            return ['success' => false, 'message' => 'Invalid email/phone or password'];
        }
        
        // Verify password
        if (!verifyPassword($password, $user['password_hash'])) {
            $_SESSION['login_attempts']++;
            return ['success' => false, 'message' => 'Invalid email/phone or password'];
        }
        
        // Reset login attempts on successful login
        $_SESSION['login_attempts'] = 0;
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log user activity
        logUserActivity($db, $user['id'], 'User Login');
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed. Please try again.'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = [];
    session_destroy();
    session_start();
    session_regenerate_id(true);
    generateCSRFToken();
}

/**
 * Admin login - Username only
 */
function adminLogin($username, $password) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Get admin by username
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Verify password
        if (!verifyPassword($password, $admin['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Update last login
        $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
        
        // Set session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        
        // Log activity
        logAdminActivity($db, $admin['id'], 'Admin Login', null, 'Successful login');
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'admin' => $admin
        ];
        
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed. Please try again.'];
    }
}

/**
 * Admin logout
 */
function adminLogout() {
    $adminId = getCurrentAdminId();
    if ($adminId) {
        $db = Database::getInstance()->getConnection();
        logAdminActivity($db, $adminId, 'Admin Logout', null, 'Logged out');
    }
    
    session_unset();
    session_destroy();
    session_start();
    generateCSRFToken();
}

/**
 * Notify admins of customer login (for real-time updates)
 */
function notifyCustomerLogin($userId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get user details
        $stmt = $db->prepare("SELECT name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if(!$user) return;
        
        // Create notification
        $stmt = $db->prepare("
            INSERT INTO notifications 
            (type, title, message, user_id, is_read, created_at)
            VALUES 
            ('customer_login', 'Customer Login', 
             CONCAT(?, ' just logged in'), ?, 0, NOW())
        ");
        $stmt->execute([$user['name'], $userId]);
        
        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Log activity
        logUserActivity($db, $userId, 'Customer Login', "User logged in from {$_SERVER['REMOTE_ADDR']}");
        
    } catch(Exception $e) {
        error_log("Login notification error: " . $e->getMessage());
    }
}

/**
 * Notify admins of new customer registration
 */
function notifyNewRegistration($userId, $userName, $userEmail, $userPhone) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Create notification
        $stmt = $db->prepare("
            INSERT INTO notifications 
            (type, title, message, user_id, metadata, is_read, created_at)
            VALUES 
            ('new_customer', 'New Customer Registered', 
             CONCAT(?, ' has just registered'), ?, ?, 0, NOW())
        ");
        
        $metadata = json_encode([
            'email' => $userEmail,
            'phone' => $userPhone
        ]);
        
        $stmt->execute([$userName, $userId, $metadata]);
        
        // Log activity
        logUserActivity($db, $userId, 'Customer Registration', "New customer registered");
        
    } catch(Exception $e) {
        error_log("Registration notification error: " . $e->getMessage());
    }
}

/**
 * Check if user has paid registration fee
 */
function hasPaidRegistrationFee($userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return false;
    }
    
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("
            SELECT payment_status 
            FROM registration_fees 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $payment = $stmt->fetch();
        
        return $payment && $payment['payment_status'] == 'success';
        
    } catch (Exception $e) {
        // If table doesn't exist yet, assume fee is paid (for backward compatibility)
        error_log("Check registration fee error: " . $e->getMessage());
        return true;
    }
}

/**
 * Require registration fee payment
 * Redirects to payment page if fee not paid
 */
function requireRegistrationFee() {
    if (!isLoggedIn()) {
        return; // Let requireLogin() handle this
    }
    
    if (!hasPaidRegistrationFee()) {
        header('Location: pay-registration-fee.php');
        exit();
    }
}

?>
