<?php
/**
 * Test Document Requirements Data
 */
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if document_requirements table has data
    $stmt = $db->query("SELECT COUNT(*) as count FROM document_requirements");
    $result = $stmt->fetch();
    
    $count = $result['count'];
    
    if ($count > 0) {
        echo '<div class="status-ok">';
        echo '<i class="bi bi-check-circle"></i> <strong>SUCCESS!</strong><br>';
        echo "Found <strong>{$count}</strong> document requirements in database.<br>";
        echo '</div>';
        
        // Show breakdown by service
        $stmt = $db->query("
            SELECT s.service_name, COUNT(dr.id) as doc_count
            FROM document_requirements dr
            JOIN servicesand s ON dr.service_id = s.id
            GROUP BY s.id, s.service_name
            ORDER BY s.id
        ");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<h6 class="mt-3">Documents per Service:</h6>';
        echo '<ul>';
        foreach ($services as $svc) {
            echo "<li><strong>{$svc['service_name']}</strong>: {$svc['doc_count']} documents</li>";
        }
        echo '</ul>';
        
    } else {
        echo '<div class="status-error">';
        echo '<i class="bi bi-x-circle"></i> <strong>ERROR!</strong><br>';
        echo 'No document requirements found in database.<br>';
        echo 'Run the document requirements setup first!<br><br>';
        echo '<a href="run-document-requirements-setup.php" class="btn btn-warning">Setup Document Requirements</a>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="status-error">';
    echo '<i class="bi bi-x-circle"></i> <strong>Database Error:</strong> ' . $e->getMessage();
    echo '</div>';
}
?>
