<?php
/**
 * Service Fields Migration Runner
 * SDW SaaS - Dynamic form fields setup
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Service Fields Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #0d6efd, #6610f2); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .migration-box { max-width: 800px; background: white; border-radius: 15px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    </style>
</head>
<body>
    <div class='migration-box'>
        <h2 class='text-center mb-4'><i class='bi bi-ui-checks'></i> Dynamic Form Fields Setup</h2>
        <p class='lead'>This will create custom form fields for services:</p>
        <ul>
            <li>PAN Card - Father's Name, Mother's Name, Marital Status, etc.</li>
            <li>School Admission - Student details, Class applying for, Blood group</li>
            <li>Voter ID - Personal details, Relation type, Age</li>
        </ul>
        <hr>
";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='alert alert-info'><strong>Connected to database successfully!</strong></div>";
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . '/database/service_fields.sql';
    
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
                if (stripos($statement, 'INSERT INTO service_fields') !== false) {
                    preg_match("/VALUES\s*\((\d+)/i", $statement, $matches);
                    if (isset($matches[1])) {
                        echo "<div class='alert alert-success'><strong>✓ Added dynamic fields for service ID:</strong> {$matches[1]}</div>";
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
            <h4><i class='bi bi-check-circle'></i> Service Fields Setup Complete!</h4>
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
            <li>✅ Created <code>service_fields</code> table for dynamic form fields</li>
            <li>✅ Added sample fields for PAN Card, School Admission, Voter ID</li>
            <li>✅ Updated <code>service_requests</code> with <code>dynamic_fields</code> JSON column</li>
            <li>✅ Created summary view for easy management</li>
          </ul>
          
          <h4 class='mt-4'>Field Types Available:</h4>
          <div class='row'>
            <div class='col-md-6'>
                <ul>
                    <li>📝 Text input</li>
                    <li>📄 Textarea</li>
                    <li>📅 Date picker</li>
                    <li>✉️ Email</li>
                    <li>🔢 Number</li>
                </ul>
            </div>
            <div class='col-md-6'>
                <ul>
                    <li>📋 Dropdown select</li>
                    <li>🔘 Radio buttons</li>
                    <li>☑️ Checkbox</li>
                </ul>
            </div>
          </div>
          
          <div class='alert alert-info mt-3'>
            <strong>💡 How it works:</strong><br>
            Each service can have custom form fields that appear dynamically:<br>
            • PAN Card shows: Father's name, Mother's name, Marital status<br>
            • School shows: Student details, Class, Blood group<br>
            • Voter ID shows: Personal info, Relation type, Age
          </div>
          
          <a href='index.php' class='btn btn-primary mt-3'>Go to Dashboard</a>
          <a href='apply-service-dynamic.php?id=1' class='btn btn-success mt-3'>Test PAN Card Form</a>
          ";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4><i class='bi bi-x-circle'></i> Setup Failed</h4>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
          </div>
          <p>Please check:</p>
          <ul>
            <li>Database connection is working</li>
            <li>SQL file exists at: <code>database/service_fields.sql</code></li>
            <li>Database user has ALTER TABLE permissions</li>
          </ul>
          <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";

?>
