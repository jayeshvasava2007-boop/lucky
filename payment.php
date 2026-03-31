<?php
/**
 * Payment Page
 * Sans Digital Work - SDW
 * Razorpay Payment Integration
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
    SELECT sr.*, s.service_name, s.fees, s.registration_fees 
    FROM service_requests sr
    JOIN servicesand s ON sr.service_id = s.id
    WHERE sr.id = ? AND sr.user_id = ?
");
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch();

if (!$request) {
    die('Invalid request');
}

$totalAmount = $request['fees'] + $request['registration_fees'];
$amountInPaise = $totalAmount * 100; // Razorpay expects amount in paise

// Generate order ID
$orderId = 'ORDER_' . time() . '_' . $requestId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .payment-container { max-width: 600px; margin: 40px auto; }
        .card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .order-summary { background-color: #f8f9fa; padding: 20px; border-radius: 8px; }
        .amount-display { font-size: 2rem; color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo SITE_NAME; ?></a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-white">Secure Payment</span>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="payment-container">
            <h2 class="mb-4">Complete Your Payment</h2>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Order Summary</h4>
                    
                    <div class="order-summary mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Service:</strong></p>
                                <p><strong>Registration Fee:</strong></p>
                                <p><strong>Service Fee:</strong></p>
                                <hr>
                                <p class="fw-bold">Total Amount:</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p><?php echo sanitizeOutput($request['service_name']); ?></p>
                                <p>₹<?php echo number_format($request['registration_fees'], 2); ?></p>
                                <p>₹<?php echo number_format($request['fees'], 2); ?></p>
                                <hr>
                                <p class="amount-display">₹<?php echo number_format($totalAmount, 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success mt-3">
                        <i class="bi bi-shield-check"></i> This is a secure SSL encrypted payment
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body text-center p-5">
                    <h5 class="mb-4">Click below to pay securely via Razorpay</h5>
                    
                    <button id="rzp-button" class="btn btn-success btn-lg px-5">
                        Pay ₹<?php echo number_format($totalAmount, 2); ?>
                    </button>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            Supported: Credit Card, Debit Card, Net Banking, UPI, Wallets
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="my-applications.php" class="text-muted">Cancel and go back</a>
            </div>
        </div>
    </div>
    
    <script>
    var options = {
        "key": "<?php echo RAZORPAY_KEY_ID; ?>",
        "amount": "<?php echo $amountInPaise; ?>",
        "currency": "INR",
        "name": "<?php echo SITE_NAME; ?>",
        "description": "<?php echo sanitizeOutput($request['service_name']); ?>",
        "order_id": "<?php echo $orderId; ?>",
        "handler": function (response){
            // Payment successful
            window.location.href = 'payment-success.php?razorpay_payment_id=' + response.razorpay_payment_id + 
                                   '&razorpay_order_id=' + response.razorpay_order_id + 
                                   '&razorpay_signature=' + response.razorpay_signature +
                                   '&request_id=<?php echo $requestId; ?>';
        },
        "prefill": {
            "name": "<?php echo sanitizeOutput($_SESSION['user_name']); ?>",
            "email": "<?php echo sanitizeOutput($_SESSION['user_email']); ?>",
            "contact": ""
        },
        "theme": {
            "color": "#28a745"
        },
        "modal": {
            "ondismiss": function(){
                console.log("Payment closed by user");
            }
        }
    };
    
    var rzp1 = new Razorpay(options);
    document.getElementById('rzp-button').onclick = function(e){
        e.preventDefault();
        rzp1.open();
    };
    </script>
</body>
</html>
