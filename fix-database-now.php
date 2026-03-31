<?php
/**
 * EMERGENCY FIX: Add uploaded_documents Column
 * Run this file in your browser to immediately fix the database
 */

// Database configuration
$host = 'localhost';
$dbname = 'sans_digital_db';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password (empty)

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Emergency Database Fix</h2>";
    echo "<hr>";
    
    echo "<h3>Step 1: Checking current table structure...</h3>";
    $stmt = $pdo->query("DESCRIBE service_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if uploaded_documents exists
    $hasColumn = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'uploaded_documents') {
            $hasColumn = true;
            break;
        }
    }
    
    if ($hasColumn) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h4 style='color: green;'>✓ Column 'uploaded_documents' already exists!</h4>";
        echo "<p>No fix needed. Your database is already configured.</p>";
        echo "</div>";
    } else {
        echo "<h3>Step 2: Adding missing columns...</h3>";
        
        // Add uploaded_documents column
        $sql1 = "ALTER TABLE service_requests ADD COLUMN uploaded_documents JSON NULL COMMENT 'JSON array of uploaded file paths' AFTER aadhar_image_path";
        $pdo->exec($sql1);
        echo "<p style='color: green;'>✓ Added 'uploaded_documents' column</p>";
        
        // Add dynamic_fields column
        $sql2 = "ALTER TABLE service_requests ADD COLUMN dynamic_fields JSON NULL COMMENT 'JSON encoded dynamic form fields' AFTER uploaded_documents";
        $pdo->exec($sql2);
        echo "<p style='color: green;'>✓ Added 'dynamic_fields' column</p>";
        
        echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h4 style='color: green;'>✓ Database Fixed Successfully!</h4>";
        echo "<p>The missing columns have been added to the service_requests table.</p>";
        echo "</div>";
    }
    
    // Verify final structure
    echo "<h3>Step 3: Final verification...</h3>";
    $stmt = $pdo->query("DESCRIBE service_requests");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($finalColumns as $col) {
        if (in_array($col['Field'], ['uploaded_documents', 'dynamic_fields'])) {
            echo "<tr style='background: #d4edda;'>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>✅ Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='admin/manage-all-documents.php' style='color: blue; font-weight: bold;'>Open Admin Panel</a> - The error should now be fixed</li>";
    echo "<li>Test uploading documents from apply-service.php</li>";
    echo "<li>Verify admin can view and edit documents</li>";
    echo "</ol>";
    
    echo "<hr>";
    echo "<p style='margin-top: 20px;'><a href='admin/index.php'>← Back to Admin Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h4 style='color: red;'>✗ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
