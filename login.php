<?php
/**
 * User Login Page
 * Sans Digital Work - SDW
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

setupSecureSession();
session_start();
generateCSRFToken();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Determine login identifier (email or phone)
        $loginIdentifier = !empty($phone) ? $phone : $email;
        
        if (empty($loginIdentifier) || empty($password)) {
            $error = 'Email/Phone and password are required';
        } else {
            $result = loginUser($loginIdentifier, $password);
            
            if ($result['success']) {
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #007bff, #6610f2);
            transition: background 0.3s ease;
        }
        [data-bs-theme="dark"] body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
        }
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            background-color: #fff;
            transition: background 0.3s ease, color 0.3s ease;
        }
        [data-bs-theme="dark"] .card {
            background-color: #2d3748;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }
        .card-body {
            padding: 2rem;
        }
        h1 {
            font-weight: 700;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        [data-bs-theme="dark"] h1 {
            color: #e2e8f0;
            text-shadow: none;
        }
        [data-bs-theme="dark"] .card-body h3 {
            color: #e2e8f0;
        }
        .form-label {
            font-weight: 600;
        }
        .btn-primary {
            background: linear-gradient(90deg, #007bff, #6610f2);
            border: none;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .btn-primary:hover {
            transform: scale(1.02);
        }
        .alert {
            border-radius: 8px;
        }
        .register-link a {
            text-decoration: none;
            font-weight: 600;
            color: #007bff;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            font-size: 1.5rem;
            cursor: pointer;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        [data-bs-theme="dark"] .theme-toggle {
            background: rgba(0, 0, 0, 0.3);
        }
        .text-muted {
            color: #e0e0e0 !important;
        }
        [data-bs-theme="dark"] .text-muted {
            color: #a0aec0 !important;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">🌙</button>
    
    <div class="login-container">
        <div class="text-center mb-4">
            <h1><?php echo SITE_NAME; ?></h1>
            <p class="text-muted">Your trusted digital service partner</p>
        </div>
        
        <?php 
        // Show registration success message if exists
        if (isset($_SESSION['registration_success'])): 
        ?>
            <div class="alert alert-success">
                <?php echo sanitizeOutput($_SESSION['registration_success']); ?>
                <?php unset($_SESSION['registration_success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo sanitizeOutput($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body p-4">
                <h3 class="text-center mb-4" style="color: #333;">Login to Your Account</h3>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-muted">(or use phone below)</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               autocomplete="username"
                               value="<?php echo isset($_POST['email']) ? sanitizeOutput($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number <span class="text-muted">(Alternative)</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               placeholder="e.g., 9876543210"
                               pattern="[0-9]{10}" 
                               title="Please enter 10 digit mobile number"
                               value="<?php echo isset($_POST['phone']) ? sanitizeOutput($_POST['phone']) : ''; ?>">
                        <small class="text-muted">Enter your registered mobile number (10 digits)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-3 register-link">
            <p style="color: #e0e0e0;">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggleBtn = document.getElementById('themeToggle');
        const htmlTag = document.documentElement;
        
        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        htmlTag.setAttribute('data-bs-theme', currentTheme);
        toggleBtn.textContent = currentTheme === 'dark' ? '☀️' : '🌙';

        toggleBtn.addEventListener('click', () => {
            const newTheme = htmlTag.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
            htmlTag.setAttribute('data-bs-theme', newTheme);
            toggleBtn.textContent = newTheme === 'dark' ? '☀️' : '🌙';
            localStorage.setItem('theme', newTheme);
        });
    </script>
</body>
</html>
