<?php
/**
 * Enhanced Upload Handler for Service-Wise Documents
 * SDW SaaS - Dynamic document upload with organized folder structure
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * Handle multiple document uploads for a service
 */
function handleServiceDocuments($files, $userId, $serviceId, $requestId = null) {
    $db = Database::getInstance()->getConnection();
    
    try {
        // Get document requirements for this service
        $stmt = $db->prepare("SELECT * FROM document_requirements WHERE service_id = ? ORDER BY display_order");
        $stmt->execute([$serviceId]);
        $requirements = $stmt->fetchAll();
        
        if (empty($requirements)) {
            return [
                'success' => false,
                'message' => 'No document requirements found for this service'
            ];
        }
        
        $uploadedDocs = [];
        $uploadDir = __DIR__ . '/../uploads/user_' . $userId . '/service_' . $serviceId . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($requirements as $doc) {
            $docCode = $doc['document_code'];
            
            // Check if file was uploaded for this document type
            if (!isset($files[$docCode]) || $files[$docCode]['error'] === UPLOAD_ERR_NO_FILE) {
                if ($doc['is_required']) {
                    return [
                        'success' => false,
                        'message' => 'Missing required document: ' . $doc['document_name']
                    ];
                }
                continue;
            }
            
            $file = $files[$docCode];
            
            // Validate file
            $validation = validateUploadedFile($file, $doc['file_types'], $doc['max_size_mb']);
            
            if (!$validation['success']) {
                return [
                    'success' => false,
                    'message' => $doc['document_name'] . ': ' . $validation['message']
                ];
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $docCode . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return [
                    'success' => false,
                    'message' => 'Failed to upload: ' . $doc['document_name']
                ];
            }
            
            // Store document info
            $uploadedDocs[] = [
                'document_code' => $docCode,
                'document_name' => $doc['document_name'],
                'filename' => $filename,
                'filepath' => str_replace(__DIR__ . '/../', '', $filepath),
                'file_size' => $file['size'],
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // If request ID provided, update database
        if ($requestId && !empty($uploadedDocs)) {
            $stmt = $db->prepare("UPDATE service_requests SET uploaded_documents = ? WHERE id = ?");
            $stmt->execute([json_encode($uploadedDocs), $requestId]);
        }
        
        return [
            'success' => true,
            'message' => count($uploadedDocs) . ' document(s) uploaded successfully',
            'documents' => $uploadedDocs,
            'upload_dir' => $uploadDir
        ];
        
    } catch (Exception $e) {
        error_log("Handle service documents error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to process documents. Please try again.'
        ];
    }
}

/**
 * Validate uploaded file
 */
function validateUploadedFile($file, $allowedTypes, $maxSizeMB) {
    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File upload error occurred'
        ];
    }
    
    // Check file size
    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
    if ($file['size'] > $maxSizeBytes) {
        return [
            'success' => false,
            'message' => 'File size exceeds ' . $maxSizeMB . 'MB limit'
        ];
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = array_map('trim', explode(',', $allowedTypes));
    
    if (!in_array($extension, $allowedExtensions)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Allowed: ' . strtoupper(str_replace(',', ', ', $allowedTypes))
        ];
    }
    
    // Check MIME type for images
    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/jpg'])) {
            return [
                'success' => false,
                'message' => 'Invalid image file'
            ];
        }
    }
    
    return ['success' => true, 'message' => 'File validation passed'];
}

/**
 * Get uploaded documents for a request
 */
function getUploadedDocuments($requestId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("SELECT uploaded_documents FROM service_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $result = $stmt->fetch();
        
        if ($result && $result['uploaded_documents']) {
            return json_decode($result['uploaded_documents'], true);
        }
        
        return [];
        
    } catch (Exception $e) {
        error_log("Get uploaded documents error: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete uploaded documents
 */
function deleteUploadedDocuments($documents) {
    $deleted = 0;
    
    foreach ($documents as $doc) {
        $filepath = __DIR__ . '/../' . $doc['filepath'];
        if (file_exists($filepath) && unlink($filepath)) {
            $deleted++;
        }
    }
    
    return $deleted;
}

/**
 * Get document requirements for a service (for frontend display)
 */
function getServiceDocumentRequirements($serviceId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("SELECT * FROM document_requirements WHERE service_id = ? ORDER BY display_order");
        $stmt->execute([$serviceId]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Get document requirements error: " . $e->getMessage());
        return [];
    }
}

?>
