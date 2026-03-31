<?php
/**
 * Quick Database & Admin Account Checker
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Database & Admin Account Checker</h1>";
echo "<hr>";

// Test 1: Database Connection
echo "<h2>1️⃣ Database Connection</h2>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ <strong style='color: green;'>Database connection SUCCESSFUL!</strong><br>";
    echo "Database: <code>sans_digital_db</code><br>";
} catch (Exception $e) {
    echo "❌ <strong style='color: red;'>Database connection FAILED!</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>Fix:</strong> Make sure XAMPP MySQL is running and database exists.";
    exit;
}

// Test 2: Admins Table Exists
echo "<h2>2️⃣ Admins Table Check</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() > 0) {
        echo "✅ <strong style='color: green;'>Table 'admins' EXISTS!</strong><br>";
    } else {
        echo "❌ <strong style='color: red;'>Table 'admins' NOT FOUND!</strong><br>";
        echo "<strong>Fix:</strong> Run database/schema.sql in phpMyAdmin";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Check Admin Accounts
echo "<h2>3️⃣ Admin Accounts in Database</h2>";
try {
    $stmt = $db->query("SELECT id, username, full_name, role, is_active, last_login FROM admins ORDER BY id");
    $admins = $stmt->fetchAll();
    
    if (count($admins) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #667eea; color: white;'>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Is Active</th>
                <th>Last Login</th>
              </tr>";
        
        foreach ($admins as $admin) {
            $isActive = $admin['is_active'] ? '✅ Yes' : '❌ No';
            $lastLogin = $admin['last_login'] ? date('d M Y, h:i A', strtotime($admin['last_login'])) : 'Never';
            
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td><strong>{$admin['username']}</strong></td>";
            echo "<td>{$admin['full_name']}</td>";
            echo "<td>{$admin['role']}</td>";
            echo "<td>{$isActive}</td>";
            echo "<td>{$lastLogin}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>✅ <strong style='color: green;'>" . count($admins) . " admin account(s) found!</strong></p>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px;'>";
        echo "<h3>🔐 LOGIN CREDENTIALS:</h3>";
        echo "<ul>";
        echo "<li><strong>superadmin</strong> / Password: <code>admin123</code></li>";
        echo "<li><strong>admin1</strong> / Password: <code>admin123</code></li>";
        echo "<li><strong>operator1</strong> / Password: <code>operator123</code></li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "❌ <strong style='color: red;'>NO ADMIN ACCOUNTS FOUND!</strong><br>";
        echo "<strong>Fix:</strong> Run database/setup-staff-accounts.sql in phpMyAdmin";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Test Password Verification
echo "<h2>4️⃣ Password Hash Check</h2>";
try {
    $stmt = $db->query("SELECT username, password_hash FROM admins LIMIT 1");
    $testAdmin = $stmt->fetch();
    
    if ($testAdmin) {
        $hash = $testAdmin['password_hash'];
        $startsWithDollar = strpos($hash, '$2y$10$') === 0;
        
        if ($startsWithDollar) {
            echo "✅ <strong style='color: green;'>Password is PROPERLY HASHED (bcrypt)</strong><br>";
            echo "Hash starts with: <code>" . substr($hash, 0, 20) . "...</code><br>";
            
            // Test if hash works
            $testPassword = 'admin123';
            if (password_verify($testPassword, $hash)) {
                echo "✅ <strong style='color: green;'>Password verification WORKING!</strong><br>";
                echo "Testing with password 'admin123' → SUCCESS<br>";
            } else {
                echo "⚠️ <strong style='color: orange;'>Password verification test failed</strong><br>";
                echo "Expected password might be different<br>";
            }
        } else {
            echo "❌ <strong style='color: red;'>Password is NOT hashed!</strong><br>";
            echo "Found: <code>" . htmlspecialchars($hash) . "</code><br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Session Check
echo "<h2>5️⃣ PHP Session Status</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Sessions are working<br>";
} else {
    echo "⚠️ Sessions may not be started properly<br>";
}

echo "<hr>";
echo "<h2>✅ SUMMARY</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 8px;'>";
echo "<strong>All critical checks completed!</strong><br>";
echo "<br>";
echo "If you see ✅ above, your setup is CORRECT.<br>";
echo "Try logging in with credentials shown above.<br>";
echo "</div>";

echo "<br><br>";
echo "<a href='admin/test-login.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Test Login Page →</a>";
echo " ";
echo "<a href='admin/login.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Normal Login →</a>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
        background: #f5f6fa;
    }
    h1 { color: #667eea; }
    h2 { 
        color: #2d3436; 
        border-bottom: 2px solid #667eea;
        padding-bottom: 10px;
        margin-top: 30px;
    }
    table {
        width: 100%;
        margin: 15px 0;
        background: white;
    }
    th {
        background: #667eea;
        color: white;
    }
    code {
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
    }
</style>
