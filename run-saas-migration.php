<?php
/**
 * SaaS Upgrade Migration Runner
 * SDW SaaS - Database Setup Script
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>SaaS Upgrade - Database Migration</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #0d6efd, #6610f2); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .migration-box { max-width: 800px; background: white; border-radius: 15px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .success { color: #198754; }
        .error { color: #dc3545; }
        .info { color: #0dcaf0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class='migration-box'>
        <h2 class='text-center mb-4'><i class='bi bi-database'></i> SaaS Database Upgrade</h2>
        <p class='lead'>This will upgrade your database with new tables for:</p>
        <ul>
            <li>Multi-Admin/Staff System</li>
            <li>Wallet & Commission Management</li>
            <li>Transaction Tracking</li>
            <li>Analytics Views</li>
        </ul>
        <hr>
";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='alert alert-info'><strong>Connected to database successfully!</strong></div>";
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . '/database/saas_upgrade.sql';
    
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
                
                // Show progress for major operations
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?(\w+)/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "<div class='alert alert-success'><strong>✓ Created table:</strong> {$matches[1]}</div>";
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
            <h4><i class='bi bi-check-circle'></i> Migration Completed!</h4>
            <p><strong>{$executed}</strong> SQL statements executed successfully.</p>
          </div>";
    
    if (!empty($errors)) {
        echo "<div class='alert alert-warning'>
                <h5>Warnings/Errors:</h5>
                <ul>
            ";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "  </ul></div>";
    }
    
    echo "<hr>
          <h4>Next Steps:</h4>
          <ol>
            <li>Review the changes above</li>
            <li>Login to admin panel</li>
            <li>Check analytics dashboard at <code>admin/analytics.php</code></li>
            <li>Create sub-admin accounts</li>
            <li>Test wallet system</li>
          </ol>
          <div class='alert alert-info'>
            <strong>Default Super Admin Credentials:</strong><br>
            Username: <code>superadmin</code><br>
            Password: <code>password</code> (Change this immediately!)
          </div>
          <a href='index.php' class='btn btn-primary mt-3'>Go to Dashboard</a>
          <a href='admin/analytics.php' class='btn btn-success mt-3'>View Analytics</a>
          ";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4><i class='bi bi-x-circle'></i> Migration Failed</h4>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
          </div>
          <p>Please check:</p>
          <ul>
            <li>Database connection is working</li>
            <li>SQL file exists at: <code>database/saas_upgrade.sql</code></li>
            <li>Database user has CREATE TABLE permissions</li>
          </ul>
          <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";

?>
