<?php
/**
 * My Applications Page
 * Sans Digital Work - SDW
 * User can view their service request status
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

// Get all applications for this user
$stmt = $db->prepare("
    SELECT 
        sr.*,
        s.service_name,
        s.fees,
        s.registration_fees,
        a.full_name AS processed_by
    FROM service_requests sr
    JOIN servicesand s ON sr.service_id = s.id
    LEFT JOIN admins a ON sr.admin_id = a.id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$userId]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .application-card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo SITE_NAME; ?></a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-white">Welcome, <?php echo sanitizeOutput($_SESSION['user_name']); ?></span>
                <a class="nav-link" href="apply-service.php">Apply New</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2 class="mb-4">My Service Requests</h2>
        
        <?php if (count($applications) > 0): ?>
            <div class="row">
                <?php foreach ($applications as $app): ?>
                    <div class="col-md-6">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo sanitizeOutput($app['service_name']); ?></h5>
                                        <small class="text-muted">Applied on: <?php echo date('d M Y', strtotime($app['created_at'])); ?></small>
                                    </div>
                                    <?php
                                    $badgeClass = match($app['application_status']) {
                                        'Pending' => 'bg-warning',
                                        'Processing' => 'bg-info',
                                        'Completed' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> fs-6"><?php echo $app['application_status']; ?></span>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1"><strong>Payment:</strong></p>
                                        <p class="mb-3">
                                            <?php if ($app['payment_status'] === 'success'): ?>
                                                <span class="text-success"><i class="bi bi-check-circle"></i> Paid</span>
                                            <?php else: ?>
                                                <span class="text-danger"><i class="bi bi-x-circle"></i> Unpaid</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1"><strong>Amount:</strong></p>
                                        <p class="mb-3">₹<?php echo number_format($app['fees'] + $app['registration_fees'], 2); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($app['remarks']): ?>
                                    <div class="alert alert-light border">
                                        <strong>Admin Remarks:</strong>
                                        <p class="mb-0"><?php echo sanitizeOutput($app['remarks']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($app['processed_by']): ?>
                                    <small class="text-muted">
                                        Processed by: <?php echo sanitizeOutput($app['processed_by']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                <h4 class="mt-3">No Applications Yet</h4>
                <p class="text-muted">You haven't applied for any services yet.</p>
                <a href="apply-service.php" class="btn btn-primary">Apply for a Service</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
