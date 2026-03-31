<?php
/**
 * Database Connection Test File
 * Sans Digital Work - SDW
 * Run this to verify your database connection is working
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";
echo "<hr>";

// Test 1: Check if MySQL extension is loaded
echo "<h3>Test 1: PDO Extension</h3>";
if (extension_loaded('pdo_mysql')) {
    echo "✓ PDO MySQL extension is loaded<br>";
} else {
    echo "✗ PDO MySQL extension NOT loaded<br>";
    die("Please enable pdo_mysql in php.ini");
}

// Test 2: Database Connection
echo "<h3>Test 2: Database Connection</h3>";
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sans_digital_db');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "✓ Database connection successful!<br>";
    echo "✓ Connected to database: <strong>" . DB_NAME . "</strong><br>";
    
} catch(PDOException $e) {
    echo "✗ Database connection FAILED!<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>Troubleshooting:</strong><br>";
    echo "1. Check if MySQL is running in XAMPP<br>";
    echo "2. Verify database 'sans_digital_db' exists in phpMyAdmin<br>";
    echo "3. Check credentials in config/database.php<br>";
    die();
}

// Test 3: Check Tables
echo "<h3>Test 3: Checking Tables</h3>";
$tables = ['users', 'servicesand', 'service_requests', 'admins', 'admin_activity_log'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ Table '$table' exists with $count records<br>";
    } catch (PDOException $e) {
        echo "✗ Table '$table' NOT found or error occurred<br>";
    }
}

// Test 4: Check Admin Users
echo "<h3>Test 4: Admin Users</h3>";
try {
    $stmt = $pdo->query("SELECT username, full_name, role FROM admins");
    $admins = $stmt->fetchAll();
    echo "Found " . count($admins) . " admin users:<br>";
    foreach ($admins as $admin) {
        echo "- Username: <strong>{$admin['username']}</strong> | Name: {$admin['full_name']} | Role: {$admin['role']}<br>";
    }
} catch (PDOException $e) {
    echo "✗ Error checking admins: " . $e->getMessage() . "<br>";
}

// Test 5: Check Services
echo "<h3>Test 5: Available Services</h3>";
try {
    $stmt = $pdo->query("SELECT service_name, fees, registration_fees FROM servicesand WHERE status='active'");
    $services = $stmt->fetchAll();
    echo "Found " . count($services) . " active services:<br>";
    foreach ($services as $service) {
        $total = $service['fees'] + $service['registration_fees'];
        echo "- {$service['service_name']}: ₹" . number_format($total, 2) . " (₹{$service['fees']} + ₹{$service['registration_fees']} reg)<br>";
    }
} catch (PDOException $e) {
    echo "✗ Error checking services: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>✓ All Tests Completed!</h3>";
echo "<p>Your database connection is working correctly.</p>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='admin/login.php'>Go to Admin Panel</a></p>";
?>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h2 { color: #28a745; }
    h3 { color: #007bff; margin-top: 20px; }
    .check { color: #28a745; }
    .error { color: #dc3545; }
</style>
