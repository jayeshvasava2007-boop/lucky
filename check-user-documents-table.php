<?php
/**
 * Check user_documents table structure
 */
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h1>🔍 user_documents Table Checker</h1>";
echo "<hr>";

try {
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'user_documents'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table <strong>user_documents</strong> EXISTS<br><br>";
        
        // Get column structure
        $stmt = $db->query("DESCRIBE user_documents");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br><hr>";
        echo "<h3>Sample Data (if any):</h3>";
        try {
            $stmt = $db->query("SELECT * FROM user_documents LIMIT 3");
            $data = $stmt->fetchAll();
            if (count($data) > 0) {
                echo "<pre>";
                print_r($data[0]);
                echo "</pre>";
            } else {
                echo "No data yet.";
            }
        } catch (Exception $e) {
            echo "Cannot read data: " . $e->getMessage();
        }
        
    } else {
        echo "❌ Table <strong>user_documents</strong> does NOT exist!<br>";
        echo "<br><strong>Solution:</strong> Run the SQL creation script.";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<br><br>";
echo "<a href='admin/view-documents.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to View Documents →</a>";
?>

<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; }
    h1 { color: #667eea; }
    table { width: 100%; margin: 20px 0; }
    th { background: #667eea; color: white; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>
