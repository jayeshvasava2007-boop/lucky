<?php
/**
 * Alternative Payment Options Page
 * Sans Digital Works - Multiple Payment Methods
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

setupSecureSession();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Options - Sans Digital Works</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .payment-container { max-width: 900px; margin: 40px auto; }
        .payment-card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; transition: transform 0.3s; }
        .payment-card:hover { transform: translateY(-5px); }
        .amount-display { font-size: 2rem; color: #28a745; font-weight: bold; }
        .payment-icon { font-size: 3rem; margin-bottom: 10px; }
        .whatsapp-btn { background-color: #25D366; color: white; }
        .upi-btn { background-color: #007bff; color: white; }
        .cash-btn { background-color: #ffc107; color: #000; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Sans Digital Works</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-white">Secure Payment</span>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="payment-container">
            <h2 class="mb-4 text-center">Choose Your Payment Method</h2>
            
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h4>Order Summary</h4>
                    <p class="text-muted"><?php echo sanitizeOutput($request['service_name']); ?></p>
                    <div class="amount-display">₹<?php echo number_format($totalAmount, 2); ?></div>
                    <small class="text-muted">(₹<?php echo number_format($request['fees'], 2); ?> + ₹<?php echo number_format($request['registration_fees'], 2); ?> registration)</small>
                </div>
            </div>
            
            <div class="row">
                <!-- Option 1: Online Payment (Razorpay) -->
                <div class="col-md-4">
                    <div class="card payment-card text-center p-4">
                        <div class="payment-icon text-primary">
                            <i class="bi bi-credit-card-2-front"></i>
                        </div>
                        <h5>Online Payment</h5>
                        <p class="text-muted small">Pay via UPI, Card, or Net Banking</p>
                        <hr>
                        <ul class="list-unstyled text-start small">
                            <li><i class="bi bi-check-circle text-success"></i> Google Pay / PhonePe</li>
                            <li><i class="bi bi-check-circle text-success"></i> Credit / Debit Card</li>
                            <li><i class="bi bi-check-circle text-success"></i> Net Banking</li>
                            <li><i class="bi bi-check-circle text-success"></i> Instant Confirmation</li>
                        </ul>
                        <?php if (RAZORPAY_KEY_ID !== 'YOUR_TEST_KEY_ID'): ?>
                            <button onclick="payWithRazorpay()" class="btn btn-primary w-100 mt-3">
                                <i class="bi bi-lock-fill"></i> Pay Now
                            </button>
                        <?php else: ?>
                            <button disabled class="btn btn-secondary w-100 mt-3">
                                <i class="bi bi-exclamation-triangle"></i> Not Configured
                            </button>
                            <small class="text-danger mt-2 d-block">Admin needs to add Razorpay keys</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Option 2: WhatsApp Payment -->
                <div class="col-md-4">
                    <div class="card payment-card text-center p-4">
                        <div class="payment-icon text-success">
                            <i class="bi bi-whatsapp"></i>
                        </div>
                        <h5>WhatsApp Payment</h5>
                        <p class="text-muted small">Pay via UPI on WhatsApp</p>
                        <hr>
                        <ul class="list-unstyled text-start small">
                            <li><i class="bi bi-check-circle text-success"></i> Send Payment Request</li>
                            <li><i class="bi bi-check-circle text-success"></i> We'll send UPI ID</li>
                            <li><i class="bi bi-check-circle text-success"></i> Screenshot as Proof</li>
                            <li><i class="bi bi-check-circle text-success"></i> Manual Verification</li>
                        </ul>
                        <a href="https://wa.me/919327830280?text=Hi%2C%20I%20want%20to%20pay%20for%20<?php echo urlencode($request['service_name']); ?>.%20Application%20ID%3A%20<?php echo $requestId; ?>.%20Amount%3A%20Rs.%20<?php echo $totalAmount; ?>" 
                           target="_blank" class="btn whatsapp-btn w-100 mt-3">
                            <i class="bi bi-whatsapp"></i> Pay on WhatsApp
                        </a>
                        <small class="text-muted mt-2 d-block">+91 93278 30280</small>
                    </div>
                </div>
                
                <!-- Option 3: Visit Office -->
                <div class="col-md-4">
                    <div class="card payment-card text-center p-4">
                        <div class="payment-icon text-warning">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h5>Visit Our Office</h5>
                        <p class="text-muted small">Pay Cash/Card at Office</p>
                        <hr>
                        <ul class="list-unstyled text-start small">
                            <li><i class="bi bi-check-circle text-success"></i> Personal Assistance</li>
                            <li><i class="bi bi-check-circle text-success"></i> Cash/Card Accepted</li>
                            <li><i class="bi bi-check-circle text-success"></i> Instant Receipt</li>
                            <li><i class="bi bi-check-circle text-success"></i> Document Verification</li>
                        </ul>
                        <a href="tel:+919327830280" class="btn cash-btn w-100 mt-3">
                            <i class="bi bi-telephone"></i> Call to Visit
                        </a>
                        <small class="text-muted mt-2 d-block">Mon-Fri, 11 AM - 8 PM</small>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <strong><i class="bi bi-info-circle"></i> Important:</strong>
                <ul class="mb-0">
                    <li>All fees are non-refundable</li>
                    <li>Payment must be completed before processing</li>
                    <li>Keep transaction ID/screenshot for reference</li>
                    <li>For any payment issues, contact: +91 93278 30280</li>
                </ul>
            </div>
            
            <div class="text-center mt-4">
                <a href="my-applications.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Applications
                </a>
            </div>
        </div>
    </div>
    
    <?php if (RAZORPAY_KEY_ID !== 'YOUR_TEST_KEY_ID'): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    function payWithRazorpay() {
        var options = {
            "key": "<?php echo RAZORPAY_KEY_ID; ?>",
            "amount": "<?php echo $totalAmount * 100; ?>",
            "currency": "INR",
            "name": "Sans Digital Works",
            "description": "<?php echo sanitizeOutput($request['service_name']); ?>",
            "order_id": "<?php echo 'ORDER_' . time() . '_' . $requestId; ?>",
            "handler": function (response){
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
                "color": "#0d6efd"
            }
        };
        var rzp1 = new Razorpay(options);
        rzp1.open();
    }
    </script>
    <?php endif; ?>
</body>
</html>
