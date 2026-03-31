<?php
/**
 * Run Database Migration for Address and Aadhar Fields
 * This script adds address, aadhar_front, and aadhar_back columns to users table
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>Database Migration: Adding Address and Aadhar Fields</h2>";
echo "<hr>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h3>Step 1: Checking current users table structure...</h3>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Current columns: " . implode(', ', $columns) . "</p>";
    
    // Check if columns already exist
    $hasAddress = in_array('address', $columns);
    $hasAadharFront = in_array('aadhar_front', $columns);
    $hasAadharBack = in_array('aadhar_back', $columns);
    
    if ($hasAddress && $hasAadharFront && $hasAadharBack) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✓ All columns already exist!</strong><br>";
        echo "The users table already has address, aadhar_front, and aadhar_back columns.";
        echo "</div>";
    } else {
        echo "<h3>Step 2: Running migration...</h3>";
        
        // Read and execute SQL file
        $sqlFile = __DIR__ . '/database/add-user-address-aadhar.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL migration file not found!");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            // Skip USE statement as we're already connected to the database
            if (stripos(trim($statement), 'USE') === 0) continue;
            
            echo "<p>Executing: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
            $db->exec($statement);
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✓ Migration completed successfully!</strong><br>";
        echo "Added columns: address, aadhar_front, aadhar_back to users table.";
        echo "</div>";
        
        // Verify the changes
        echo "<h3>Step 3: Verifying changes...</h3>";
        $stmt = $db->query("DESCRIBE users");
        $updatedColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Updated columns: " . implode(', ', $updatedColumns) . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>The registration form now includes address field</li>";
    echo "<li>Aadhar card upload options (front and back) are available</li>";
    echo "<li>Test the registration process at: <a href='register.php' target='_blank'>register.php</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong style='color: red;'>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p style='margin-top: 20px;'><a href='index.php'>← Back to Home</a></p>";
?>
