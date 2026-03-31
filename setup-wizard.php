<?php
/**
 * PROFESSIONAL DOCUMENT MANAGEMENT SYSTEM - SETUP WIZARD
 * SDW SaaS - Enterprise-Grade Installation
 */

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Document Management System Setup';
$step = $_GET['step'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SDW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .wizard-container {
            max-width: 900px;
            width: 100%;
            margin: 20px;
        }
        .wizard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .wizard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .wizard-body {
            padding: 40px;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 0 10px;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .step.active .step-circle {
            background: #667eea;
            transform: scale(1.1);
        }
        .step.completed .step-circle {
            background: #28a745;
        }
        .step-label {
            font-size: 14px;
            color: #666;
        }
        .step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .feature-list li:before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .status-box {
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .status-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .btn-wizard {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-wizard:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-card">
            <div class="wizard-header">
                <h2><i class="bi bi-file-earmark-text"></i> Document Management System Setup</h2>
                <p class="mb-0">Professional installation wizard for SDW SaaS</p>
            </div>
            
            <div class="wizard-body">
                <!-- Progress Steps -->
                <div class="progress-steps">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">
                        <div class="step-circle">1</div>
                        <div class="step-label">Welcome</div>
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                        <div class="step-circle">2</div>
                        <div class="step-label">Configuration</div>
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                        <div class="step-circle">3</div>
                        <div class="step-label">Installation</div>
                    </div>
                    <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                        <div class="step-circle">4</div>
                        <div class="step-label">Complete</div>
                    </div>
                </div>

                <?php
                try {
                    $db = Database::getInstance()->getConnection();
                    
                    switch($step) {
                        case 1: // Welcome
                            include 'setup-welcome.php';
                            break;
                        case 2: // Configuration
                            include 'setup-config.php';
                            break;
                        case 3: // Installation
                            include 'setup-install.php';
                            break;
                        case 4: // Complete
                            include 'setup-complete.php';
                            break;
                        default:
                            include 'setup-welcome.php';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">';
                    echo '<h5><i class="bi bi-x-circle-fill"></i> Error</h5>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
