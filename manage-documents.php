<?php
/**
 * Admin - Manage Document Requirements
 * SDW SaaS - Add/Edit/Delete service documents
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

setupSecureSession();
session_start();
requireAdmin();

$db = Database::getInstance()->getConnection();
$success = '';
$error = '';

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("DELETE FROM document_requirements WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $success = 'Document deleted successfully!';
    } catch (Exception $e) {
        $error = 'Failed to delete: ' . $e->getMessage();
    }
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("
                INSERT INTO document_requirements 
                (service_id, document_name, document_code, file_types, max_size_mb, is_required, description, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['service_id'],
                $_POST['document_name'],
                strtolower(str_replace(' ', '_', $_POST['document_name'])),
                $_POST['file_types'],
                $_POST['max_size_mb'],
                isset($_POST['is_required']) ? 1 : 0,
                $_POST['description'],
                $_POST['display_order']
            ]);
            $success = 'Document added successfully!';
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $db->prepare("
                UPDATE document_requirements 
                SET document_name = ?, file_types = ?, max_size_mb = ?, 
                    is_required = ?, description = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['document_name'],
                $_POST['file_types'],
                $_POST['max_size_mb'],
                isset($_POST['is_required']) ? 1 : 0,
                $_POST['description'],
                $_POST['display_order'],
                $_POST['id']
            ]);
            $success = 'Document updated successfully!';
        }
    } catch (Exception $e) {
        $error = 'Operation failed: ' . $e->getMessage();
    }
}

// Get all services
$services = $db->query("SELECT * FROM servicesand ORDER BY id")->fetchAll();

// Get selected service filter
$selectedService = $_GET['service'] ?? 0;

// Get documents for selected service or all
if ($selectedService > 0) {
    $stmt = $db->prepare("
        SELECT dr.*, s.service_name 
        FROM document_requirements dr
        JOIN servicesand s ON dr.service_id = s.id
        WHERE dr.service_id = ?
        ORDER BY dr.display_order
    ");
    $stmt->execute([$selectedService]);
    $documents = $stmt->fetchAll();
} else {
    $documents = $db->query("
        SELECT dr.*, s.service_name 
        FROM document_requirements dr
        JOIN servicesand s ON dr.service_id = s.id
        ORDER BY dr.service_id, dr.display_order
    ")->fetchAll();
}

// Get document being edited
$editingDoc = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM document_requirements WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editingDoc = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents - Admin - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #212529; }
        .sidebar a { color: #fff; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: #0d6efd; }
        .content { padding: 30px; }
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-responsive { background: white; border-radius: 10px; padding: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 bg-dark">
                    <h5 class="text-white mb-0"><i class="bi bi-speedometer2"></i> Admin Panel</h5>
                </div>
                <a href="index.php"><i class="bi bi-house-door"></i> Dashboard</a>
                <a href="view-request.php"><i class="bi bi-folder-check"></i> View Requests</a>
                <a href="analytics.php"><i class="bi bi-graph-up"></i> Analytics</a>
                <a href="manage-documents.php" class="active"><i class="bi bi-file-earmark-text"></i> Manage Documents</a>
                <a href="../index.php"><i class="bi bi-box-arrow-left"></i> Back to Site</a>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-file-earmark-text"></i> Manage Document Requirements</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                        <i class="bi bi-plus-circle"></i> Add New Document
                    </button>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Filter by Service -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Filter by Service</label>
                                <select name="service" class="form-select" onchange="this.form.submit()">
                                    <option value="0">All Services</option>
                                    <?php foreach ($services as $svc): ?>
                                        <option value="<?php echo $svc['id']; ?>" <?php echo $selectedService == $svc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($svc['service_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Documents Table -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <?php echo $selectedService > 0 ? 'Documents for: ' . htmlspecialchars($documents[0]['service_name'] ?? '') : 'All Documents'; ?>
                            <span class="badge bg-primary float-end"><?php echo count($documents); ?> documents</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Service</th>
                                        <th>Document Name</th>
                                        <th>Code</th>
                                        <th>File Types</th>
                                        <th>Max Size</th>
                                        <th>Required</th>
                                        <th>Order</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><?php echo $doc['id']; ?></td>
                                        <td><?php echo htmlspecialchars($doc['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                        <td><code><?php echo $doc['document_code']; ?></code></td>
                                        <td><?php echo strtoupper($doc['file_types']); ?></td>
                                        <td><?php echo $doc['max_size_mb']; ?> MB</td>
                                        <td>
                                            <?php if ($doc['is_required']): ?>
                                                <span class="badge bg-danger">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $doc['display_order']; ?></td>
                                        <td>
                                            <a href="edit-document.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="?delete=<?php echo $doc['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this document?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Document Modal -->
    <div class="modal fade" id="addDocumentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Service</label>
                            <select name="service_id" class="form-select" required>
                                <?php foreach ($services as $svc): ?>
                                    <option value="<?php echo $svc['id']; ?>"><?php echo htmlspecialchars($svc['service_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Document Name</label>
                            <input type="text" name="document_name" class="form-control" placeholder="e.g., Passport Size Photo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File Types (comma-separated)</label>
                            <input type="text" name="file_types" class="form-control" placeholder="jpg,png,pdf" value="jpg,jpeg,png,pdf" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max Size (MB)</label>
                            <input type="number" name="max_size_mb" class="form-control" value="2" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_required" class="form-check-input" id="isRequired" checked>
                            <label class="form-check-label" for="isRequired">Required Document</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Add Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Document Modal (if editing) -->
    <?php if ($editingDoc): ?>
    <div class="modal fade show" id="editDocumentModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $editingDoc['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Document</h5>
                        <a href="manage-documents.php" class="btn-close"></a>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Document Name</label>
                            <input type="text" name="document_name" class="form-control" value="<?php echo htmlspecialchars($editingDoc['document_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File Types</label>
                            <input type="text" name="file_types" class="form-control" value="<?php echo htmlspecialchars($editingDoc['file_types']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max Size (MB)</label>
                            <input type="number" name="max_size_mb" class="form-control" value="<?php echo $editingDoc['max_size_mb']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($editingDoc['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="<?php echo $editingDoc['display_order']; ?>" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_required" class="form-check-input" id="editIsRequired" <?php echo $editingDoc['is_required'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="editIsRequired">Required Document</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="manage-documents.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
