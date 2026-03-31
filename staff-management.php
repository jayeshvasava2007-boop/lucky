<?php
/**
 * Staff Management Page
 * Sans Digital Work - SDW
 * Manage admin/staff members (3 members)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();
requireAdmin(); // Only admins can manage staff

$db = Database::getInstance()->getConnection();
$adminId = getCurrentAdminId();
$error = '';
$success = '';

// Handle add/update/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // Add new staff member
                    $username = sanitizeInput($_POST['username'] ?? '');
                    $fullName = sanitizeInput($_POST['full_name'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $role = $_POST['role'] ?? 'admin';
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    
                    if (empty($username) || empty($fullName) || empty($password)) {
                        $error = 'All fields are required';
                    } elseif (strlen($password) < 6) {
                        $error = 'Password must be at least 6 characters';
                    } else {
                        try {
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("
                                INSERT INTO admins (username, password_hash, full_name, role, is_active)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$username, $passwordHash, $fullName, $role, $isActive]);
                            $success = 'Staff member added successfully!';
                            
                            // Log activity
                            logAdminActivity($db, $adminId, 'add_staff', null, "Added staff: $username");
                        } catch (Exception $e) {
                            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), '1062') !== false) {
                                $error = 'Username already exists';
                            } else {
                                $error = 'Failed to add staff member';
                                error_log("Add staff error: " . $e->getMessage());
                            }
                        }
                    }
                    break;
                    
                case 'update':
                    // Update existing staff member
                    $staffId = (int)($_POST['staff_id'] ?? 0);
                    $fullName = sanitizeInput($_POST['full_name'] ?? '');
                    $role = $_POST['role'] ?? 'admin';
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    $newPassword = $_POST['new_password'] ?? '';
                    
                    if ($staffId === 0 || empty($fullName)) {
                        $error = 'Invalid staff ID or missing name';
                    } else {
                        try {
                            if (!empty($newPassword)) {
                                if (strlen($newPassword) < 6) {
                                    $error = 'Password must be at least 6 characters';
                                    break;
                                }
                                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                                $stmt = $db->prepare("
                                    UPDATE admins 
                                    SET full_name = ?, role = ?, is_active = ?, password_hash = ?
                                    WHERE id = ?
                                ");
                                $stmt->execute([$fullName, $role, $isActive, $passwordHash, $staffId]);
                            } else {
                                $stmt = $db->prepare("
                                    UPDATE admins 
                                    SET full_name = ?, role = ?, is_active = ?
                                    WHERE id = ?
                                ");
                                $stmt->execute([$fullName, $role, $isActive, $staffId]);
                            }
                            $success = 'Staff member updated successfully!';
                            
                            // Log activity
                            logAdminActivity($db, $adminId, 'update_staff', null, "Updated staff ID: $staffId");
                        } catch (Exception $e) {
                            $error = 'Failed to update staff member';
                            error_log("Update staff error: " . $e->getMessage());
                        }
                    }
                    break;
                    
                case 'delete':
                    // Delete staff member
                    $staffId = (int)($_POST['staff_id'] ?? 0);
                    
                    if ($staffId === 0) {
                        $error = 'Invalid staff ID';
                    } elseif ($staffId === $adminId) {
                        $error = 'Cannot delete your own account';
                    } else {
                        try {
                            $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
                            $stmt->execute([$staffId]);
                            $success = 'Staff member deleted successfully!';
                            
                            // Log activity
                            logAdminActivity($db, $adminId, 'delete_staff', null, "Deleted staff ID: $staffId");
                        } catch (Exception $e) {
                            $error = 'Failed to delete staff member';
                            error_log("Delete staff error: " . $e->getMessage());
                        }
                    }
                    break;
            }
        }
    }
}

// Get all staff members
$staffQuery = "SELECT id, username, full_name, role, is_active, last_login, created_at FROM admins ORDER BY id ASC";
$staffMembers = $db->query($staffQuery)->fetchAll();

// Count staff members
$totalStaff = count($staffMembers);
$activeStaff = count(array_filter($staffMembers, fn($s) => $s['is_active']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - <?php echo SITE_NAME; ?></title>
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
        .sidebar {
            min-height: calc(100vh - 56px);
            background: linear-gradient(180deg, #2d3436 0%, #1e272e 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar a {
            color: #b2bec3;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-left-color: #667eea;
        }
        .main-content {
            padding: 30px;
        }
        .staff-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .staff-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .staff-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-box h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-people-fill"></i> SDW Staff Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            Lucky
                            <span class="badge bg-light text-dark ms-2"><?php echo sanitizeOutput($_SESSION['admin_role']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="pt-4">
                    <a href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="view-request.php">
                        <i class="bi bi-folder-check"></i> All Requests
                    </a>
                    <a href="manage-documents.php">
                        <i class="bi bi-file-earmark-text"></i> Manage Documents
                    </a>
                    <a href="staff-management.php" class="active">
                        <i class="bi bi-people-fill"></i> Staff Management
                    </a>
                    <hr class="text-white mx-3">
                    <a href="../index.php">
                        <i class="bi bi-box-arrow-left"></i> Back to Site
                    </a>
                    <a href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo sanitizeOutput($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo sanitizeOutput($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-people-fill text-primary"></i> Staff Management</h2>
                        <p class="text-muted mb-0">Manage admin and staff members</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                        <i class="bi bi-plus-circle"></i> Add Staff Member
                    </button>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-box">
                            <h3><?php echo $totalStaff; ?></h3>
                            <p class="text-muted mb-0">Total Staff</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <h3 class="text-success"><?php echo $activeStaff; ?></h3>
                            <p class="text-muted mb-0">Active Staff</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <h3 class="text-danger"><?php echo $totalStaff - $activeStaff; ?></h3>
                            <p class="text-muted mb-0">Inactive Staff</p>
                        </div>
                    </div>
                </div>

                <!-- Staff List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Staff Members</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($staffMembers) > 0): ?>
                            <div class="row">
                                <?php foreach ($staffMembers as $staff): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="staff-card">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="staff-avatar me-3">
                                                    <?php echo strtoupper(substr($staff['full_name'], 0, 1)); ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="mb-1"><?php echo sanitizeOutput($staff['full_name']); ?></h5>
                                                    <p class="text-muted small mb-1">@<?php echo sanitizeOutput($staff['username']); ?></p>
                                                    <div class="mb-2">
                                                        <?php
                                                        $roleBadge = match($staff['role']) {
                                                            'super_admin' => 'bg-danger',
                                                            'admin' => 'bg-primary',
                                                            'operator' => 'bg-info text-dark',
                                                            default => 'bg-secondary'
                                                        };
                                                        ?>
                                                        <span class="badge <?php echo $roleBadge; ?>">
                                                            <?php echo ucwords(str_replace('_', ' ', $staff['role'])); ?>
                                                        </span>
                                                        <?php if ($staff['is_active']): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactive</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="small text-muted mb-3">
                                                <div><i class="bi bi-clock-history"></i> Last login: <?php echo $staff['last_login'] ? date('d M Y, h:i A', strtotime($staff['last_login'])) : 'Never'; ?></div>
                                                <div><i class="bi bi-calendar-check"></i> Joined: <?php echo date('d M Y', strtotime($staff['created_at'])); ?></div>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editStaff(<?php echo htmlspecialchars(json_encode($staff)); ?>)">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <?php if ($staff['id'] !== $adminId): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo sanitizeOutput($staff['full_name']); ?>')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                                <h5 class="text-muted mt-3">No staff members found</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add Staff Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" required>
                            <small class="text-muted">Unique username for login</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="operator">Operator</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" checked id="is_active_add">
                            <label class="form-check-label" for="is_active_add">
                                Active Account
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="staff_id" id="edit_staff_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Staff Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" placeholder="Leave blank to keep current">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role">
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="operator">Operator</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Active Account
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form method="POST" action="" id="deleteForm">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="staff_id" id="delete_staff_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editStaff(staff) {
            document.getElementById('edit_staff_id').value = staff.id;
            document.getElementById('edit_username').value = staff.username;
            document.getElementById('edit_full_name').value = staff.full_name;
            document.getElementById('edit_role').value = staff.role;
            document.getElementById('edit_is_active').checked = staff.is_active == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('editStaffModal'));
            modal.show();
        }
        
        function deleteStaff(id, name) {
            if (confirm(`Are you sure you want to delete staff member "${name}"? This action cannot be undone.`)) {
                document.getElementById('delete_staff_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>

