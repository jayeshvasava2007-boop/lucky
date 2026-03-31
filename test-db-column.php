<?php
/**
 * Test Database Column
 */
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if uploaded_documents column exists
    $stmt = $db->query("SHOW COLUMNS FROM service_requests LIKE 'uploaded_documents'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo '<div class="status-ok">';
        echo '<i class="bi bi-check-circle"></i> <strong>SUCCESS!</strong><br>';
        echo 'Column <code>uploaded_documents</code> EXISTS in service_requests table.<br>';
        echo 'Type: ' . $result['Type'] . ' | Null: ' . $result['Null'];
        echo '</div>';
        
        // Show all columns
        echo '<h6 class="mt-3">All columns in service_requests:</h6>';
        $stmt = $db->query("SHOW COLUMNS FROM service_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<ul>';
        foreach ($columns as $col) {
            $highlight = ($col['Field'] === 'uploaded_documents' || $col['Field'] === 'dynamic_fields') ? 
                'style="color: green; font-weight: bold;"' : '';
            echo "<li {$highlight}>{$col['Field']} ({$col['Type']})</li>";
        }
        echo '</ul>';
        
    } else {
        echo '<div class="status-error">';
        echo '<i class="bi bi-x-circle"></i> <strong>ERROR!</strong><br>';
        echo 'Column <code>uploaded_documents</code> does NOT exist.<br>';
        echo 'You need to run the database fix first!<br><br>';
        echo '<a href="FIX-DATABASE.html" class="btn btn-warning">Fix Database Now</a>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="status-error">';
    echo '<i class="bi bi-x-circle"></i> <strong>Database Error:</strong> ' . $e->getMessage();
    echo '</div>';
}
?>
