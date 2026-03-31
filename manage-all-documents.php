<?php
/**
 * Admin - Manage All User Documents
 * Sans Digital Work - SDW
 * View, edit, and update all user-uploaded documents across all services
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

// Setup secure session
setupSecureSession();
session_start();
requireAdmin();

$db = Database::getInstance()->getConnection();
$adminId = getCurrentAdminId();
$success = '';
$error = '';

// Handle document update/replacement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_document'])) {
    try {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $requestId = (int)$_POST['request_id'];
        $docCode = sanitizeInput($_POST['document_code']);
        $remarks = sanitizeInput($_POST['admin_remarks'] ?? '');
        
        // Handle file upload if provided
        $newFilePath = '';
        if (isset($_FILES['replacement_file']) && $_FILES['replacement_file']['error'] === UPLOAD_ERR_OK) {
            // Validate file size (5MB max)
            if ($_FILES['replacement_file']['size'] > 5242880) {
                throw new Exception('File size must be less than 5MB');
            }
            
            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            $fileExt = strtolower(pathinfo($_FILES['replacement_file']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception('Invalid file type. Allowed: JPG, PNG, PDF, DOC, DOCX');
            }
            
            // Generate secure filename
            $fileName = 'doc_' . $requestId . '_' . $docCode . '_' . time() . '.' . $fileExt;
            
            // Ensure upload directory exists
            $uploadDir = __DIR__ . '/../uploads/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Move uploaded file
            $targetPath = $uploadDir . $fileName;
            if (!move_uploaded_file($_FILES['replacement_file']['tmp_name'], $targetPath)) {
                throw new Exception('Failed to upload replacement file');
            }
            
            $newFilePath = 'uploads/documents/' . $fileName;
        }
        
        // Get current request data
        $stmt = $db->prepare("SELECT uploaded_documents FROM service_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception('Service request not found');
        }
        
        // Parse existing documents JSON
        $documents = json_decode($request['uploaded_documents'], true) ?? [];
        
        // Update or add the document path
        if (!empty($newFilePath)) {
            $documents[$docCode] = $newFilePath;
            
            // Convert back to JSON
            $documentsJSON = json_encode($documents);
            
            // Update database
            $stmt = $db->prepare("
                UPDATE service_requests 
                SET uploaded_documents = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$documentsJSON, $requestId]);
            
            // Log activity
            logAdminActivity($db, $adminId, 'Update Document', $requestId, 
                "Updated document '{$docCode}' for request #{$requestId}. New file: {$newFilePath}");
            
            $success = "Document updated successfully!";
        } else {
            throw new Exception('Please select a file to upload');
        }
        
    } catch (Exception $e) {
        $error = 'Update failed: ' . $e->getMessage();
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$serviceFilter = (int)($_GET['service'] ?? 0);
$searchTerm = sanitizeInput($_GET['search'] ?? '');

// Build query
$whereClause = "1=1";
$params = [];

if ($statusFilter !== 'all') {
    $whereClause .= " AND sr.payment_status = ?";
    $params[] = $statusFilter;
}

if ($serviceFilter > 0) {
    $whereClause .= " AND sr.service_id = ?";
    $params[] = $serviceFilter;
}

if (!empty($searchTerm)) {
    $whereClause .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.service_name LIKE ?)";
    $searchParam = "%{$searchTerm}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Get all service requests with documents
$query = "
    SELECT 
        sr.id,
        sr.created_at,
        sr.updated_at,
        sr.aadhar_number,
        sr.aadhar_image_path,
        sr.uploaded_documents,
        sr.payment_status,
        sr.application_status,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        s.id AS service_id,
        s.service_name,
        (s.fees + s.registration_fees) AS total_amount
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    JOIN servicesand s ON sr.service_id = s.id
    WHERE {$whereClause}
    ORDER BY sr.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get all services for filter
$servicesQuery = "SELECT id, service_name FROM servicesand WHERE status = 'active' ORDER BY service_name";
$services = $db->query($servicesQuery)->fetchAll();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN payment_status = 'success' THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM service_requests
";
$stats = $db->query($statsQuery)->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Documents - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table-responsive { max-height: 80vh; }
        .document-badge { font-size: 0.85rem; }
        .status-paid { background-color: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; }
        .status-pending { background-color: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 20px; }
        .btn-view { background-color: #0d6efd; color: white; border: none; }
        .btn-edit { background-color: #ffc107; color: #000; border: none; }
        .modal-header { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; }
        .document-preview { max-width: 100%; height: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 8px; }
        .upload-area { border: 2px dashed #dee2e6; padding: 30px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { border-color: #0d6efd; background-color: #f8f9fa; }
        .filter-section { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <img src="log.png" alt="Logo" style="height: 40px; margin-right: 12px;">
                <span>Admin Panel</span>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-white me-3">
                    👋 Lucky
                </span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i> <?php echo sanitizeOutput($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo sanitizeOutput($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-folder2-open"></i> Manage All User Documents</h2>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                <p class="text-muted">View, edit, and update all documents uploaded by users</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Total Requests</h5>
                        <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-check-circle"></i> Paid Requests</h5>
                        <h2 class="mb-0"><?php echo $stats['paid']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-clock"></i> Pending Payment</h5>
                        <h2 class="mb-0"><?php echo $stats['pending']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section shadow-sm">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Payment Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="success" <?php echo $statusFilter === 'success' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Service</label>
                    <select name="service" class="form-select">
                        <option value="0">All Services</option>
                        <?php foreach ($services as $svc): ?>
                            <option value="<?php echo $svc['id']; ?>" <?php echo $serviceFilter == $svc['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitizeOutput($svc['service_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by user name, email, or service"
                           value="<?php echo sanitizeOutput($searchTerm); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Documents Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Payment</th>
                                <th>Documents</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($requests) > 0): ?>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><strong>#<?php echo $req['id']; ?></strong></td>
                                        <td>
                                            <div>
                                                <strong><?php echo sanitizeOutput($req['user_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo sanitizeOutput($req['user_email']); ?></small><br>
                                                <small class="text-muted"><?php echo sanitizeOutput($req['user_phone']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo sanitizeOutput($req['service_name']); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo date('d M Y', strtotime($req['created_at'])); ?></small><br>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($req['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($req['payment_status'] === 'success'): ?>
                                                <span class="status-paid"><i class="bi bi-check-circle"></i> Paid</span>
                                            <?php else: ?>
                                                <span class="status-pending"><i class="bi bi-clock"></i> Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $documents = json_decode($req['uploaded_documents'], true) ?? [];
                                            $docCount = count($documents);
                                            $hasAadhar = !empty($req['aadhar_image_path']);
                                            $totalDocs = $docCount + ($hasAadhar ? 1 : 0);
                                            ?>
                                            <span class="badge bg-primary document-badge">
                                                <i class="bi bi-file-earmark-text"></i> <?php echo $totalDocs; ?> document(s)
                                            </span>
                                            <?php if ($hasAadhar): ?>
                                                <br><small class="text-success"><i class="bi bi-check-circle"></i> Aadhaar uploaded</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-view" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewModal<?php echo $req['id']; ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <button type="button" class="btn btn-sm btn-edit" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $req['id']; ?>">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- View Document Modal -->
                                    <?php include __DIR__ . '/modals/view-documents-modal.php'; ?>
                                    
                                    <!-- Edit Document Modal -->
                                    <?php include __DIR__ . '/modals/edit-documents-modal.php'; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-inbox fs-1 text-muted"></i>
                                        <p class="text-muted mt-3">No documents found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // File input preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name;
                if (fileName) {
                    const previewDiv = e.target.closest('.modal-body').querySelector('.file-preview');
                    if (previewDiv) {
                        previewDiv.innerHTML = '<i class="bi bi-file-earmark-check text-success"></i> Selected: ' + fileName;
                    }
                }
            });
        });
    </script>
</body>
</html>
