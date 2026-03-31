<?php
/**
 * Edit Document Requirement
 * SDW SaaS - Admin can edit document requirements
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
$error = '';
$success = '';

// Get document ID
$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    $_SESSION['error_message'] = 'Invalid document ID';
    header('Location: manage-documents.php');
    exit();
}

// Fetch document data
$stmt = $db->prepare("SELECT * FROM document_requirements WHERE id = ?");
$stmt->execute([$id]);
$document = $stmt->fetch();

if (!$document) {
    $_SESSION['error_message'] = 'Document not found';
    header('Location: manage-documents.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        try {
            $documentName = sanitizeInput($_POST['document_name']);
            $fileTypes = sanitizeInput($_POST['file_types']);
            $maxSize = floatval($_POST['max_size_mb']);
            $isRequired = isset($_POST['is_required']) ? 1 : 0;
            $description = sanitizeInput($_POST['description']);
            $displayOrder = (int)$_POST['display_order'];
            
            // Handle file upload if provided
            $fileName = $document['file_name']; // Keep existing file by default
            
            if (!empty($_FILES['file']['name'])) {
                // Validate file upload
                if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    // Check file size (2MB max)
                    if ($_FILES['file']['size'] > 2097152) {
                        throw new Exception('File size must be less than 2MB');
                    }
                    
                    // Validate file type
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                    $fileExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                    
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
                    if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                        throw new Exception('Failed to upload file');
                    }
                    
                    // Delete old file if exists
                    if (!empty($document['file_name']) && file_exists($uploadDir . $document['file_name'])) {
                        unlink($uploadDir . $document['file_name']);
                    }
                } else {
                    throw new Exception('File upload error: ' . $_FILES['file']['error']);
                }
            }
            
            // Update database
            $stmt = $db->prepare("
                UPDATE document_requirements 
                SET document_name = ?, 
                    file_name = ?,
                    file_types = ?, 
                    max_size_mb = ?, 
                    is_required = ?, 
                    description = ?, 
                    display_order = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $documentName,
                $fileName,
                $fileTypes,
                $maxSize,
                $isRequired,
                $description,
                $displayOrder,
                $id
            ]);
            
            // Log admin activity
            logAdminActivity($db, $adminId, 'Edit Document', null, "Updated document: {$documentName} (ID: {$id})");
            
            $success = 'Document updated successfully!';
            
            // Refresh document data
            $stmt = $db->prepare("SELECT * FROM document_requirements WHERE id = ?");
            $stmt->execute([$id]);
            $document = $stmt->fetch();
            
        } catch (Exception $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Get all services for dropdown
$services = $db->query("SELECT * FROM servicesand ORDER BY service_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="manage-documents.php">
                <i class="bi bi-file-earmark-text"></i> Document Management
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
    
    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo sanitizeOutput($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo sanitizeOutput($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil-square"></i> Edit Document Requirement</h2>
            <a href="manage-documents.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Documents
            </a>
        </div>
        
        <!-- Edit Form -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="service_id" class="form-label">Service</label>
                            <select class="form-select" id="service_id" name="service_id" disabled>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>" 
                                            <?php echo ($document['service_id'] == $service['id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitizeOutput($service['service_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Service cannot be changed</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="display_order" name="display_order" 
                                   value="<?php echo sanitizeOutput($document['display_order']); ?>" min="1" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_name" class="form-label">Document Name *</label>
                        <input type="text" class="form-control" id="document_name" name="document_name" 
                               value="<?php echo sanitizeOutput($document['document_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo sanitizeOutput($document['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="file_types" class="form-label">Allowed File Types</label>
                            <input type="text" class="form-control" id="file_types" name="file_types" 
                                   value="<?php echo sanitizeOutput($document['file_types']); ?>" 
                                   placeholder="jpg,jpeg,png,pdf">
                            <small class="text-muted">Comma-separated extensions</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="max_size_mb" class="form-label">Max File Size (MB)</label>
                            <input type="number" step="0.1" class="form-control" id="max_size_mb" name="max_size_mb" 
                                   value="<?php echo sanitizeOutput($document['max_size_mb']); ?>" min="0.1">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Required?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="is_required" name="is_required" 
                                       <?php echo $document['is_required'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_required">Mandatory document</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">Upload File (Optional)</label>
                        <?php if (!empty($document['file_name'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-file-earmark-check"></i> 
                                Current file: <strong><?php echo sanitizeOutput($document['file_name']); ?></strong>
                                <br><small>Leave empty to keep existing file. Upload new file to replace.</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="file" name="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="text-muted">Allowed: JPG, PNG, PDF, DOC, DOCX (Max 2MB)</small>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Document
                        </button>
                        <a href="manage-documents.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Document Info -->
        <div class="card mt-4 bg-light">
            <div class="card-body">
                <h5><i class="bi bi-info-circle"></i> Document Information</h5>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="200"><strong>Document Code:</strong></td>
                        <td><?php echo sanitizeOutput($document['document_code']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo !empty($document['created_at']) ? date('d M Y, h:i A', strtotime($document['created_at'])) : 'Unknown'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td><?php echo !empty($document['updated_at']) ? date('d M Y, h:i A', strtotime($document['updated_at'])) : 'Never updated'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
