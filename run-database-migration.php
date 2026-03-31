<?php
/**
 * Database Migration: Add uploaded_documents column
 * Required for dynamic document upload system
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>Database Migration: Add uploaded_documents Column</h2>";
echo "<hr>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h3>Step 1: Checking service_requests table structure...</h3>";
    $stmt = $db->query("DESCRIBE service_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Current columns:</p><ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']}) - {$col['Comment']}</li>";
    }
    echo "</ul>";
    
    // Check if uploaded_documents already exists
    $hasUploadedDocs = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'uploaded_documents') {
            $hasUploadedDocs = true;
            break;
        }
    }
    
    if ($hasUploadedDocs) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong style='color: green;'>✓ Column 'uploaded_documents' already exists!</strong><br>";
        echo "No migration needed.";
        echo "</div>";
    } else {
        echo "<h3>Step 2: Running migration...</h3>";
        
        // Read and execute SQL file
        $sqlFile = __DIR__ . '/database/add-uploaded-documents-column.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL migration file not found!");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            // Skip USE statement
            if (stripos(trim($statement), 'USE') === 0) continue;
            
            // Skip SELECT statements
            if (stripos(trim($statement), 'SELECT') === 0) continue;
            
            echo "<p>Executing: " . htmlspecialchars(substr($statement, 0, 80)) . "...</p>";
            $db->exec($statement);
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong style='color: green;'>✓ Migration completed successfully!</strong><br>";
        echo "Added columns: uploaded_documents, dynamic_fields to service_requests table.";
        echo "</div>";
        
        // Verify the changes
        echo "<h3>Step 3: Verifying changes...</h3>";
        $stmt = $db->query("DESCRIBE service_requests");
        $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Updated columns:</p><ul>";
        foreach ($updatedColumns as $col) {
            if (in_array($col['Field'], ['uploaded_documents', 'dynamic_fields'])) {
                echo "<li style='color: green;'><strong>✓ {$col['Field']}</strong> ({$col['Type']}) - {$col['Comment']}</li>";
            }
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>The database is now configured for dynamic document uploads</li>";
    echo "<li>Users can upload service-specific documents via apply-service.php</li>";
    echo "<li>Admins can view and edit documents at admin/manage-all-documents.php</li>";
    echo "<li>Test the complete flow:</li>";
    echo "<ul>";
    echo "<li>Login as user → Dashboard → Apply for service</li>";
    echo "<li>Upload documents based on service requirements</li>";
    echo "<li>Login as admin → Manage All Documents → View/Edit uploaded files</li>";
    echo "</ul>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong style='color: red;'>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p style='margin-top: 20px;'><a href='admin/index.php'>← Back to Admin Dashboard</a></p>";
?>
