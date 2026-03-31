<?php
/**
 * User Profile Settings Page
 * Sans Digital Work - SDW
 * Users can update their profile information and change password
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();
$error = '';
$success = '';

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token';
        } else {
            $name = sanitizeInput($_POST['name'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $address = sanitizeInput($_POST['address'] ?? '');
            $city = sanitizeInput($_POST['city'] ?? '');
            $state = sanitizeInput($_POST['state'] ?? '');
            $pincode = sanitizeInput($_POST['pincode'] ?? '');

            if (empty($name) || empty($phone)) {
                $error = 'Name and phone are required';
            } elseif (!validatePhoneFormat($phone)) {
                $error = 'Invalid phone number format';
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET name = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $phone, $address, $city, $state, $pincode, $userId]);
                    
                    $_SESSION['user_name'] = $name;
                    $success = 'Profile updated successfully!';
                    
                    // Refresh user data
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                } catch (Exception $e) {
                    error_log("Profile update error: " . $e->getMessage());
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token';
        } else {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = 'All password fields are required';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Password must be at least 6 characters long';
            } else {
                // Verify current password
                if (password_verify($currentPassword, $user['password'])) {
                    try {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $userId]);
                        
                        $success = 'Password changed successfully!';
                    } catch (Exception $e) {
                        error_log("Password change error: " . $e->getMessage());
                        $error = 'Failed to change password. Please try again.';
                    }
                } else {
                    $error = 'Current password is incorrect';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .profile-avatar i {
            font-size: 4rem;
            color: #667eea;
        }
        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .nav-pills .nav-link {
            color: #667eea;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 10px;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tab-content {
            padding: 25px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <img src="log.png" style="height:40px; margin-right:10px;">
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-applications.php"><i class="bi bi-folder-check"></i> My Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php"><i class="bi bi-person-circle"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary px-4" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="text-center">
                <div class="profile-avatar mb-3">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h1 class="fw-bold mb-2"><?php echo sanitizeOutput($user['name']); ?></h1>
                <p class="mb-0 opacity-75"><?php echo sanitizeOutput($user['email']); ?></p>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo sanitizeOutput($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo sanitizeOutput($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                            <a class="nav-link active" id="v-pills-profile-tab" data-bs-toggle="pill" href="#v-pills-profile" role="tab">
                                <i class="bi bi-person-gear me-2"></i> Profile Information
                            </a>
                            <a class="nav-link" id="v-pills-password-tab" data-bs-toggle="pill" href="#v-pills-password" role="tab">
                                <i class="bi bi-key me-2"></i> Change Password
                            </a>
                            <a class="nav-link" id="v-pills-security-tab" data-bs-toggle="pill" href="#v-pills-security" role="tab">
                                <i class="bi bi-shield-lock me-2"></i> Security Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content" id="v-pills-tabContent">
                    <!-- Profile Information Tab -->
                    <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                        <div class="settings-card">
                            <h4 class="fw-bold mb-4"><i class="bi bi-person-gear"></i> Profile Information</h4>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo sanitizeOutput($user['name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="<?php echo sanitizeOutput($user['email']); ?>" disabled>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo sanitizeOutput($user['phone']); ?>" 
                                               pattern="[0-9]{10}" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="dob" 
                                               value="<?php echo sanitizeOutput($user['dob'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo sanitizeOutput($user['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" 
                                               value="<?php echo sanitizeOutput($user['city'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="state" 
                                               value="<?php echo sanitizeOutput($user['state'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pincode</label>
                                        <input type="text" class="form-control" name="pincode" 
                                               value="<?php echo sanitizeOutput($user['pincode'] ?? ''); ?>" 
                                               pattern="[0-9]{6}">
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password Tab -->
                    <div class="tab-pane fade" id="v-pills-password" role="tabpanel">
                        <div class="settings-card">
                            <h4 class="fw-bold mb-4"><i class="bi bi-key"></i> Change Password</h4>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="new_password" required>
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security Settings Tab -->
                    <div class="tab-pane fade" id="v-pills-security" role="tabpanel">
                        <div class="settings-card">
                            <h4 class="fw-bold mb-4"><i class="bi bi-shield-lock"></i> Security Settings</h4>
                            
                            <div class="mb-4">
                                <h6 class="fw-bold">Account Security</h6>
                                <p class="text-muted">Keep your account secure by following these tips:</p>
                                <ul class="text-muted">
                                    <li>Use a strong password (minimum 6 characters)</li>
                                    <li>Don't share your password with anyone</li>
                                    <li>Change your password regularly</li>
                                    <li>Enable two-factor authentication when available</li>
                                    <li>Logout from shared computers</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold">Session Information</h6>
                                <p class="text-muted">
                                    <strong>Last Login:</strong> <?php echo date('d M Y, h:i A', strtotime($user['last_login'] ?? 'now')); ?><br>
                                    <strong>Account Created:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                </p>
                            </div>

                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <strong>Important:</strong> If you suspect any unauthorized access to your account, 
                                change your password immediately and contact support.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
