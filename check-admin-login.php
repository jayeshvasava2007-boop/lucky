<?php
/**
 * Admin Login Diagnostic Tool
 * Sans Digital Work - SDW
 * Check if admin login is working correctly
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Admin Login Diagnostic Tool</h1>";
echo "<hr>";

// Test 1: Database Connection
echo "<h2>1️⃣ Database Connection</h2>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ <strong style='color: green;'>Database connection successful!</strong><br>";
} catch (Exception $e) {
    echo "❌ <strong style='color: red;'>Database connection FAILED: " . $e->getMessage() . "</strong><br>";
    die();
}

// Test 2: Admins Table Exists
echo "<h2>2️⃣ Admins Table Check</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() > 0) {
        echo "✅ <strong style='color: green;'>Table 'admins' exists!</strong><br>";
    } else {
        echo "❌ <strong style='color: red;'>Table 'admins' NOT found!</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Check Admin Accounts
echo "<h2>3️⃣ Admin Accounts in Database</h2>";
try {
    $stmt = $db->query("SELECT id, username, full_name, role, is_active, last_login, created_at FROM admins ORDER BY id");
    $admins = $stmt->fetchAll();
    
    if (count($admins) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Is Active</th>
                <th>Last Login</th>
                <th>Password Hash Starts With</th>
              </tr>";
        
        foreach ($admins as $admin) {
            $hashStart = substr($admin['password_hash'], 0, 20) . "...";
            $isActive = $admin['is_active'] ? '✅ Yes' : '❌ No';
            $lastLogin = $admin['last_login'] ? date('d M Y, h:i A', strtotime($admin['last_login'])) : 'Never';
            
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td><strong>{$admin['username']}</strong></td>";
            echo "<td>{$admin['full_name']}</td>";
            echo "<td>{$admin['role']}</td>";
            echo "<td>{$isActive}</td>";
            echo "<td>{$lastLogin}</td>";
            echo "<td style='font-family: monospace;'>{$hashStart}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>✅ <strong style='color: green;'>" . count($admins) . " admin account(s) found!</strong></p>";
    } else {
        echo "❌ <strong style='color: red;'>No admin accounts found in database!</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Password Hash Verification
echo "<h2>4️⃣ Password Hash Format Check</h2>";
try {
    $stmt = $db->query("SELECT username, password_hash FROM admins WHERE username IN ('superadmin', 'admin1', 'operator1')");
    $testAccounts = $stmt->fetchAll();
    
    foreach ($testAccounts as $account) {
        $hash = $account['password_hash'];
        $startsWithDollar = strpos($hash, '$2y$10$') === 0;
        $hashLength = strlen($hash);
        
        if ($startsWithDollar && $hashLength >= 60) {
            echo "✅ <strong style='color: green;'>{$account['username']}</strong>: Password is properly hashed (bcrypt)<br>";
        } else {
            echo "⚠️ <strong style='color: orange;'>{$account['username']}</strong>: Password hash format may be incorrect<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Test Password Verification
echo "<h2>5️⃣ Password Verification Test</h2>";
echo "<form method='POST' style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<label><strong>Test Login:</strong></label><br><br>";
echo "Username: <select name='test_username' style='padding: 8px; margin: 5px;'>";
echo "<option value='superadmin'>superadmin</option>";
echo "<option value='admin1'>admin1</option>";
echo "<option value='operator1'>operator1</option>";
echo "</select><br>";
echo "Password: <input type='password' name='test_password' placeholder='Enter password' style='padding: 8px; margin: 5px;' required><br>";
echo "<button type='submit' name='test_login' style='padding: 10px 20px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;'>Test Login</button>";
echo "</form>";

if (isset($_POST['test_login'])) {
    require_once __DIR__ . '/includes/security.php';
    require_once __DIR__ . '/includes/auth.php';
    
    $testUsername = $_POST['test_username'];
    $testPassword = $_POST['test_password'];
    
    echo "<div style='background: white; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h3>Test Results:</h3>";
    
    // Get admin from database
    try {
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
        $stmt->execute([$testUsername]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            echo "❌ <strong style='color: red;'>Admin '{$testUsername}' not found or inactive!</strong><br>";
        } else {
            echo "✅ Admin found: {$admin['full_name']}<br>";
            echo "📧 Role: {$admin['role']}<br>";
            echo "🔐 Password hash in DB: <code style='background: #f8f9fa; padding: 5px;'>" . substr($admin['password_hash'], 0, 30) . "...</code><br>";
            
            // Test password verification
            $verifyResult = password_verify($testPassword, $admin['password_hash']);
            
            if ($verifyResult) {
                echo "<br>✅✅✅ <strong style='color: green; font-size: 18px;'>PASSWORD VERIFIED SUCCESSFULLY!</strong> ✅✅✅<br>";
                echo "🎉 Login would succeed for this user!<br>";
            } else {
                echo "<br>❌❌❌ <strong style='color: red; font-size: 18px;'>PASSWORD VERIFICATION FAILED!</strong> ❌❌❌<br>";
                echo "⚠️ The password entered does not match the hash in database.<br>";
                echo "💡 Expected passwords:<br>";
                echo "&nbsp;&nbsp;&nbsp;- For 'superadmin' and 'admin1': <code>admin123</code><br>";
                echo "&nbsp;&nbsp;&nbsp;- For 'operator1': <code>operator123</code><br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
}

// Test 6: Code Implementation Check
echo "<h2>6️⃣ Code Implementation Check</h2>";
$authFile = __DIR__ . '/includes/auth.php';
if (file_exists($authFile)) {
    $content = file_get_contents($authFile);
    
    echo "Checking <code>" . realpath($authFile) . "</code>...<br><br>";
    
    // Check for password_verify usage
    if (strpos($content, 'password_verify') !== false || strpos($content, 'verifyPassword') !== false) {
        echo "✅ <strong style='color: green;'>CORRECT:</strong> Code uses <code>password_verify()</code> or <code>verifyPassword()</code><br>";
    } else {
        echo "❌ <strong style='color: red;'>WARNING:</strong> Code does NOT use <code>password_verify()</code>!<br>";
    }
    
    // Check for direct comparison (bad practice)
    if (preg_match('/\$password\s*==\s*\$.*\[.password.*\]/i', $content)) {
        echo "❌ <strong style='color: red;'>DANGEROUS:</strong> Found direct password comparison!<br>";
    } else {
        echo "✅ <strong style='color: green;'>GOOD:</strong> No direct password comparison found<br>";
    }
    
    // Check for prepared statements
    if (strpos($content, 'prepare(') !== false && strpos($content, 'execute(') !== false) {
        echo "✅ <strong style='color: green;'>GOOD:</strong> Uses prepared statements (SQL injection protected)<br>";
    } else {
        echo "⚠️ <strong style='color: orange;'>WARNING:</strong> May not use prepared statements<br>";
    }
} else {
    echo "❌ File not found: {$authFile}<br>";
}

// Test 7: Quick Fix Options
echo "<h2>7️⃣ Quick Fix Options</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 10px; border-left: 4px solid #ffc107;'>";
echo "<h4>🔧 If Login is Not Working:</h4>";
echo "<ol>";
echo "<li><strong>Verify credentials:</strong> Username: <code>superadmin</code>, Password: <code>admin123</code></li>";
echo "<li><strong>Check database:</strong> Run SQL query:<br>";
echo "<code style='display: block; background: #f8f9fa; padding: 10px; margin: 10px 0;'>SELECT * FROM admins WHERE username = 'superadmin';</code></li>";
echo "<li><strong>Reset password:</strong> <a href='fix-admin-login.php' target='_blank' style='color: #0d6efd;'>Click here to run reset script</a></li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; margin-top: 30px;'>";
echo "✅ <strong>All tests completed!</strong><br>";
echo "📋 <a href='admin/login.php' style='color: #0d6efd; font-weight: bold;'>Go to Admin Login Page</a></p>";
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        background: #f5f6fa;
    }
    h1 {
        color: #667eea;
        text-align: center;
    }
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
