<?php
/**
 * File Upload Handler
 * Sans Digital Work - SDW
 * Handles secure Aadhar card uploads
 */

/**
 * Handle file upload with security checks
 */
function handleAadharUpload($file, $userId) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . getUploadErrorMessage($file['error'])];
    }
    
    // Validate file size (max 2MB)
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds 2MB limit'];
    }
    
    // Validate minimum file size (prevent empty files)
    if ($file['size'] < 1024) { // Minimum 1KB
        return ['success' => false, 'message' => 'File size too small. Please upload a valid document'];
    }
    
    // Validate file type
    $allowedTypes = ALLOWED_TYPES;
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Only JPG, PNG, and PDF files are allowed'];
    }
    
    // Additional security: Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Invalid file extension'];
    }
    
    // Scan for malicious content (basic check)
    if (!validateUploadedFile($file['tmp_name'])) {
        return ['success' => false, 'message' => 'File validation failed. Please upload a valid document'];
    }
    
    // Generate secure filename
    $secureFilename = 'aadhar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
    
    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Move uploaded file
    $destination = UPLOAD_DIR . $secureFilename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Set proper permissions
        chmod($destination, 0644);
        
        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'filename' => $secureFilename,
            'path' => '/uploads/aadhar/' . $secureFilename
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

/**
 * Get human-readable upload error message
 */
function getUploadErrorMessage($errorCode) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];
    
    return $errors[$errorCode] ?? 'Unknown upload error';
}

/**
 * Validate uploaded file for malware (basic check)
 * For production, integrate with ClamAV or similar
 */
function validateUploadedFile($filepath) {
    // Basic check - verify it's actually the file type it claims to be
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return false;
    }
    
    return true;
}

/**
 * Delete uploaded file
 */
function deleteUploadedFile($filename) {
    $filepath = UPLOAD_DIR . $filename;
    
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * Get file URL for display
 */
function getFileURL($filename) {
    return SITE_URL . '/uploads/aadhar/' . $filename;
}

?>
