<?php
/**
 * Admin View Documents - Document Verification System
 * SDW SaaS - Approve/Reject user uploaded documents
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

// Handle file replacement
if (isset($_POST['replace'])) {
    try {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $docId = (int)$_POST['doc_id'];
        
        // Validate file upload
        if (!isset($_FILES['new_file']) || $_FILES['new_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed. Please try again.');
        }
        
        // Check file size (2MB max)
        if ($_FILES['new_file']['size'] > 2097152) {
            throw new Exception('File size must be less than 2MB');
        }
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $fileExt = strtolower(pathinfo($_FILES['new_file']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowedTypes));
        }
        
        // Generate secure filename
        $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
        
        // Ensure upload directory exists
        $uploadDir = __DIR__ . '/../uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        $targetPath = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES['new_file']['tmp_name'], $targetPath)) {
            throw new Exception('Failed to upload file');
        }
        
        // Update database with new file
        $stmt = $db->prepare("UPDATE user_documents SET file_name = ?, original_filename = ? WHERE id = ?");
        $stmt->execute([$fileName, $_FILES['new_file']['name'], $docId]);
        
        // Log activity
        logAdminActivity($db, $adminId, 'Replace Document', null, "Replaced document file (ID: {$docId})");
        
        $success = 'File replaced successfully!';
        
    } catch (Exception $e) {
        $error = 'File replacement failed: ' . $e->getMessage();
    }
}

// Handle approve
if (isset($_POST['approve'])) {
    try {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $docId = (int)$_POST['doc_id'];
        $remark = sanitizeInput($_POST['remark'] ?? '');
        
        // Update status to approved
        $stmt = $db->prepare("
            UPDATE user_documents 
            SET status = 'approved', 
                admin_remark = ?, 
                verified_at = NOW(),
                verified_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$remark, $adminId, $docId]);
        
        // Log activity
        logAdminActivity($db, $adminId, 'Approve Document', null, "Approved document (ID: {$docId})");
        
        $success = 'Document approved successfully!';
        
    } catch (Exception $e) {
        $error = 'Approval failed: ' . $e->getMessage();
    }
}

// Handle reject
if (isset($_POST['reject'])) {
    try {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $docId = (int)$_POST['doc_id'];
        $remark = sanitizeInput($_POST['remark'] ?? '');
        
        // Update status to rejected
        $stmt = $db->prepare("
            UPDATE user_documents 
            SET status = 'rejected', 
                admin_remark = ?, 
                verified_at = NOW(),
                verified_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$remark, $adminId, $docId]);
        
        // Log activity
        logAdminActivity($db, $adminId, 'Reject Document', null, "Rejected document (ID: {$docId}): {$remark}");
        
        $success = 'Document rejected';
        
    } catch (Exception $e) {
        $error = 'Rejection failed: ' . $e->getMessage();
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$serviceFilter = (int)($_GET['service'] ?? 0);

// Build query based on filters
$whereClause = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereClause[] = "ud.status = ?";
    $params[] = $statusFilter;
}

if ($serviceFilter > 0) {
    $whereClause[] = "sr.service_id = ?";
    $params[] = $serviceFilter;
}

$whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';

// Get all uploaded documents with details
$query = "
    SELECT 
        ud.*,
        dr.document_name,
        dr.document_code,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        s.service_name,
        sr.id AS request_id,
        sr.application_status,
        a.full_name AS verified_by_name
    FROM user_documents ud
    JOIN document_requirements dr ON ud.document_id = dr.id
    JOIN users u ON ud.user_id = u.id
    JOIN service_requests sr ON ud.service_request_id = sr.id
    JOIN servicesand s ON sr.service_id = s.id
    LEFT JOIN admins a ON ud.verified_by = a.id
    {$whereSQL}
    ORDER BY ud.uploaded_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM user_documents
";
$stats = $db->query($statsQuery)->fetch();

// Get all services for filter
$services = $db->query("SELECT * FROM servicesand ORDER BY service_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Documents - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .document-card {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s ease;
        }
        .document-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .document-card.pending { border-left-color: #ffc107; }
        .document-card.approved { border-left-color: #198754; }
        .document-card.rejected { border-left-color: #dc3545; }
        
        .status-badge {
            min-width: 100px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="view-documents.php">
                <i class="bi bi-file-earmark-check"></i> Document Verification
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link" href="logout.php">
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
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p class="mb-0">Pending Review</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h3><?php echo $stats['approved']; ?></h3>
                        <p class="mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h3><?php echo $stats['rejected']; ?></h3>
                        <p class="mb-0">Rejected</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p class="mb-0">Total Documents</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status Filter</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Service Filter</label>
                        <select name="service" class="form-select">
                            <option value="0">All Services</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" 
                                        <?php echo $serviceFilter == $service['id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitizeOutput($service['service_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Documents List -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-files"></i> Uploaded Documents
                    <span class="badge bg-primary float-end"><?php echo count($documents); ?> documents</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($documents)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No documents found.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($documents as $doc): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card document-card <?php echo $doc['status']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0">
                                                <i class="bi bi-file-earmark-text"></i> 
                                                <?php echo sanitizeOutput($doc['document_name']); ?>
                                            </h6>
                                            <span class="badge <?php 
                                                echo match($doc['status']) {
                                                    'pending' => 'bg-warning text-dark',
                                                    'approved' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                }; 
                                            ?> status-badge">
                                                <?php echo ucfirst($doc['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <hr class="my-2">
                                        
                                        <p class="small mb-2">
                                            <strong>User:</strong> <?php echo sanitizeOutput($doc['user_name']); ?><br>
                                            <strong>Email:</strong> <?php echo sanitizeOutput($doc['user_email']); ?><br>
                                            <strong>Service:</strong> <?php echo sanitizeOutput($doc['service_name']); ?><br>
                                            <strong>Uploaded:</strong> <?php echo date('d M Y, h:i A', strtotime($doc['uploaded_at'])); ?>
                                        </p>
                                        
                                        <?php if (!empty($doc['admin_remark'])): ?>
                                            <div class="alert alert-light small py-2">
                                                <strong>Remark:</strong> <?php echo sanitizeOutput($doc['admin_remark']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($doc['verified_by_name'])): ?>
                                            <p class="small text-muted mb-2">
                                                <i class="bi bi-person-check"></i> Verified by: <?php echo sanitizeOutput($doc['verified_by_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <!-- File Actions -->
                                        <div class="mb-3">
                                            <a href="../uploads/documents/<?php echo sanitizeOutput($doc['file_name']); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View File
                                            </a>
                                            
                                            <?php if ($doc['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                        onclick="toggleReplaceForm(<?php echo $doc['id']; ?>)">
                                                    <i class="bi bi-arrow-repeat"></i> Replace File
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Replace File Form -->
                                        <?php if ($doc['status'] === 'pending'): ?>
                                            <div id="replace-form-<?php echo $doc['id']; ?>" style="display:none;" class="mb-3 p-3 bg-light rounded">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                    <label class="form-label small">Upload New File:</label>
                                                    <input type="file" name="new_file" class="form-control form-control-sm mb-2" required>
                                                    <button type="submit" name="replace" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-upload"></i> Upload & Replace
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Approve/Reject Form (Only for pending) -->
                                        <?php if ($doc['status'] === 'pending'): ?>
                                            <hr>
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                
                                                <div class="mb-2">
                                                    <label class="form-label small">Admin Remark:</label>
                                                    <input type="text" name="remark" class="form-control form-control-sm" 
                                                           placeholder="Add a note (optional)">
                                                </div>
                                                
                                                <div class="d-flex gap-2">
                                                    <button type="submit" name="approve" class="btn btn-sm btn-success flex-grow-1">
                                                        <i class="bi bi-check-circle"></i> Approve
                                                    </button>
                                                    <button type="submit" name="reject" class="btn btn-sm btn-danger flex-grow-1">
                                                        <i class="bi bi-x-circle"></i> Reject
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="alert alert-light small py-2">
                                                <i class="bi bi-info-circle"></i> 
                                                This document has been <?php echo $doc['status']; ?>.
                                                <?php if (!empty($doc['verified_at'])): ?>
                                                    On <?php echo date('d M Y, h:i A', strtotime($doc['verified_at'])); ?>.
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleReplaceForm(docId) {
            const form = document.getElementById('replace-form-' + docId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
