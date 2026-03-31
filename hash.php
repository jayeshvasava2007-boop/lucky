<?php
/**
 * Simple Password Hash Generator
 * Sans Digital Work - SDW
 */

// Generate hash for admin123
$hash = password_hash("admin123", PASSWORD_DEFAULT);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-top: 0;
            text-align: center;
        }
        .hash-box {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            word-break: break-all;
        }
        .hash-label {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            display: block;
        }
        .hash-value {
            background: #2d3436;
            color: #55efc4;
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
            display: block;
        }
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            display: block;
            margin: 20px auto;
            transition: all 0.3s ease;
        }
        .copy-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .steps {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 10px;
        }
        code {
            background: #2d3436;
            color: #55efc4;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>
        
        <div class="hash-box">
            <span class="hash-label">Password: <code>admin123</code></span>
            <span class="hash-label">Generated Hash (bcrypt):</span>
            <span class="hash-value"><?php echo $hash; ?></span>
        </div>
        
        <button class="copy-btn" onclick="copyHash()">
            📋 Copy Hash
        </button>
        
        <div class="info">
            <strong>ℹ️ Hash Information:</strong>
            <ul style="margin: 10px 0;">
                <li>Algorithm: <strong>bCrypt</strong></li>
                <li>Format: <code>$2y$10$...</code></li>
                <li>Cost: 10 (default)</li>
                <li>Secure: ✅ Yes</li>
            </ul>
        </div>
        
        <div class="steps">
            <strong>📝 How to Use This Hash:</strong>
            <ol>
                <li>Click "Copy Hash" button above</li>
                <li>Open phpMyAdmin</li>
                <li>Select database: <code>sans_digital_db</code></li>
                <li>Run this SQL query:
                    <pre style="background: #2d3436; color: #55efc4; padding: 10px; border-radius: 5px; overflow-x: auto;"><code>UPDATE admins 
SET password_hash = '<?php echo $hash; ?>' 
WHERE username = 'superadmin';</code></pre>
                </li>
                <li>Login with username: <code>superadmin</code> and password: <code>admin123</code></li>
            </ol>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="check-database.php" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; margin: 5px;">Check Database →</a>
            <a href="admin/test-login.php" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin: 5px;">Test Login →</a>
            <a href="admin/login.php" style="display: inline-block; padding: 10px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 8px; margin: 5px;">Normal Login →</a>
        </div>
    </div>
    
    <script>
        function copyHash() {
            const hash = "<?php echo $hash; ?>";
            navigator.clipboard.writeText(hash).then(() => {
                alert('✅ Hash copied to clipboard!');
            }).catch(err => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = hash;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('✅ Hash copied to clipboard!');
            });
        }
    </script>
</body>
</html>
