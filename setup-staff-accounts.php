<?php
/**
 * Staff Accounts Setup Wizard
 * Sans Digital Work - SDW
 * Create 3 default staff accounts
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();

$db = Database::getInstance()->getConnection();
$success = '';
$error = '';
$accountsCreated = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Staff Account 1
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO admins (username, password_hash, full_name, role, is_active)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE username = username
        ");
        
        // Account 1: Super Admin
        $stmt->execute(['superadmin', $passwordHash, 'Rajesh Kumar (Super Admin)', 'super_admin', 1]);
        $accountsCreated[] = ['username' => 'superadmin', 'role' => 'Super Admin'];
        
        // Account 2: Admin
        $stmt->execute(['admin1', $passwordHash, 'Priya Sharma (Admin)', 'admin', 1]);
        $accountsCreated[] = ['username' => 'admin1', 'role' => 'Admin'];
        
        // Account 3: Operator
        $stmt->execute(['operator1', $passwordHash, 'Amit Patel (Operator)', 'operator', 1]);
        $accountsCreated[] = ['username' => 'operator1', 'role' => 'Operator'];
        
        $success = 'All 3 staff accounts created successfully!';
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
            $error = 'Some accounts already exist. You can manage them from the Staff Management page.';
        } else {
            $error = 'Error creating accounts: ' . $e->getMessage();
        }
    }
}

// Get existing accounts
try {
    $existingAccounts = $db->query("SELECT id, username, full_name, role, is_active FROM admins ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
    $existingAccounts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Accounts Setup - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-container {
            max-width: 800px;
            width: 100%;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .account-card {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
        }
        .badge-role {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .btn-create {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
        .credentials-box {
            background: #fff3cd;
            border: 2px dashed #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="setup-container p-3">
        <div class="card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-people-fill text-primary" style="font-size: 4rem;"></i>
                    <h2 class="mt-3 fw-bold">Staff Accounts Setup</h2>
                    <p class="text-muted">Create 3 default admin/staff accounts</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo sanitizeOutput($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo sanitizeOutput($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (count($existingAccounts) > 0): ?>
                    <h5 class="mb-3"><i class="bi bi-info-circle"></i> Existing Accounts:</h5>
                    <?php foreach ($existingAccounts as $acc): ?>
                        <div class="account-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo sanitizeOutput($acc['full_name']); ?></strong><br>
                                    <small class="text-muted">@<?php echo sanitizeOutput($acc['username']); ?></small>
                                </div>
                                <div>
                                    <?php
                                    $badgeClass = match($acc['role']) {
                                        'super_admin' => 'bg-danger',
                                        'admin' => 'bg-primary',
                                        'operator' => 'bg-info text-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge-role badge <?php echo $badgeClass; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $acc['role'])); ?>
                                    </span>
                                    <?php if ($acc['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center mt-4">
                        <a href="staff-management.php" class="btn btn-primary">
                            <i class="bi bi-gear"></i> Manage Staff Accounts
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> This will create 3 staff accounts with the following credentials:
                    </div>

                    <div class="credentials-box">
                        <h5 class="fw-bold"><i class="bi bi-key"></i> DEFAULT LOGIN CREDENTIALS</h5>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <strong>Account 1 (Super Admin):</strong><br>
                                Username: <code>superadmin</code><br>
                                Password: <code>admin123</code>
                            </div>
                            <div class="col-md-4">
                                <strong>Account 2 (Admin):</strong><br>
                                Username: <code>admin1</code><br>
                                Password: <code>admin123</code>
                            </div>
                            <div class="col-md-4">
                                <strong>Account 3 (Operator):</strong><br>
                                Username: <code>operator1</code><br>
                                Password: <code>operator123</code>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-danger">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <strong>IMPORTANT:</strong> Change these passwords after first login!
                            </small>
                        </div>
                    </div>

                    <form method="POST" class="mt-4">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-create btn-lg text-white">
                                <i class="bi bi-plus-circle"></i> Create 3 Staff Accounts
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                <?php endif; ?>

                <hr class="my-4">

                <div class="text-center text-muted small">
                    <p class="mb-0">
                        <i class="bi bi-shield-check"></i> All passwords are securely hashed using PHP's password_hash()
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
