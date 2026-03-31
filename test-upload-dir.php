<?php
/**
 * Test Upload Directory
 */

$uploadDir = __DIR__ . '/uploads/';
$testDir = $uploadDir . 'test_' . time();

echo '<h6 class="mb-2">Checking directory structure...</h6>';

// Check if uploads directory exists
if (!is_dir($uploadDir)) {
    echo '<div class="status-error">';
    echo '<i class="bi bi-x-circle"></i> <strong>ERROR!</strong><br>';
    echo 'Uploads directory does not exist.<br>';
    echo 'Creating it now... ';
    
    if (mkdir($uploadDir, 0755, true)) {
        echo '<span style="color: green;">SUCCESS!</span><br>';
        echo 'Directory created at: ' . $uploadDir;
        echo '</div>';
    } else {
        echo '<span style="color: red;">FAILED!</span><br>';
        echo 'Cannot create directory. Check permissions.';
        echo '</div>';
    }
} else {
    echo '<div class="status-ok">';
    echo '<i class="bi bi-check-circle"></i> <strong>SUCCESS!</strong><br>';
    echo 'Uploads directory EXISTS at: ' . $uploadDir . '<br>';
    
    // Check if writable
    if (is_writable($uploadDir)) {
        echo 'Directory is <strong>writable</strong> ✅<br>';
        
        // Try to create test subdirectory
        if (mkdir($testDir, 0755, true)) {
            echo 'Test subdirectory created successfully ✅<br>';
            rmdir($testDir); // Clean up
            echo 'Test cleanup completed ✅';
        } else {
            echo 'Warning: Could not create test subdirectory ⚠️';
        }
    } else {
        echo 'Warning: Directory may not be writable ⚠️<br>';
        echo 'Please check permissions (should be 755 or 777)';
    }
    
    echo '</div>';
    
    // Show directory info
    echo '<h6 class="mt-3">Directory Info:</h6>';
    echo '<ul>';
    echo '<li>Path: ' . realpath($uploadDir) . '</li>';
    echo '<li>Permissions: ' . substr(sprintf('%o', fileperms($uploadDir)), -4) . '</li>';
    echo '<li>Writable: ' . (is_writable($uploadDir) ? 'Yes' : 'No') . '</li>';
    echo '</ul>';
}
?>
