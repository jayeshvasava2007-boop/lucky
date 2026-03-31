<?php
/**
 * Add Documents for All Services
 * SDW SaaS - Complete document requirements setup
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Service Documents - SDW</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .setup-box { 
            max-width: 1000px; 
            background: white; 
            border-radius: 15px; 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
        }
        .service-card {
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class='setup-box'>
        <h2 class='text-center mb-4'><i class='bi bi-file-earmark-plus'></i> Add Document Requirements</h2>
        <p class='lead'>This will add document requirements for all services:</p>
        
        <div class='alert alert-info'>
            <strong>Services to Configure:</strong>
            <ul class='mb-0'>
                <li>✅ Driving License (5 documents)</li>
                <li>✅ Bank & Financial Services (6 documents)</li>
                <li>✅ Job Placement & Online Work (6 documents)</li>
                <li>✅ School & College Admission (8 documents)</li>
                <li>✅ Passport Seva (5 documents)</li>
            </ul>
        </div>
        
        <hr>
";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='alert alert-success'><strong>✓ Connected to database!</strong></div>";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database/all-services-documents.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found!");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Split into statements
    $statements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $executed = 0;
    $servicesAdded = [];
    
    foreach ($statements as $statement) {
        try {
            if (trim($statement) && stripos($statement, 'INSERT INTO document_requirements') !== false) {
                $db->exec($statement);
                $executed++;
                
                // Extract service ID from INSERT statement
                preg_match("/VALUES\s*\((\d+)/i", $statement, $matches);
                if (isset($matches[1]) && !in_array($matches[1], $servicesAdded)) {
                    $servicesAdded[] = $matches[1];
                }
            } elseif (trim($statement) && !stripos($statement, 'SELECT')) {
                $db->exec($statement);
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "<div class='alert alert-warning'>Note: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    echo "<div class='alert alert-success'>
            <h4><i class='bi bi-check-circle-fill'></i> Documents Added Successfully!</h4>
            <p><strong>{$executed}</strong> service configurations added.</p>
          </div>";
    
    // Display what was added
    echo "<h4 class='mt-4'>Documents Configured:</h4>";
    
    $serviceNames = [
        4 => 'Passport Seva',
        5 => 'Driving License',
        6 => 'Bank & Financial Services',
        7 => 'Job Placement & Online Work',
        8 => 'School & College Admission'
    ];
    
    foreach ($servicesAdded as $serviceId) {
        $serviceName = $serviceNames[$serviceId] ?? "Service ID: {$serviceId}";
        
        // Get count of documents for this service
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM document_requirements WHERE service_id = ?");
        $stmt->execute([$serviceId]);
        $result = $stmt->fetch();
        
        echo "<div class='service-card'>
                <h5><i class='bi bi-check-circle text-success'></i> {$serviceName}</h5>
                <p class='mb-0'><strong>{$result['count']}</strong> documents configured</p>
              </div>";
    }
    
    echo "<hr>
          <div class='alert alert-info'>
            <strong>💡 What's Next?</strong><br>
            1. Visit apply-service.php to test<br>
            2. Select any service to see dynamic document fields<br>
            3. Use admin panel to add/edit documents (coming soon)
          </div>
          
          <div class='row mt-4'>
            <div class='col-md-4'>
                <a href='apply-service.php' class='btn btn-success w-100'>
                    <i class='bi bi-play-circle'></i> Test Apply Service
                </a>
            </div>
            <div class='col-md-4'>
                <a href='index.php' class='btn btn-outline-primary w-100'>
                    <i class='bi bi-house-door'></i> Dashboard
                </a>
            </div>
            <div class='col-md-4'>
                <a href='admin/' class='btn btn-outline-secondary w-100'>
                    <i class='bi bi-person-gear'></i> Admin Panel
                </a>
            </div>
          </div>
          ";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4><i class='bi bi-x-circle-fill'></i> Setup Failed</h4>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
          </div>
          <p>Please check:</p>
          <ul>
            <li>XAMPP MySQL is running</li>
            <li>Database connection in config/database.php</li>
            <li>Services exist in servicesand table</li>
          </ul>
          <pre class='bg-light p-3'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";

?>
