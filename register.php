<?php
/**
 * User Registration Page
 * Sans Digital Work - SDW
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/email_notifications.php';

setupSecureSession();
session_start();
generateCSRFToken();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Handle Aadhar card uploads
        $aadharFrontPath = '';
        $aadharBackPath = '';
        $uploadError = '';
        
        // Check if files were uploaded
        if (isset($_FILES['aadhar_front']) && $_FILES['aadhar_front']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            $fileType = $_FILES['aadhar_front']['type'];
            $fileSize = $_FILES['aadhar_front']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $uploadError = 'Aadhar front must be JPG, JPEG, or PNG format';
            } elseif ($fileSize > $maxSize) {
                $uploadError = 'Aadhar front file size must not exceed 5MB';
            } else {
                $uploadDir = __DIR__ . '/uploads/aadhar/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExtension = pathinfo($_FILES['aadhar_front']['name'], PATHINFO_EXTENSION);
                $aadharFrontPath = 'uploads/aadhar/front_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                
                if (!move_uploaded_file($_FILES['aadhar_front']['tmp_name'], $uploadDir . basename($aadharFrontPath))) {
                    $uploadError = 'Failed to upload Aadhar front image';
                }
            }
        }
        
        if (empty($uploadError) && isset($_FILES['aadhar_back']) && $_FILES['aadhar_back']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            $fileType = $_FILES['aadhar_back']['type'];
            $fileSize = $_FILES['aadhar_back']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $uploadError = 'Aadhar back must be JPG, JPEG, or PNG format';
            } elseif ($fileSize > $maxSize) {
                $uploadError = 'Aadhar back file size must not exceed 5MB';
            } else {
                $uploadDir = __DIR__ . '/uploads/aadhar/';
                
                $fileExtension = pathinfo($_FILES['aadhar_back']['name'], PATHINFO_EXTENSION);
                $aadharBackPath = 'uploads/aadhar/back_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                
                if (!move_uploaded_file($_FILES['aadhar_back']['tmp_name'], $uploadDir . basename($aadharBackPath))) {
                    $uploadError = 'Failed to upload Aadhar back image';
                }
            }
        }

        if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
            $error = 'All required fields are required';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (!empty($uploadError)) {
            $error = $uploadError;
        } else {
            $result = registerUser($name, $email, $phone, $password, $address, $aadharFrontPath, $aadharBackPath);
            if ($result['success']) {
                // Send welcome email
                sendWelcomeEmail($result['user_id']);
                
                // Redirect to login page with success message
                session_start();
                $_SESSION['registration_success'] = 'Registration successful! Please login to continue.';
                header('Location: login.php');
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
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background 0.3s ease, color 0.3s ease;
        }
        .register-container {
            width: 100%;
            max-width: 450px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: background 0.3s ease, color 0.3s ease;
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
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            border: none;
            background: transparent;
            font-size: 1.2rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle">🌙</button>
    <div class="register-container">
        <div class="text-center mb-4">
            <h1><?php echo SITE_NAME; ?></h1>
            <p class="text-muted">Your trusted digital service partner</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo sanitizeOutput($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo sanitizeOutput($success); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h3 class="text-center mb-4">Create Your Account</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo isset($_POST['name']) ? sanitizeOutput($_POST['name']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? sanitizeOutput($_POST['email']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required 
                               pattern="[0-9]{10}" placeholder="10-digit mobile number"
                               value="<?php echo isset($_POST['phone']) ? sanitizeOutput($_POST['phone']) : ''; ?>">
                        <small class="text-muted">Enter 10-digit mobile number</small>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required
                                  placeholder="Enter your full address"><?php echo isset($_POST['address']) ? sanitizeOutput($_POST['address']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Aadhar Card Upload</label>
                        <div class="mb-2">
                            <label for="aadhar_front" class="form-label">Aadhar Front Side</label>
                            <input type="file" class="form-control" id="aadhar_front" name="aadhar_front" 
                                   accept="image/jpeg,image/jpg,image/png" required>
                            <small class="text-muted">Upload front side of Aadhar card (JPG, PNG - Max 5MB)</small>
                        </div>
                        <div>
                            <label for="aadhar_back" class="form-label">Aadhar Back Side</label>
                            <input type="file" class="form-control" id="aadhar_back" name="aadhar_back" 
                                   accept="image/jpeg,image/jpg,image/png" required>
                            <small class="text-muted">Upload back side of Aadhar card (JPG, PNG - Max 5MB)</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('themeToggle');
        const htmlTag = document.documentElement;

        toggleBtn.addEventListener('click', () => {
            if (htmlTag.getAttribute('data-bs-theme') === 'light') {
                htmlTag.setAttribute('data-bs-theme', 'dark');
                toggleBtn.textContent = '☀️';
            } else {
                htmlTag.setAttribute('data-bs-theme', 'light');
                toggleBtn.textContent = '🌙';
            }
        });
    </script>
</body>
</html>