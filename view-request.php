<?php
/**
 * View Request Details
 * Sans Digital Work - SDW
 * Admin can view details and update status
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_notifications.php';
require_once __DIR__ . '/../includes/upload.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();
requireAdmin();

$db = Database::getInstance()->getConnection();
$adminId = getCurrentAdminId();
$requestId = (int)($_GET['id'] ?? 0);

if ($requestId === 0) {
    header('Location: index.php');
    exit();
}

// Get request details with fees from services table
$stmt = $db->prepare("
    SELECT 
        sr.*,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        s.service_name,
        s.description AS service_description,
        s.fees,
        s.registration_fees,
        a.full_name AS processed_by
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    JOIN servicesand s ON sr.service_id = s.id
    LEFT JOIN admins a ON sr.admin_id = a.id
    WHERE sr.id = ?
");
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    die('Request not found');
}

// Decrypt Aadhar number
$aadharNumber = decryptData($request['aadhar_number']);
$personalData = json_decode($request['personal_data'], true);

// Handle status update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("Invalid request");
    }
    
    $newStatus = $_POST['application_status'] ?? '';
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    
    if (in_array($newStatus, ['Pending', 'Processing', 'Completed', 'Rejected'])) {
        try {
            $stmt = $db->prepare("
                UPDATE service_requests 
                SET application_status = ?, remarks = ?, admin_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $remarks, $adminId]);
            
            // Log activity
            logAdminActivity($db, $adminId, 'Update Status', $requestId, "Changed status to {$newStatus}");
            
            // Send email notification
            sendStatusUpdateEmail($request['user_id'], $requestId, $newStatus, $remarks);
            
            $message = 'Status updated successfully and email sent';
            
            // Refresh data
            $stmt = $db->prepare("SELECT application_status, remarks FROM service_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = array_merge($request, $stmt->fetch());
            
        } catch (Exception $e) {
            $message = 'Failed to update status';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request #<?php echo $request['id']; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .card-header {
            border-bottom: none;
            font-weight: 600;
        }

        .detail-card {
            margin-bottom: 20px;
        }

        .label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 20px;
        }

        .navbar {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section-title {
            font-weight: 600;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand fw-bold" href="index.php">
            <img src="../log.png" style="height:35px; margin-right:10px;">
            <?php echo SITE_NAME; ?> Admin
        </a>

        <div class="ms-auto d-flex align-items-center">
            <span class="text-white me-3">
                👨‍💼 Lucky
            </span>
            <a class="btn btn-outline-light btn-sm" href="logout.php">
                Logout
            </a>
        </div>
    </nav>
    
    <div class="container mt-4">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card p-3 text-center">
                    <small>Total Amount</small>
                    <h5 class="text-success">₹<?php echo number_format($request['fees'] + $request['registration_fees'], 2); ?></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center">
                    <small>Status</small>
                    <?php
                    $statusClass = match($request['application_status']) {
                        'Pending' => 'bg-warning text-dark',
                        'Processing' => 'bg-info',
                        'Completed' => 'bg-success',
                        'Rejected' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                    ?>
                    <h5><span class="badge <?php echo $statusClass; ?> status-badge"><?php echo $request['application_status']; ?></span></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center">
                    <small>Payment</small>
                    <h5><?php echo $request['payment_status'] === 'success' ? '<span class="badge bg-success">Paid ✓</span>' : '<span class="badge bg-danger">Unpaid</span>'; ?></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center">
                    <small>Request ID</small>
                    <h5>#<?php echo $request['id']; ?></h5>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Service Request #<?php echo $request['id']; ?></h2>
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo sanitizeOutput($message); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Left Column - Application Details -->
            <div class="col-md-8">
                <!-- Service Information -->
                <div class="card detail-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-briefcase"></i> Service Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="label">Service Name:</p>
                                <p><?php echo sanitizeOutput($request['service_name']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="label">Applied On:</p>
                                <p><?php echo date('d M Y, h:i A', strtotime($request['created_at'])); ?></p>
                            </div>
                            <div class="col-md-12 mb-3">
                                <p class="label">Description:</p>
                                <p><?php echo sanitizeOutput($request['service_description']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div class="card detail-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="label">Full Name:</p>
                                <p><?php echo sanitizeOutput($request['user_name']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="label">Email Address:</p>
                                <p><a href="mailto:<?php echo sanitizeOutput($request['user_email']); ?>">
                                    <?php echo sanitizeOutput($request['user_email']); ?>
                                </a></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="label">Phone Number:</p>
                                <p><a href="tel:<?php echo sanitizeOutput($request['user_phone']); ?>">
                                    <?php echo sanitizeOutput($request['user_phone']); ?>
                                </a></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="label">Date of Birth:</p>
                                <p><?php echo isset($personalData['dob']) ? date('d M Y', strtotime($personalData['dob'])) : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="label">Gender:</p>
                                <p><?php echo sanitizeOutput($personalData['gender'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-12 mb-3">
                                <p class="label">Address:</p>
                                <p><?php echo sanitizeOutput($personalData['address'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aadhar Information -->
                <div class="card detail-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-card-heading"></i> Aadhar Card Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="label">Aadhar Number:</p>
                                <p class="fs-5"><?php echo substr($aadharNumber, 0, 4) . ' XXXX XXXX'; ?></p>
                                <small class="text-muted">Full number is encrypted in database</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="label">Uploaded Document:</p>
                                <?php
                                $filePath = UPLOAD_DIR . $request['aadhar_image_path'];
                                $fileUrl = getFileURL($request['aadhar_image_path']);
                                $extension = pathinfo($request['aadhar_image_path'], PATHINFO_EXTENSION);
                                ?>
                                <?php if (file_exists($filePath)): ?>
                                    <?php if (in_array($extension, ['jpg','jpeg','png'])): ?>
                                        <div class="text-center">
                                            <img src="<?php echo $fileUrl; ?>" 
                                                 class="img-fluid rounded shadow"
                                                 style="max-height:200px; cursor:pointer;"
                                                 onclick="window.open('<?php echo $fileUrl; ?>','_blank')">
                                            <small class="d-block mt-2 text-muted">Click to view full image</small>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo $fileUrl; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-file-pdf"></i> View PDF
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-danger">File not found</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Payment & Status -->
            <div class="col-md-4">
                <!-- Customer Contact -->
                <div class="card detail-card p-3 text-center">
                    <h5 class="mb-3"><i class="bi bi-telephone"></i> Contact Customer</h5>
                    <a href="tel:<?php echo $request['user_phone']; ?>" class="btn btn-success mb-2">
                        📞 Call Customer
                    </a>
                    <a href="https://wa.me/<?php echo $request['user_phone']; ?>" target="_blank" class="btn btn-success">
                        💬 WhatsApp
                    </a>
                </div>
                
                <!-- Payment Status -->
                <div class="card detail-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <p class="label">Payment Status:</p>
                        <?php if ($request['payment_status'] === 'success'): ?>
                            <span class="badge bg-success status-badge">Paid ✓</span>
                        <?php else: ?>
                            <span class="badge bg-danger status-badge">Unpaid</span>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <p class="label mb-1">Registration Fee:</p>
                        <p>₹<?php echo number_format($request['registration_fees'], 2); ?></p>
                        
                        <p class="label mb-1">Service Fee:</p>
                        <p>₹<?php echo number_format($request['fees'], 2); ?></p>
                        
                        <hr>
                        
                        <p class="label mb-1 fw-bold">Total Amount:</p>
                        <p class="fs-4 text-success">₹<?php echo number_format($request['fees'] + $request['registration_fees'], 2); ?></p>
                        
                        <?php if ($request['payment_id']): ?>
                            <hr>
                            <p class="label mb-1">Payment ID:</p>
                            <p class="small text-muted"><?php echo sanitizeOutput($request['payment_id']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Update Status -->
                <div class="card detail-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Update Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="mb-3">
                                <label for="application_status" class="form-label">Current Status</label>
                                <span class="badge <?php 
                                    echo match($request['application_status']) {
                                        'Pending' => 'bg-warning',
                                        'Processing' => 'bg-info',
                                        'Completed' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                ?> status-badge mb-3">
                                    <?php echo $request['application_status']; ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <label for="application_status" class="form-label">Change Status To</label>
                                <select name="application_status" class="form-select" required>
                                    <option value="Pending" <?php echo $request['application_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo $request['application_status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Completed" <?php echo $request['application_status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Rejected" <?php echo $request['application_status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks/Notes</label>
                                <textarea name="remarks" class="form-control" rows="3"><?php echo sanitizeOutput($request['remarks'] ?? ''); ?></textarea>
                                <small class="text-muted">Optional: Add notes about this application</small>
                            </div>
                            
                            <button type="submit" name="update_status" class="btn btn-primary w-100" id="updateBtn">
                                <i class="bi bi-check-circle"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelector("form").addEventListener("submit", function() {
        let btn = document.getElementById("updateBtn");
        btn.innerHTML = "Updating...";
        btn.disabled = true;
    });
    </script>
</body>
</html>
