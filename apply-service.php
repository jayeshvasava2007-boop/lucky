<?php
/**
 * Apply for Service
 * Sans Digital Work - SDW
 * Handles service applications with Aadhar upload and payment
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();
generateCSRFToken();
requireLogin();

$error = '';
$success = '';
$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// Get service ID from URL parameter (user selects service beforehand)
$serviceId = (int)($_GET['id'] ?? $_POST['service_id'] ?? 0);

if ($serviceId === 0) {
    // Redirect to main page if no service selected
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
$stmt = $db->prepare("
    SELECT * FROM document_requirements 
    WHERE service_id = ? 
    ORDER BY display_order
");
$stmt->execute([$serviceId]);
$documentRequirements = $stmt->fetchAll();

// Get all available services for dropdown
$stmt = $db->query("SELECT * FROM servicesand WHERE status = 'active' ORDER BY service_name");
$allServices = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $aadharNumber = sanitizeInput($_POST['aadhar_number'] ?? '');
        $personalData = json_encode([
            'address' => $_POST['address'] ?? '',
            'dob' => $_POST['dob'] ?? '',
            'gender' => $_POST['gender'] ?? ''
        ]);
        
        // Validation
        if ($serviceId === 0) {
            $error = 'Please select a service';
        } elseif (!validateAadharFormat($aadharNumber)) {
            $error = 'Invalid Aadhar number format. Must be 12 digits';
        } elseif (!isset($_FILES['aadhar_image']) || $_FILES['aadhar_image']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload your Aadhar card';
        } else {
            // Encrypt Aadhar number
            $encryptedAadhar = encryptData($aadharNumber);
            
            // Handle Aadhar file upload
            $uploadDir = __DIR__ . '/uploads/user_' . $userId . '/service_' . $serviceId . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uploadResult = handleAadharUpload($_FILES['aadhar_image'], $userId, $uploadDir);
            
            if (!$uploadResult['success']) {
                $error = $uploadResult['message'];
            } else {
                // Handle service-specific documents upload
                $uploadedDocuments = [];
                $documentsError = '';
                
                if (isset($_POST['documents']) && is_array($_POST['documents'])) {
                    foreach ($documentRequirements as $docReq) {
                        $docCode = $docReq['document_code'];
                        
                        // Check if this document type was uploaded
                        if (isset($_FILES['documents'][$docCode]) && $_FILES['documents'][$docCode]['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES['documents'][$docCode];
                            
                            // Validate file type
                            $allowedTypes = explode(',', $docReq['file_types']);
                            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            
                            if (!in_array($fileExt, $allowedTypes)) {
                                $documentsError = "Invalid file type for {$docReq['document_name']}. Allowed: " . implode(', ', $allowedTypes);
                                break;
                            }
                            
                            // Validate file size
                            $maxSize = $docReq['max_size_mb'] * 1024 * 1024;
                            if ($file['size'] > $maxSize) {
                                $documentsError = "{$docReq['document_name']} exceeds maximum size of {$docReq['max_size_mb']}MB";
                                break;
                            }
                            
                            // Generate secure filename
                            $fileName = $docCode . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
                            $targetPath = $uploadDir . $fileName;
                            
                            // Move uploaded file
                            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                                $relativePath = 'uploads/user_' . $userId . '/service_' . $serviceId . '/' . $fileName;
                                $uploadedDocuments[$docCode] = $relativePath;
                            } else {
                                $documentsError = "Failed to upload {$docReq['document_name']}";
                                break;
                            }
                        } elseif ($docReq['is_required']) {
                            $documentsError = "Please upload {$docReq['document_name']}";
                            break;
                        }
                    }
                }
                
                if (!empty($documentsError)) {
                    $error = $documentsError;
                    // Delete uploaded Aadhar if documents failed
                    deleteUploadedFile($uploadResult['filename']);
                } else {
                    try {
                        // Get service details
                        $stmt = $db->prepare("SELECT fees, registration_fees FROM servicesand WHERE id = ?");
                        $stmt->execute([$serviceId]);
                        $service = $stmt->fetch();
                        
                        $totalAmount = $service['fees'] + $service['registration_fees'];
                        
                        // Convert documents array to JSON
                        $documentsJSON = json_encode($uploadedDocuments);
                        
                        // Create service request with uploaded documents
                        $stmt = $db->prepare("
                            INSERT INTO service_requests 
                            (user_id, service_id, aadhar_number, aadhar_image_path, uploaded_documents, personal_data, payment_status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'pending')
                        ");
                        $stmt->execute([
                            $userId,
                            $serviceId,
                            $encryptedAadhar,
                            $uploadResult['filename'],
                            $documentsJSON,
                            $personalData
                        ]);
                        
                        $requestId = $db->lastInsertId();
                        
                        // Redirect to payment options page
                        header('Location: payment-options.php?request_id=' . $requestId);
                        exit();
                        
                    } catch (Exception $e) {
                        error_log("Application error: " . $e->getMessage());
                        $error = 'Failed to submit application. Please try again.';
                        
                        // Delete uploaded files if database insert failed
                        deleteUploadedFile($uploadResult['filename']);
                        foreach ($uploadedDocuments as $docPath) {
                            deleteUploadedFile($docPath);
                        }
                    }
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
    <title>Apply for Service - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .service-option {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
            background: #fff;
        }
        .service-option:hover {
            border-color: #0d6efd;
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(13,110,253,0.15);
        }
        .service-option.selected {
            border-color: #0d6efd;
            background: linear-gradient(135deg, #e7f1ff 0%, #ffffff 100%);
            box-shadow: 0 5px 20px rgba(13,110,253,0.25);
            transform: scale(1.02);
        }
        .document-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s ease;
        }
        .document-card:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .upload-field-card {
            border-radius: 10px;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }
        .upload-field-card:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15);
        }
        .document-upload-section {
            display: none;
            animation: fadeIn 0.5s ease-in;
        }
        .document-upload-section.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <img src="log.png" style="height:40px; margin-right:10px;">
                <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                <span class="nav-item nav-link text-white me-3">
                    👋 Welcome, <?php echo sanitizeOutput($_SESSION['user_name']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="bg-primary text-white py-4 shadow-sm">
        <div class="container text-center">
            <h2 class="fw-bold mb-1">Apply for Service</h2>
            <p class="mb-0">Fill details carefully and proceed to secure payment</p>
        </div>
    </div>
    
    <div class="container">
        <div class="form-container">
            <div class="mb-4 text-center">
                <span class="badge bg-primary p-2">1. Service Selected</span>
                <span class="badge bg-secondary p-2">2. Fill Details</span>
                <span class="badge bg-secondary p-2">3. Payment</span>
            </div>
                                    
            <!-- Service Info Banner -->
            <div class="alert alert-info d-flex align-items-center mb-4">
                <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1"><?php echo sanitizeOutput($service['service_name']); ?></h5>
                    <p class="mb-0"><?php echo sanitizeOutput($service['description']); ?> | 
                       <strong>Amount:</strong> ₹<?php echo number_format($service['fees'] + $service['registration_fees'], 2); ?> 
                       (₹<?php echo number_format($service['fees'], 2); ?> + ₹<?php echo number_format($service['registration_fees'], 2); ?> registration)
                    </p>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="service_id" value="<?php echo $serviceId; ?>">
                        
                        <!-- Service Selection Dropdown -->
                        <div class="mb-4">
                            <label for="serviceSelector" class="form-label fw-bold">
                                <i class="bi bi-briefcase"></i> Select Service
                            </label>
                            <select class="form-select form-select-lg" id="serviceSelector">
                                <option value="">-- Choose a Service --</option>
                                <?php foreach ($allServices as $svc): ?>
                                    <option value="<?php echo $svc['id']; ?>" 
                                            <?php echo ($svc['id'] == $serviceId) ? 'selected' : ''; ?>>
                                        <?php echo sanitizeOutput($svc['service_name']); ?> 
                                        (₹<?php echo number_format($svc['fees'] + $svc['registration_fees'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Select a service to view its document requirements
                            </small>
                        </div>
                        
                        <!-- Document Requirements Section -->
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-folder2-open"></i> Required Documents</h5>
                            <div class="row" id="document-list">
                                <?php foreach ($documentRequirements as $doc): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-file-earmark-text fs-3 text-primary me-2"></i>
                                                    <h6 class="mb-0 fw-bold"><?php echo sanitizeOutput($doc['document_name']); ?></h6>
                                                </div>
                                                <div class="ms-4">
                                                    <?php if ($doc['is_required']): ?>
                                                        <span class="badge bg-danger ms-2">Required</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary ms-2">Optional</span>
                                                    <?php endif; ?>
                                                    <p class="small text-muted mt-2 mb-1">
                                                        <strong>Allowed:</strong> <?php echo strtoupper(str_replace(',', ', ', $doc['file_types'])); ?> | 
                                                        <strong>Max Size:</strong> <?php echo $doc['max_size_mb']; ?>MB
                                                    </p>
                                                    <?php if ($doc['description']): ?>
                                                        <small class="text-muted"><i class="bi bi-info-circle"></i> <?php echo sanitizeOutput($doc['description']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Dynamic Document Upload Fields -->
                        <div class="mb-4 document-upload-section <?php echo count($documentRequirements) > 0 ? 'active' : ''; ?>" id="documentUploadSection">
                            <h5 class="mb-3"><i class="bi bi-cloud-upload"></i> Upload Documents</h5>
                            <div id="service-documents-container">
                                <?php if (count($documentRequirements) > 0): ?>
                                    <?php foreach ($documentRequirements as $doc): ?>
                                        <div class="mb-3 document-upload-item" data-doc-code="<?php echo $doc['document_code']; ?>">
                                            <label class="form-label fw-bold">
                                                Upload <?php echo sanitizeOutput($doc['document_name']); ?>
                                                <?php if ($doc['is_required']): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                            </label>
                                            <div class="border rounded p-3 bg-light upload-field-card">
                                                <i class="bi bi-cloud-upload text-primary fs-4 me-2"></i>
                                                <input type="file" 
                                                       class="form-control d-inline-block w-auto" 
                                                       name="documents[<?php echo $doc['document_code']; ?>]" 
                                                       accept=".<?php echo str_replace(',', '.', $doc['file_types']); ?>" 
                                                       <?php echo $doc['is_required'] ? 'required' : ''; ?>
                                                       data-document-type="<?php echo $doc['document_code']; ?>">
                                                <small class="text-muted d-block mt-2">
                                                    Max <?php echo $doc['max_size_mb']; ?>MB | <?php echo strtoupper(str_replace(',', ', ', $doc['file_types'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i> No documents required for this service.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="aadhar_number" class="form-label fw-bold">Aadhar Number</label>
                            <input type="text" class="form-control" id="aadhar_number" name="aadhar_number" required 
                                   pattern="[0-9\s]{14,19}" placeholder="XXXX XXXX XXXX" maxlength="19">
                            <small class="text-muted">Enter 12-digit Aadhar number</small>
                        </div>
                        
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
                        
                        <!-- Terms & Conditions -->
                        <div class="card border-warning mb-4">
                            <div class="card-body bg-warning bg-opacity-10">
                                <h6 class="fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Important Terms & Conditions</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Non-Refundable Fees:</strong> All service fees and registration fees are non-refundable once the application process begins.</li>
                                    <li><strong>Document Accuracy:</strong> You are responsible for providing accurate and valid documents. Incorrect information may lead to rejection.</li>
                                    <li><strong>Processing Time:</strong> Processing times are estimates and may vary based on government authorities.</li>
                                    <li><strong>Data Privacy:</strong> Your personal information is encrypted and stored securely as per our Privacy Policy.</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Trust Indicators -->
                        <div class="alert alert-success mb-4">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <i class="bi bi-shield-check fs-3 text-success"></i>
                                    <p class="small fw-bold mb-0">Secure & Encrypted</p>
                                </div>
                                <div class="col-md-3">
                                    <i class="bi bi-headset fs-3 text-success"></i>
                                    <p class="small fw-bold mb-0">24/7 Support Available</p>
                                </div>
                                <div class="col-md-3">
                                    <i class="bi bi-clock-history fs-3 text-success"></i>
                                    <p class="small fw-bold mb-0">Fast Processing</p>
                                </div>
                                <div class="col-md-3">
                                    <i class="bi bi-people-fill fs-3 text-success"></i>
                                    <p class="small fw-bold mb-0">Trusted by 1000+ Customers</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Note:</strong> You will be redirected to payment gateway after submitting this form.
                            Registration fee (₹50) + Service fee will be charged.
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 btn-lg" id="submitBtn">
                            <i class="bi bi-credit-card"></i> Proceed to Payment
                        </button>
                        
                        <div class="text-center mt-3">
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <span class="badge bg-success"><i class="bi bi-lock-fill"></i> Secure Payment</span>
                                <span class="badge bg-info"><i class="bi bi-file-earmark-lock"></i> Data Protected</span>
                                <span class="badge bg-primary"><i class="bi bi-lightning-charge-fill"></i> Fast Processing</span>
                                <span class="badge bg-danger"><i class="bi bi-hand-thumbs-up-fill"></i> Trusted Service</span>
                            </div>
                            <small class="text-muted d-block mt-2">
                                👍 Join 1000+ satisfied customers. Your trust is our strength!
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/whatsapp-button.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Service selector functionality
        const serviceSelector = document.getElementById('serviceSelector');
        const documentUploadSection = document.getElementById('documentUploadSection');
        const currentServiceId = <?php echo $serviceId; ?>;
        
        // Handle service change
        serviceSelector.addEventListener('change', function() {
            const selectedServiceId = this.value;
            
            if (selectedServiceId) {
                // Redirect to apply-service.php with new service ID
                window.location.href = 'apply-service.php?id=' + selectedServiceId;
            }
        });
        
        console.log('Current Service ID:', currentServiceId);
        
        // Format Aadhar number with spaces
        document.getElementById('aadhar_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(.{4})(.{4})(.{4})/, '$1 $2 $3');
            e.target.value = value;
        });
        
        // Show processing state on form submit
        document.querySelector("form").addEventListener("submit", function() {
            let btn = document.getElementById("submitBtn");
            btn.innerHTML = "Processing...";
            btn.disabled = true;
        });
        
        // Preview uploaded files
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name;
                if (fileName) {
                    const parentDiv = e.target.closest('.upload-field-card');
                    const previewText = parentDiv.querySelector('.file-preview');
                    
                    if (!previewText) {
                        const newPreview = document.createElement('small');
                        newPreview.className = 'text-success d-block mt-2 file-preview';
                        newPreview.innerHTML = '<i class="bi bi-check-circle"></i> Selected: ' + fileName;
                        parentDiv.appendChild(newPreview);
                    } else {
                        previewText.innerHTML = '<i class="bi bi-check-circle"></i> Selected: ' + fileName;
                    }
                }
            });
        });
    </script>
</body>
</html>
