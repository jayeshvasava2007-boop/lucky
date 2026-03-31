<?php
/**
 * Complete Database Setup Script
 * SDW SaaS - All-in-one migration runner
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete Database Setup - SDW</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { 
            background: linear-gradient(135deg, #0d6efd, #6610f2); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .setup-box { 
            max-width: 900px; 
            background: white; 
            border-radius: 15px; 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
        }
        .step-card {
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class='setup-box'>
        <h2 class='text-center mb-4'><i class='bi bi-database-check'></i> Complete Database Setup</h2>
        <p class='lead'>This will create all required tables for the dynamic document system:</p>
        
        <div class='alert alert-info'>
            <strong>Tables to Create:</strong>
            <ul class='mb-0'>
                <li>✅ document_requirements - Service-wise document list</li>
                <li>✅ service_fields - Dynamic form fields</li>
                <li>✅ service_requests.dynamic_fields column - JSON storage</li>
                <li>✅ Summary views for easy management</li>
            </ul>
        </div>
        
        <hr>
";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='alert alert-success'><strong>✓ Connected to database successfully!</strong></div>";
    
    // Check if database exists
    $stmt = $db->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    $dbName = $result['db_name'];
    
    echo "<div class='alert alert-info'>Connected to database: <strong>{$dbName}</strong></div>";
    
    // Step 1: Create document_requirements table
    echo "<div class='step-card'>";
    echo "<h5><i class='bi bi-folder2-open'></i> Step 1: Creating document_requirements table...</h5>";
    
    $sql1 = "CREATE TABLE IF NOT EXISTS document_requirements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        document_name VARCHAR(100) NOT NULL,
        document_code VARCHAR(50) NOT NULL,
        file_types VARCHAR(100) DEFAULT 'jpg,jpeg,png,pdf',
        max_size_mb INT DEFAULT 2,
        is_required BOOLEAN DEFAULT TRUE,
        description TEXT NULL,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES servicesand(id) ON DELETE CASCADE,
        INDEX idx_service (service_id),
        INDEX idx_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql1);
    echo "<div class='alert alert-success'>✓ document_requirements table created!</div>";
    echo "</div>";
    
    // Step 2: Insert sample data for PAN Card
    echo "<div class='step-card'>";
    echo "<h5><i class='bi bi-person-badge'></i> Step 2: Adding PAN Card documents...</h5>";
    
    $sql2 = "INSERT INTO document_requirements (service_id, document_name, document_code, file_types, max_size_mb, is_required, description, display_order)
    VALUES 
    (1, 'Passport Size Photo', 'photo', 'jpg,jpeg,png', 1, 1, 'Recent photograph with white background', 1),
    (1, 'Signature', 'signature', 'jpg,jpeg,png', 1, 1, 'Clear signature on white paper', 2),
    (1, 'Aadhar Card Copy', 'aadhar_copy', 'jpg,pdf', 2, 1, 'Both sides of Aadhar card', 3)
    ON DUPLICATE KEY UPDATE document_name=document_name";
    
    $db->exec($sql2);
    echo "<div class='alert alert-success'>✓ PAN Card documents added!</div>";
    echo "</div>";
    
    // Step 3: Insert sample data for School Admission
    echo "<div class='step-card'>";
    echo "<h5><i class='bi bi-mortarboard'></i> Step 3: Adding School Admission documents...</h5>";
    
    $sql3 = "INSERT INTO document_requirements (service_id, document_name, document_code, file_types, max_size_mb, is_required, description, display_order)
    VALUES 
    (2, '10th Marks Memo', 'marksheet_10', 'jpg,pdf', 2, 1, 'SSC marksheet', 1),
    (2, '12th Marks Memo', 'marksheet_12', 'jpg,pdf', 2, 1, 'Intermediate marksheet', 2),
    (2, 'Transfer Certificate', 'tc', 'jpg,pdf', 2, 1, 'TC from previous school', 3),
    (2, 'Passport Size Photo', 'photo', 'jpg,jpeg,png', 1, 1, 'Recent photograph', 4)
    ON DUPLICATE KEY UPDATE document_name=document_name";
    
    $db->exec($sql3);
    echo "<div class='alert alert-success'>✓ School Admission documents added!</div>";
    echo "</div>";
    
    // Step 4: Insert sample data for Voter ID
    echo "<div class='step-card'>";
    echo "<h5><i class='bi bi-card-checking'></i> Step 4: Adding Voter ID documents...</h5>";
    
    $sql4 = "INSERT INTO document_requirements (service_id, document_name, document_code, file_types, max_size_mb, is_required, description, display_order)
    VALUES 
    (3, 'Address Proof', 'address_proof', 'jpg,pdf', 2, 1, 'Electricity bill/Rental agreement', 1),
    (3, 'Age Proof', 'age_proof', 'jpg,pdf', 2, 1, 'Birth certificate/10th memo', 2),
    (3, 'Passport Size Photo', 'photo', 'jpg,jpeg,png', 1, 1, 'Recent photograph', 3)
    ON DUPLICATE KEY UPDATE document_name=document_name";
    
    $db->exec($sql4);
    echo "<div class='alert alert-success'>✓ Voter ID documents added!</div>";
    echo "</div>";
    
    // Step 5: Create service_fields table
    echo "<div class='step-card'>";
    echo "<h5><i class='bi bi-ui-checks'></i> Step 5: Creating service_fields table...</h5>";
    
    $sql5 = "CREATE TABLE IF NOT EXISTS service_fields (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        field_label VARCHAR(100) NOT NULL,
        field_name VARCHAR(50) NOT NULL,
        field_type ENUM('text','textarea','date','email','number','select','radio','checkbox') NOT NULL DEFAULT 'text',
        options TEXT NULL,
        default_value VARCHAR(255) NULL,
        is_required BOOLEAN DEFAULT FALSE,
        description TEXT NULL,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES servicesand(id) ON DELETE CASCADE,
        INDEX idx_service (service_id),
        INDEX idx_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql5);
    echo "<div class='alert alert-success'>✓ service_fields table created!</div>";
    echo "</div>";
    
    // Step 6: Add dynamic_fields column to service_requests
    echo "<div class='step-card'>";
    echo "<h5><i class='bi bi-table'></i> Step 6: Updating service_requests table...</h5>";
    
    try {
        $sql6 = "ALTER TABLE service_requests ADD COLUMN dynamic_fields JSON NULL COMMENT 'JSON of dynamic field values' AFTER personal_data";
        $db->exec($sql6);
        echo "<div class='alert alert-success'>✓ dynamic_fields column added to service_requests!</div>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div class='alert alert-info'>ℹ️ dynamic_fields column already exists!</div>";
        } else {
            throw $e;
        }
    }
    echo "</div>";
    
    // Step 7: Create summary view
    echo "<div class='step-card'>";
    echo "<h5><i class='bi bi-eye'></i> Step 7: Creating summary views...</h5>";
    
    $sql7 = "CREATE OR REPLACE VIEW document_requirements_summary AS
    SELECT 
        s.id as service_id,
        s.service_name,
        COUNT(dr.id) as total_documents,
        SUM(CASE WHEN dr.is_required = 1 THEN 1 ELSE 0 END) as required_documents,
        GROUP_CONCAT(CONCAT(dr.document_name, ' (', dr.file_types, ')') ORDER BY dr.display_order SEPARATOR ', ') as documents_list
    FROM servicesand s
    LEFT JOIN document_requirements dr ON s.id = dr.service_id
    GROUP BY s.id, s.service_name";
    
    $db->exec($sql7);
    echo "<div class='alert alert-success'>✓ Summary views created!</div>";
    echo "</div>";
    
    // Final Success Message
    echo "<div class='alert alert-success'>
            <h4><i class='bi bi-check-circle-fill'></i> Database Setup Complete!</h4>
            <p>All tables and data have been created successfully.</p>
          </div>";
    
    echo "<hr>
          <h4>What's Ready:</h4>
          <ul>
            <li>✅ <strong>document_requirements</strong> table with sample data</li>
            <li>✅ <strong>service_fields</strong> table for dynamic forms</li>
            <li>✅ <strong>dynamic_fields</strong> JSON column in service_requests</li>
            <li>✅ <strong>Summary views</strong> for easy management</li>
          </ul>
          
          <h4 class='mt-4'>Sample Data Added:</h4>
          <div class='row'>
            <div class='col-md-4'>
                <div class='card'>
                    <div class='card-body'>
                        <h6 class='card-title'><i class='bi bi-person-badge'></i> PAN Card</h6>
                        <ul class='small mb-0'>
                            <li>Photo (1MB)</li>
                            <li>Signature (1MB)</li>
                            <li>Aadhar Copy (2MB)</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class='col-md-4'>
                <div class='card'>
                    <div class='card-body'>
                        <h6 class='card-title'><i class='bi bi-mortarboard'></i> School Admission</h6>
                        <ul class='small mb-0'>
                            <li>10th Marks (2MB)</li>
                            <li>12th Marks (2MB)</li>
                            <li>TC (2MB)</li>
                            <li>Photo (1MB)</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class='col-md-4'>
                <div class='card'>
                    <div class='card-body'>
                        <h6 class='card-title'><i class='bi bi-card-checking'></i> Voter ID</h6>
                        <ul class='small mb-0'>
                            <li>Address Proof (2MB)</li>
                            <li>Age Proof (2MB)</li>
                            <li>Photo (1MB)</li>
                        </ul>
                    </div>
                </div>
            </div>
          </div>
          
          <div class='alert alert-info mt-3'>
            <strong>💡 Next Steps:</strong><br>
            1. Visit apply-service.php to test dynamic documents<br>
            2. Select any service to see relevant upload fields<br>
            3. Fill form and submit to payment
          </div>
          
          <a href='apply-service.php' class='btn btn-primary mt-3'>Test Apply Service Page</a>
          <a href='index.php' class='btn btn-outline-secondary mt-3'>Go to Dashboard</a>
          ";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4><i class='bi bi-x-circle-fill'></i> Setup Failed</h4>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
          </div>
          <div class='alert alert-warning'>
            <strong>Troubleshooting:</strong>
            <ul class='mb-0'>
                <li>Check if XAMPP MySQL is running</li>
                <li>Verify database connection in config/database.php</li>
                <li>Ensure 'sans_digital_db' database exists</li>
                <li>Check user permissions for CREATE TABLE</li>
            </ul>
          </div>
          <pre class='bg-light p-3'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";

?>
