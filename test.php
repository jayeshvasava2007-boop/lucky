<?php
/**
 * Password Hash Generator & Tester
 * Sans Digital Work - SDW
 */

echo "<h1>🔐 Password Hash Generator</h1>";
echo "<hr>";

// Test passwords
$testPasswords = [
    'admin123',
    'operator123',
    'superadmin123'
];

echo "<h2>Generated Password Hashes:</h2>";
foreach ($testPasswords as $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; font-family: monospace;'>";
    echo "<strong>Password:</strong> <code>{$password}</code><br>";
    echo "<strong>Hash:</strong> <code>{$hash}</code><br>";
    echo "</div>";
}

echo "<hr>";

// Verify existing hash from database
echo "<h2>Test Password Verification:</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get first admin from database
    $stmt = $db->query("SELECT username, password_hash FROM admins LIMIT 1");
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<strong>Testing with user:</strong> {$admin['username']}<br>";
        echo "<strong>Hash in DB:</strong> " . htmlspecialchars($admin['password_hash']) . "<br>";
        echo "</div>";
        
        // Test verification
        $testPassword = 'admin123';
        $result = password_verify($testPassword, $admin['password_hash']);
        
        echo "<div style='background: " . ($result ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<strong>Test:</strong> password_verify('{$testPassword}', hash)<br>";
        echo "<strong>Result:</strong> " . ($result ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
        echo "</div>";
        
        // Also test via security.php function
        require_once __DIR__ . '/includes/security.php';
        $result2 = verifyPassword($testPassword, $admin['password_hash']);
        
        echo "<div style='background: " . ($result2 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<strong>Test via verifyPassword():</strong><br>";
        echo "<strong>Result:</strong> " . ($result2 ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px;'>";
        echo "❌ No admin accounts found in database!<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px;'>";
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>📝 How to Use:</h3>";
echo "<ol>";
echo "<li>Copy a hash from above</li>";
echo "<li>Go to phpMyAdmin</li>";
echo "<li>Run: <code>UPDATE admins SET password_hash = 'YOUR_HASH_HERE' WHERE username = 'superadmin';</code></li>";
echo "<li>Login with password: <code>admin123</code></li>";
echo "</ol>";

echo "<br><br>";
echo "<a href='check-database.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Check Database →</a>";
echo " ";
echo "<a href='admin/test-login.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Test Login →</a>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        background: #f5f6fa;
    }
    h1 { color: #667eea; }
    h2 { color: #2d3436; margin-top: 20px; }
    code { 
        background: #2d3436; 
        color: #55efc4; 
        padding: 2px 8px; 
        border-radius: 3px;
        word-break: break-all;
    }
</style>
