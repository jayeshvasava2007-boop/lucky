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

?>
