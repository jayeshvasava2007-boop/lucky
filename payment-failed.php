<?php
/**
 * Payment Failed Page
 * Sans Digital Work - SDW
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();

$error = $_GET['error'] ?? 'unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .failed-container { max-width: 600px; margin: 60px auto; text-align: center; }
        .failed-icon { color: #dc3545; font-size: 5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="failed-container">
            <i class="bi bi-x-circle-fill failed-icon"></i>
            
            <h1 class="mt-4 mb-3">Payment Failed</h1>
            
            <div class="alert alert-danger">
                <?php
                switch($error) {
                    case 'signature_mismatch':
                        echo 'Payment verification failed. Please contact support.';
                        break;
                    case 'processing_error':
                        echo 'An error occurred while processing your payment.';
                        break;
                    default:
                        echo 'Your payment could not be completed. Please try again.';
                }
                ?>
            </div>
            
            <p class="text-muted mb-4">
                Don't worry, your money has not been deducted. Please try again.
            </p>
            
            <div class="d-grid gap-2 d-inline-block">
                <a href="javascript:history.back()" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-left"></i> Go Back and Retry
                </a>
                <a href="my-applications.php" class="btn btn-outline-secondary">
                    View My Applications
                </a>
            </div>
            
            <div class="mt-4">
                <small class="text-muted">
                    Need help? Contact us at <?php echo ADMIN_EMAIL; ?>
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
