<?php
/**
 * Configuration Check & Debug Helper
 * Sans Digital Work - SDW
 * Use this to verify all configurations are correct
 */

// Enable all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Check - SDW</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        h1 { color: #667eea; border-bottom: 3px solid #28a745; padding-bottom: 10px; }
        h2 { color: #007bff; margin-top: 30px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; color: #333; }
        .check-item { margin: 15px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #28a745; border-radius: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Sans Digital Work - Configuration Check</h1>
        <p>Use this page to verify all system configurations are correct.</p>
        
        <?php
        // PHP Version Check
        echo "<h2>1. PHP Environment</h2>";
        $phpVersion = phpversion();
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            echo "<div class='check-item'>✓ PHP Version: <span class='success'>$phpVersion</span> (Good!)</div>";
        } else {
            echo "<div class='check-item'>✗ PHP Version: <span class='error'>$phpVersion</span> (Need 7.4+)</div>";
        }
        
        // Required Extensions
        echo "<h2>2. Required Extensions</h2>";
        $extensions = ['pdo', 'pdo_mysql', 'openssl', 'json', 'fileinfo'];
        echo "<table><tr><th>Extension</th><th>Status</th></tr>";
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $status = $loaded ? "<span class='success'>✓ Loaded</span>" : "<span class='error'>✗ Not Loaded</span>";
            echo "<tr><td>$ext</td><td>$status</td></tr>";
        }
        echo "</table>";
        
        // Upload Configuration
        echo "<h2>3. PHP Upload Settings</h2>";
        $uploadSettings = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads')
        ];
        echo "<table><tr><th>Setting</th><th>Value</th></tr>";
        foreach ($uploadSettings as $key => $value) {
            echo "<tr><td>$key</td><td>$value</td></tr>";
        }
        echo "</table>";
        
        // Directory Checks
        echo "<h2>4. Directory Structure</h2>";
        $directories = [
            'Uploads Folder' => __DIR__ . '/uploads/aadhar',
            'Config Folder' => __DIR__ . '/config',
            'Includes Folder' => __DIR__ . '/includes',
            'Admin Folder' => __DIR__ . '/admin'
        ];
        foreach ($directories as $name => $path) {
            $exists = is_dir($path);
            $writable = is_writable($path);
            $status = $exists ? ($writable ? "<span class='success'>✓ Exists & Writable</span>" : "<span class='warning'>⚠ Exists but Not Writable</span>") : "<span class='error'>✗ Not Found</span>";
            echo "<div class='check-item'>$name: $status</div>";
        }
        
        // File Checks
        echo "<h2>5. Critical Files</h2>";
        $files = [
            'Database Config' => __DIR__ . '/config/database.php',
            'Schema File' => __DIR__ . '/database/schema.sql',
            '.htaccess' => __DIR__ . '/.htaccess'
        ];
        foreach ($files as $name => $path) {
            $exists = file_exists($path);
            $readable = is_readable($path);
            $status = $exists ? ($readable ? "<span class='success'>✓ Exists & Readable</span>" : "<span class='warning'>⚠ Exists but Not Readable</span>") : "<span class='error'>✗ Not Found</span>";
            echo "<div class='check-item'>$name: $status</div>";
        }
        
        // Database Connection Test
        echo "<h2>6. Database Connection</h2>";
        try {
            require_once __DIR__ . '/config/database.php';
            $db = Database::getInstance()->getConnection();
            echo "<div class='check-item'>✓ Database Connection: <span class='success'>Successful</span></div>";
            
            // Check tables
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<div class='check-item'>✓ Tables Found: <span class='success'>" . count($tables) . "</span></div>";
            echo "<div style='margin-left: 20px;'>";
            foreach ($tables as $table) {
                echo "• $table<br>";
            }
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='check-item'>✗ Database Connection: <span class='error'>Failed</span><br>";
            echo "Error: " . $e->getMessage() . "</div>";
        }
        
        // Security Check
        echo "<h2>7. Security Configuration</h2>";
        $securityChecks = [
            'OpenSSL Available' => function_exists('openssl_encrypt'),
            'Password Hashing' => function_exists('password_hash'),
            'Session Started' => session_status() === PHP_SESSION_ACTIVE
        ];
        foreach ($securityChecks as $check => $result) {
            $status = $result ? "<span class='success'>✓ Available</span>" : "<span class='error'>✗ Not Available</span>";
            echo "<div class='check-item'>$check: $status</div>";
        }
        
        ?>
        
        <hr style="margin: 30px 0;">
        <h2>✅ Quick Links</h2>
        <a href="index.php" class="btn">🏠 Homepage</a>
        <a href="register.php" class="btn">📝 Register</a>
        <a href="login.php" class="btn">🔐 Login</a>
        <a href="admin/login.php" class="btn">👨‍💼 Admin Panel</a>
        <a href="test-db.php" class="btn">🧪 Database Test</a>
        
        <hr style="margin: 30px 0;">
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; border-radius: 5px;">
            <strong>⚠ Important Note:</strong><br>
            If you see any red (✗) errors above, please fix them before using the application.
            Most common issues:
            <ul>
                <li>Database not created → Import database/schema.sql in phpMyAdmin</li>
                <li>Extensions missing → Enable in php.ini</li>
                <li>Folder permissions → Right-click folder → Properties → Security</li>
            </ul>
        </div>
    </div>
</body>
</html>
