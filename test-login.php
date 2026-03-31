<?php
/**
 * Test Admin Login Page (Debug Version)
 * Sans Digital Work - SDW
 * Use this to test if login works
 */

// Show errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();
generateCSRFToken();

// TEMPORARILY DISABLE REDIRECT FOR TESTING
// Comment out these lines if you want normal behavior
/*
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit();
}
*/

$error = '';
$debug_info = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_info['POST'] = $_POST;
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $debug_info['username'] = $username;
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            // Check database connection
            try {
                $db = Database::getInstance()->getConnection();
                $debug_info['db_status'] = 'Connected';
                
                // Check if user exists
                $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if (!$admin) {
                    $error = 'User not found in database!';
                    $debug_info['query_result'] = 'No user found';
                } else {
                    $debug_info['user_found'] = true;
                    $debug_info['password_hash'] = substr($admin['password_hash'], 0, 20) . '...';
                    $debug_info['is_active'] = $admin['is_active'];
                    
                    // Try login
                    $result = adminLogin($username, $password);
                    $debug_info['login_result'] = $result;
                    
                    if ($result['success']) {
                        // Regenerate session ID for extra security
                        session_regenerate_id(true);
                        header('Location: index.php');
                        exit();
                    } else {
                        $error = $result['message'];
                    }
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
                $debug_info['error'] = $e->getMessage();
            }
        }
    }
}

// Check session status
$debug_info['session_status'] = session_status();
$debug_info['session_data'] = $_SESSION;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login TEST - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 500px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            background: #fff;
        }
        .debug-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="login-card p-4">
        <div class="text-center mb-4">
            <h2>🧪 Admin Login TEST</h2>
            <p class="text-muted">Debug Version - With Error Details</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" 
                       autocomplete="username" required
                       value="superadmin">
                <small class="text-muted">Try: superadmin</small>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       autocomplete="current-password" required
                       value="admin123">
                <small class="text-muted">Try: admin123</small>
            </div>

            <button type="submit" class="btn btn-primary w-100">Test Login</button>
        </form>
        
        <hr class="my-4">
        
        <div class="alert alert-info">
            <strong>📋 Debug Info:</strong>
            <div class="debug-box">
                <pre><?php print_r($debug_info); ?></pre>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="login.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Normal Login
            </a>
            <a href="index.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-speedometer2"></i> Go to Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
