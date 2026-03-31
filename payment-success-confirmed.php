<?php
/**
 * Payment Success Confirmation Page
 * Sans Digital Work - SDW
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();
$requestId = (int)($_GET['request_id'] ?? 0);

// Get request details
$stmt = $db->prepare("
    SELECT sr.*, s.service_name 
    FROM service_requests sr
    JOIN servicesand s ON sr.service_id = s.id
    WHERE sr.id = ? AND sr.user_id = ? AND sr.payment_status = 'success'
");
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch();

if (!$request) {
    die('Invalid or unpaid request');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .success-container { max-width: 600px; margin: 60px auto; text-align: center; }
        .success-icon { color: #28a745; font-size: 5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <i class="bi bi-check-circle-fill success-icon"></i>
            
            <h1 class="mt-4 mb-3">Payment Successful!</h1>
            
            <p class="lead text-muted mb-4">
                Your application has been submitted successfully.
            </p>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row text-start">
                        <div class="col-6">
                            <strong>Application ID:</strong><br>
                            <strong>Service:</strong><br>
                            <strong>Amount Paid:</strong><br>
                            <strong>Status:</strong>
                        </div>
                        <div class="col-6 text-end">
                            #<?php echo $request['id']; ?><br>
                            <?php echo sanitizeOutput($request['service_name']); ?><br>
                            ₹<?php echo number_format($request['fees'] + $request['registration_fees'], 2); ?><br>
                            <span class="badge bg-warning">Pending Review</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                Our admin team will review your application shortly. You can track the status in your dashboard.
            </div>
            
            <div class="d-grid gap-2">
                <a href="my-applications.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-view-list"></i> View My Applications
                </a>
                <a href="apply-service.php" class="btn btn-outline-secondary">
                    Apply for Another Service
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
