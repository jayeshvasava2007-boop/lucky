<?php
/**
 * Security Functions
 * Sans Digital Work - SDW
 * Critical for protecting customer data and building trust
 */

/**
 * Encrypt sensitive data (like Aadhar numbers)
 */
function encryptData($data, $key = ENCRYPTION_KEY) {
    $encryptionKey = hash('sha256', $key, true);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $encryptionKey, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Decrypt sensitive data
 */
function decryptData($encryptedData, $key = ENCRYPTION_KEY) {
    $encryptionKey = hash('sha256', $key, true);
    list($encrypted_data, $iv) = explode('::', base64_decode($encryptedData));
    
    // Ensure IV is exactly 16 bytes
    $iv = str_pad($iv, 16, "\0");
    
    return openssl_decrypt($encrypted_data, 'AES-256-CBC', $encryptionKey, OPENSSL_RAW_DATA, $iv);
}

/**
 * Hash passwords using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Prevent XSS attacks
 */
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Prevent SQL injection - use prepared statements instead
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

/**
 * Require admin login
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit();
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current admin ID
 */
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Force HTTPS redirect (for production)
 */
function forceHTTPS() {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

/**
 * Log admin activity
 */
function logAdminActivity($db, $adminId, $action, $requestId = null, $details = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $db->prepare("INSERT INTO admin_activity_log (admin_id, action, request_id, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $requestId, $details, $ip]);
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
    }
}

/**
 * Log user activity
 */
function logUserActivity($db, $userId, $action) {
    try {
        $stmt = $db->prepare("INSERT INTO user_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$userId, $action]);
    } catch (Exception $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

/**
 * Validate Aadhar number format (basic validation)
 */
function validateAadharFormat($aadharNumber) {
    // Remove spaces and dashes
    $aadharNumber = preg_replace('/[\s\-]/', '', $aadharNumber);
    
    // Check if it's 12 digits
    if (!preg_match('/^\d{12}$/', $aadharNumber)) {
        return false;
    }
    
    // Basic checksum validation (Verhoeff algorithm can be added for stricter validation)
    return true;
}

/**
 * Set secure session cookies
 * MUST be called BEFORE session_start()
 */
function setupSecureSession() {
    // Only set these if session hasn't started yet
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
    }
}

?>
