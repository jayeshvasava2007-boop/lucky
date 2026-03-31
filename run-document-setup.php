<?php
/**
 * Document System Migration Runner
 * SDW SaaS - Service-wise document requirements setup
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Document System Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #0d6efd, #6610f2); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .migration-box { max-width: 800px; background: white; border-radius: 15px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        pre { background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 12px; }
    </style>
</head>
<body>
    <div class='migration-box'>
        <h2 class='text-center mb-4'><i class='bi bi-file-earmark-check'></i> Document Requirements Setup</h2>
        <p class='lead'>This will configure service-wise document requirements:</p>
        <ul>
            <li>PAN Card - Photo, Signature, Aadhar Copy</li>
            <li>School Admission - Marksheets, TC, Photo</li>
            <li>Voter ID - Address Proof, Age Proof, Photo</li>
            <li>Passport Seva - Birth Certificate, Marksheets, Photo</li>
            <li>Driving License - Learner License, Medical Cert, Proofs</li>
        </ul>
        <hr>
";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='alert alert-info'><strong>Connected to database successfully!</strong></div>";
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . '/database/document_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: {$sqlFile}");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        try {
            if (trim($statement)) {
                $db->exec($statement);
                $executed++;
                
                // Show progress for inserts
                if (stripos($statement, 'INSERT INTO document_requirements') !== false) {
                    preg_match("/VALUES\s*\((\d+)/i", $statement, $matches);
                    if (isset($matches[1])) {
                        echo "<div class='alert alert-success'><strong>✓ Added document requirements for service ID:</strong> {$matches[1]}</div>";
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    echo "<div class='alert alert-success'>
            <h4><i class='bi bi-check-circle'></i> Document System Setup Complete!</h4>
            <p><strong>{$executed}</strong> SQL statements executed successfully.</p>
          </div>";
    
    if (!empty($errors)) {
        echo "<div class='alert alert-warning'>
                <h5>Notes:</h5>
                <ul>
            ";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "  </ul></div>";
    }
    
    echo "<hr>
          <h4>What's New:</h4>
          <ul>
            <li>✅ Created <code>document_requirements</code> table</li>
            <li>✅ Added document fields for 5 services</li>
            <li>✅ Updated <code>service_requests</code> table for multiple documents</li>
            <li>✅ Created organized folder structure: <code>uploads/user_X/service_Y/</code></li>
          </ul>
          
          <h4 class='mt-4'>Next Steps:</h4>
          <ol>
            <li>Test the new apply form: <a href='apply-service-dynamic.php?id=1' target='_blank'>Apply for PAN Card</a></li>
            <li>View uploaded documents in admin panel</li>
            <li>Customize document requirements per service</li>
          </ol>
          
          <div class='alert alert-info mt-3'>
            <strong>💡 How it works:</strong><br>
            Each service now has specific document requirements. When a user applies:<br>
            1. They see exactly which documents are needed<br>
            2. Files are validated (type, size)<br>
            3. Documents are stored in organized folders<br>
            4. Everything is tracked in database
          </div>
          
          <a href='index.php' class='btn btn-primary mt-3'>Go to Dashboard</a>
          <a href='apply-service-dynamic.php?id=1' class='btn btn-success mt-3'>Test Apply Form</a>
          ";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4><i class='bi bi-x-circle'></i> Setup Failed</h4>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
          </div>
          <p>Please check:</p>
          <ul>
            <li>Database connection is working</li>
            <li>SQL file exists at: <code>database/document_system.sql</code></li>
            <li>Database user has ALTER TABLE permissions</li>
          </ul>
          <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";

?>
