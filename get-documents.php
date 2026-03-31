<?php
/**
 * Get Documents API (AJAX Endpoint)
 * Sans Digital Work - SDW
 * Returns document requirements for a selected service
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';

// Allow JSON response
header('Content-Type: application/json');

try {
    // DB connection
    $db = Database::getInstance()->getConnection();
    
    // Get service ID safely
    $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
    
    // Validate
    if ($serviceId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid service ID',
            'data' => []
        ]);
        exit;
    }
    
    // Fetch document requirements for selected service
    $stmt = $db->prepare("
        SELECT 
            id,
            document_name,
            document_code,
            description,
            is_required,
            file_types,
            max_size_mb,
            display_order
        FROM document_requirements
        WHERE service_id = ?
        ORDER BY display_order ASC
    ");
    
    $stmt->execute([$serviceId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Documents fetched successfully',
        'data' => $documents
    ]);
    
} catch (Exception $e) {
    error_log("Get documents error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>
