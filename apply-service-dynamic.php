<?php
/**
 * Apply for Service - Dynamic Document Upload
 * SDW SaaS - Service-wise document requirements
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload_handler.php';

setupSecureSession();
session_start();
generateCSRFToken();
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();
$error = '';
$success = '';

// Get service ID from URL or POST
$serviceId = (int)($_GET['id'] ?? $_POST['service_id'] ?? 0);

if ($serviceId === 0) {
    header('Location: index.php');
    exit();
}

// Get service details
$stmt = $db->prepare("SELECT * FROM servicesand WHERE id = ? AND status = 'active'");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    die('Service not found or inactive');
}

// Get document requirements for this service
$documentRequirements = getServiceDocumentRequirements($serviceId);

// Get dynamic fields for this service
$stmt = $db->prepare("SELECT * FROM service_fields WHERE service_id = ? ORDER BY display_order");
$stmt->execute([$serviceId]);
$fields = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $aadharNumber = sanitizeInput($_POST['aadhar_number'] ?? '');
        $personalData = json_encode([
            'address' => $_POST['address'] ?? '',
            'dob' => $_POST['dob'] ?? '',
            'gender' => $_POST['gender'] ?? ''
        ]);
        
        // Handle dynamic fields
        $dynamicData = $_POST['dynamic'] ?? [];
        $dynamicJSON = json_encode($dynamicData);
        
        // Validation
        if (!validateAadharFormat($aadharNumber)) {
            $error = 'Invalid Aadhar number format. Must be 12 digits';
        } elseif (!isset($_FILES['aadhar_image']) || $_FILES['aadhar_image']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload your Aadhar card';
        } else {
            try {
                // Encrypt Aadhar number
                $encryptedAadhar = encryptData($aadharNumber);
                
                // Handle Aadhar upload first
                $uploadDir = __DIR__ . '/uploads/user_' . $userId . '/service_' . $serviceId . '/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $aadharUpload = handleAadharUpload($_FILES['aadhar_image'], $userId, $uploadDir);
                
                if (!$aadharUpload['success']) {
                    $error = $aadharUpload['message'];
                } else {
                    // Handle service-specific documents
                    $docUpload = handleServiceDocuments($_FILES, $userId, $serviceId);
                    
                    if (!$docUpload['success']) {
                        $error = $docUpload['message'];
                        // Delete uploaded files if validation failed
                        deleteUploadedFile($aadharUpload['filename']);
                    } else {
                        // Create service request
                        $totalAmount = $service['fees'] + $service['registration_fees'];
                        
                        $stmt = $db->prepare("
                            INSERT INTO service_requests 
                            (user_id, service_id, aadhar_number, aadhar_image_path, personal_data, dynamic_fields, uploaded_documents, payment_status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                        ");
                        $stmt->execute([
                            $userId,
                            $serviceId,
                            $encryptedAadhar,
                            $aadharUpload['filename'],
                            $personalData,
                            $dynamicJSON,
                            json_encode($docUpload['documents'])
                        ]);
                        
                        $requestId = $db->lastInsertId();
                        
                        // Redirect to payment
                        header('Location: payment-options.php?request_id=' . $requestId);
                        exit();
                    }
                }
            } catch (Exception $e) {
                error_log("Application error: " . $e->getMessage());
                $error = 'Failed to submit application. Please try again.';
            }
        }
    }
}

/**
 * Helper function for Aadhar upload with custom directory
 */
function handleAadharUpload($file, $userId, $customDir = null) {
    require_once __DIR__ . '/includes/security.php';
    
    $allowedTypes = 'jpg,jpeg,png,pdf';
    $maxSizeMB = 2;
    
    // Validate file
    $validation = validateUploadedFile($file, $allowedTypes, $maxSizeMB);
    
    if (!$validation['success']) {
        return $validation;
    }
    
    // Generate filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'aadhar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    
    // Determine upload directory
    $uploadDir = $customDir ?? (__DIR__ . '/uploads/aadhar/');
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => false,
            'message' => 'Failed to upload file'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'File uploaded successfully',
        'filename' => $filename,
        'filepath' => str_replace(__DIR__ . '/', '', $filepath)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo sanitizeOutput($service['service_name']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .form-container { max-width: 900px; margin: 40px auto; }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .doc-card {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 2px solid #e0e0e0;
            background: #fff;
            transition: all 0.3s ease;
        }
        .doc-card:hover {
            border-color: #0d6efd;
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(13,110,253,0.15);
        }
        .doc-icon { font-size: 2rem; color: #0d6efd; }
        .required-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; }
        .navbar { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15);
        }
        button {
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        h5 {
            color: #0d6efd;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand fw-bold" href="index.php">
            <img src="log.png" style="height:35px; margin-right:10px;">
            <?php echo SITE_NAME; ?>
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="text-white me-3">
                👋 Welcome, <?php echo sanitizeOutput($_SESSION['user_name']); ?>
            </span>
            <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="bg-primary text-white py-4 shadow-sm">
        <div class="container text-center">
            <h2 class="fw-bold mb-1"><?php echo sanitizeOutput($service['service_name']); ?></h2>
            <p class="mb-0">Fill details and upload required documents</p>
        </div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <div class="mb-4 text-center">
                <span class="badge bg-primary p-2">1. Select Service</span>
                <span class="badge bg-primary p-2">2. Fill Details & Upload</span>
                <span class="badge bg-secondary p-2">3. Payment</span>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>
            
            <div class="card p-4">
                <h4 class="mb-3"><i class="bi bi-file-earmark-text"></i> Documents Required</h4>
                <p class="text-muted">Following are the documents needed for <?php echo sanitizeOutput($service['service_name']); ?>:</p>
                
                <div class="row mb-4">
                    <?php foreach ($documentRequirements as $index => $doc): ?>
                    <div class="col-md-4">
                        <div class="doc-card text-center">
                            <div class="doc-icon mb-2">
                                <?php if (strpos($doc['document_code'], 'photo') !== false): ?>
                                    <i class="bi bi-image"></i>
                                <?php elseif (strpos($doc['document_code'], 'sign') !== false): ?>
                                    <i class="bi bi-pen"></i>
                                <?php elseif (strpos($doc['document_code'], 'marksheet') !== false): ?>
                                    <i class="bi bi-journal"></i>
                                <?php elseif (strpos($doc['document_code'], 'aadhar') !== false): ?>
                                    <i class="bi bi-person-badge"></i>
                                <?php else: ?>
                                    <i class="bi bi-file-earmark"></i>
                                <?php endif; ?>
                            </div>
                            <h6><?php echo sanitizeOutput($doc['document_name']); ?></h6>
                            <?php if ($doc['is_required']): ?>
                                <span class="required-badge">Required</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Optional</span>
                            <?php endif; ?>
                            <small class="d-block mt-2 text-muted">
                                Max <?php echo $doc['max_size_mb']; ?>MB | <?php echo strtoupper(str_replace(',', ', ', $doc['file_types'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <hr class="my-4">
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="service_id" value="<?php echo $serviceId; ?>">
                    
                    <!-- Aadhar Number -->
                    <div class="mb-3">
                        <label for="aadhar_number" class="form-label fw-bold">Aadhar Number</label>
                        <input type="text" class="form-control" id="aadhar_number" name="aadhar_number" required 
                               pattern="[0-9\s]{14,19}" placeholder="XXXX XXXX XXXX" maxlength="19">
                        <small class="text-muted">Enter 12-digit Aadhar number</small>
                    </div>
                    
                    <!-- Aadhar Upload -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Upload Aadhar Card</label>
                        <div class="border rounded p-3 text-center bg-light">
                            <i class="bi bi-cloud-upload fs-1 text-primary"></i>
                            <input type="file" class="form-control mt-2" name="aadhar_image" 
                                   accept="image/jpeg,image/png,application/pdf" required>
                            <small class="text-muted">JPG, PNG, PDF (Max 2MB)</small>
                        </div>
                    </div>
                    
                    <!-- Personal Details -->
                    <h5 class="mt-4 mb-3"><i class="bi bi-person"></i> Personal Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dob" class="form-label fw-bold">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label fw-bold">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label fw-bold">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                    </div>
                    
                    <!-- Dynamic Service Fields -->
                    <?php if (!empty($fields)): ?>
                    <h5 class="mt-4 mb-3"><i class="bi bi-ui-checks"></i> Additional Information</h5>
                    
                    <?php foreach ($fields as $field): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <?php echo sanitizeOutput($field['field_label']); ?>
                            <?php if ($field['is_required']): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($field['field_type'] === 'text'): ?>
                            <input type="text" class="form-control"
                                name="dynamic[<?php echo $field['field_name']; ?>]"
                                value="<?php echo sanitizeOutput($field['default_value'] ?? ''); ?>"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>>

                        <?php elseif ($field['field_type'] === 'textarea'): ?>
                            <textarea class="form-control" rows="3"
                                name="dynamic[<?php echo $field['field_name']; ?>]"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>><?php echo sanitizeOutput($field['default_value'] ?? ''); ?></textarea>

                        <?php elseif ($field['field_type'] === 'date'): ?>
                            <input type="date" class="form-control"
                                name="dynamic[<?php echo $field['field_name']; ?>]"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>>

                        <?php elseif ($field['field_type'] === 'email'): ?>
                            <input type="email" class="form-control"
                                name="dynamic[<?php echo $field['field_name']; ?>]"
                                value="<?php echo sanitizeOutput($field['default_value'] ?? ''); ?>"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>>

                        <?php elseif ($field['field_type'] === 'number'): ?>
                            <input type="number" class="form-control"
                                name="dynamic[<?php echo $field['field_name']; ?>]"
                                step="0.01"
                                value="<?php echo sanitizeOutput($field['default_value'] ?? ''); ?>"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>>

                        <?php elseif ($field['field_type'] === 'select'): ?>
                            <select class="form-select"
                                name="dynamic[<?php echo $field['field_name']; ?>]"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                <option value="">Select</option>
                                <?php foreach (explode(',', $field['options']) as $opt): ?>
                                    <option value="<?php echo trim($opt); ?>">
                                        <?php echo trim($opt); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php elseif ($field['field_type'] === 'radio'): ?>
                            <?php foreach (explode(',', $field['options']) as $index => $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                        name="dynamic[<?php echo $field['field_name']; ?>]"
                                        id="<?php echo $field['field_name']; ?>_<?php echo $index; ?>"
                                        value="<?php echo trim($opt); ?>"
                                        <?php echo ($field['default_value'] ?? '') === trim($opt) ? 'checked' : ''; ?>
                                        <?php echo $field['is_required'] && $index === 0 ? 'required' : ''; ?>>
                                    <label class="form-check-label" for="<?php echo $field['field_name']; ?>_<?php echo $index; ?>">
                                        <?php echo trim($opt); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>

                        <?php elseif ($field['field_type'] === 'checkbox'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    name="dynamic[<?php echo $field['field_name']; ?>]"
                                    id="<?php echo $field['field_name']; ?>"
                                    value="1"
                                    <?php echo ($field['default_value'] ?? '') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $field['field_name']; ?>">
                                    <?php echo sanitizeOutput($field['field_label']); ?>
                                </label>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($field['description']): ?>
                            <small class="text-muted"><?php echo sanitizeOutput($field['description']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Dynamic Document Upload Fields -->
                    <h5 class="mb-3 mt-4"><i class="bi bi-folder"></i> Upload Service Documents</h5>
                    
                    <?php foreach ($documentRequirements as $doc): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <?php echo sanitizeOutput($doc['document_name']); ?>
                            <?php if ($doc['is_required']): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>
                        <div class="border rounded p-3 bg-light">
                            <input type="file" class="form-control" 
                                   name="<?php echo $doc['document_code']; ?>" 
                                   accept=".<?php echo str_replace(',', '.', $doc['file_types']); ?>"
                                   <?php echo $doc['is_required'] ? 'required' : ''; ?>>
                            <small class="text-muted">
                                Allowed: <?php echo strtoupper(str_replace(',', ', ', $doc['file_types'])); ?> | 
                                Max Size: <?php echo $doc['max_size_mb']; ?>MB
                                <?php if ($doc['description']): ?>
                                    <br><i class="bi bi-info-circle"></i> <?php echo sanitizeOutput($doc['description']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="alert alert-info mt-4">
                        <strong><i class="bi bi-info-circle"></i> Note:</strong> 
                        You will be redirected to payment gateway after submitting this form.
                        Total amount: ₹<?php echo number_format($service['fees'] + $service['registration_fees'], 2); ?> 
                        (₹<?php echo number_format($service['fees'], 2); ?> + ₹<?php echo number_format($service['registration_fees'], 2); ?> registration)
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 btn-lg" id="submitBtn">
                        <i class="bi bi-credit-card"></i> Proceed to Payment
                    </button>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            🔒 Secure Upload | 📄 Data Protected | ⚡ Fast Processing
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show processing state on form submit
        document.querySelector("form").addEventListener("submit", function() {
            let btn = document.getElementById("submitBtn");
            btn.innerHTML = "Processing...";
            btn.disabled = true;
        });
    </script>
</body>
</html>
