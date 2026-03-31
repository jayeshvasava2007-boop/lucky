<?php
/**
 * Landing Page / Homepage
 * Sans Digital Work - SDW
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sans Digital Works (SDW) - Your Trusted Digital Service Partner Across India</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 80px 0;
        }
        .service-card {
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: 100%;
            border: 1px solid #e0e0e0;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(13, 110, 253, 0.2);
            border-color: #0d6efd;
        }
        .trust-badge {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .section-title {
            color: #0d6efd;
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
        }
        .about-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 60px 0;
        }
        .cta-button {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .cta-button:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Sans Digital Works</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-applications.php">My Applications</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white px-4" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white px-4" href="register.php">Register (₹50)</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-3">Welcome to Sans Digital Works (SDW)</h1>
                    <p class="lead mb-4">
                        Your Trusted Digital Service Partner Across India 🇮🇳<br>
                        We provide fast, reliable, and secure assistance for government and professional services.
                    </p>
                    <ul class="list-unstyled mb-4">
                        <li><i class="bi bi-check-circle-fill text-success"></i> PAN Card Services</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i> Aadhaar Services</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i> Banking & GST Work</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i> Job Placement</li>
                        <li><i class="bi bi-check-circle-fill text-success"></i> Admission Support</li>
                    </ul>
                    <p class="mb-4"><i class="bi bi-shield-check"></i> <strong>Trusted by Customers Since 2022</strong></p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="register.php" class="btn btn-light btn-lg cta-button">
                            <i class="bi bi-person-plus"></i> Apply Now
                        </a>
                        <a href="https://wa.me/919327830280" target="_blank" class="btn btn-success btn-lg cta-button">
                            <i class="bi bi-whatsapp"></i> Contact on WhatsApp
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <!-- Company Logo instead of generic icon -->
                    <img src="log.png" alt="Sans Digital Works Logo" class="img-fluid rounded shadow-lg" style="max-width: 450px; border: 4px solid white; box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Trust Badges -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="trust-badge">
                        <i class="bi bi-shield-check text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">100% Secure & Trusted</h5>
                        <p class="text-muted">SSL Encrypted | Since 2022 | 5 Team Members</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="trust-badge">
                        <i class="bi bi-credit-card text-primary" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">All Payment Options</h5>
                        <p class="text-muted">UPI | Cards | Net Banking | Wallets</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="trust-badge">
                        <i class="bi bi-headset text-info" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Expert Support</h5>
                        <p class="text-muted">Mon-Fri, 11 AM - 8 PM<br>+91 93278 30280</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- About Us Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="section-title text-start">🏢 About Sans Digital Works (SDW)</h2>
                    <p class="lead">Sans Digital Works (SDW) is a trusted service provider offering assistance in various digital and documentation services across India.</p>
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-check text-primary fs-2 me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Established On</h6>
                                    <p class="mb-0">14 February 2022</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-badge text-primary fs-2 me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">CEO</h6>
                                    <p class="mb-0">Manmohan Pandey</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people text-primary fs-2 me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Team</h6>
                                    <p class="mb-0">5 Members (2 Female, 3 Male)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock text-primary fs-2 me-3"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold">Working Hours</h6>
                                    <p class="mb-0">Mon-Fri, 11 AM - 8 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-heart-fill"></i> <strong>Our Mission:</strong> Not just to earn money, but to build long-term customer trust by providing transparent and reliable services.
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <!-- Logo Image -->
                    <img src="log.png" alt="Sans Digital Works Logo" class="img-fluid rounded shadow" style="max-width: 400px; border: 3px solid #0d6efd;">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <h2 class="section-title">🛠️ Our Services</h2>
            <p class="text-center mb-5">Professional assistance for government and financial services at transparent prices</p>
            <div class="row">
                <?php
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT * FROM servicesand WHERE status = 'active'");
                $services = $stmt->fetchAll();
                
                foreach ($services as $service):
                    $total = $service['fees'] + $service['registration_fees'];
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card service-card h-100">
                            <div class="card-body text-center">
                                <?php
                                // Icon based on service type
                                $iconClass = 'bi-check-circle';
                                if (strpos($service['service_name'], 'PAN') !== false) $iconClass = 'bi-card-heading';
                                elseif (strpos($service['service_name'], 'Aadhar') !== false) $iconClass = 'bi-person-badge';
                                elseif (strpos($service['service_name'], 'Voter') !== false) $iconClass = 'bi-card-list';
                                elseif (strpos($service['service_name'], 'Driving') !== false) $iconClass = 'bi-car-front';
                                elseif (strpos($service['service_name'], 'Bank') !== false) $iconClass = 'bi-bank';
                                elseif (strpos($service['service_name'], 'Job') !== false) $iconClass = 'bi-briefcase';
                                elseif (strpos($service['service_name'], 'Admission') !== false) $iconClass = 'bi-mortarboard';
                                ?>
                                <i class="bi <?php echo $iconClass; ?> text-primary" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 mb-2"><?php echo sanitizeOutput($service['service_name']); ?></h5>
                                <p class="text-muted small flex-grow-1"><?php echo sanitizeOutput($service['description']); ?></p>
                                <hr>
                                <div class="mb-3">
                                    <small class="text-muted">Service Fee: ₹<?php echo number_format($service['fees'], 2); ?></small><br>
                                    <small class="text-muted">+ Registration: ₹<?php echo number_format($service['registration_fees'], 2); ?></small>
                                </div>
                                <div class="d-grid">
                                    <div class="fw-bold text-primary fs-5 mb-2">Total: ₹<?php echo number_format($total, 2); ?></div>
                                    <?php if (isLoggedIn()): ?>
                                        <a href="apply-service.php" class="btn btn-primary">
                                            <i class="bi bi-arrow-right-circle"></i> Apply Now
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline-primary">
                                            <i class="bi bi-box-arrow-in-right"></i> Login to Apply
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- How It Works -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">📋 Registration & Payment Process</h2>
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">1</div>
                    <h5>Register</h5>
                    <p class="text-muted">Create account with details</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">2</div>
                    <h5>Upload Aadhaar</h5>
                    <p class="text-muted">Front & Back copy</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">3</div>
                    <h5>Pay ₹50</h5>
                    <p class="text-muted">Registration fee (1 year)</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">4</div>
                    <h5>Select Service</h5>
                    <p class="text-muted">Apply for any service</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Important Notice Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Important Notice</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>We provide assistance services</strong> (not a government agency)</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>All services are processed with transparency</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>Fees once paid are NOT refundable</strong></li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <strong>Payment must be completed before service processing</strong></li>
                                <li><i class="bi bi-heart-fill text-primary"></i> <strong>Customer trust is our priority</strong> - "I will not build money, I will build customer trust."</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Sans Digital Works</h5>
                    <p class="text-muted">"I will not build a money, I will build a customer trust."</p>
                    <!-- Logo in footer -->
                    <img src="log.png" alt="Sans Digital Works Logo" class="img-fluid rounded mb-3" style="max-width: 200px; background: white; padding: 10px;">
                    <p class="text-muted small">
                        <strong>CEO:</strong> Manmohan Pandey<br>
                        <strong>Founded:</strong> February 14, 2022<br>
                        <strong>Team:</strong> 5 Members (3 Male, 2 Female)<br>
                        <strong>Service Area:</strong> All India<br>
                        <strong>Working Hours:</strong> Monday to Friday, 11 AM - 8 PM
                    </p>
                    <div class="mt-3">
                        <strong><i class="bi bi-headset"></i> Contact Us:</strong><br>
                        <i class="bi bi-whatsapp"></i> WhatsApp: +91 93278 30280<br>
                        <i class="bi bi-envelope"></i> Email: sansdigitalworks@gmail.com
                    </div>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="login.php" class="text-muted">Login</a></li>
                        <li><a href="register.php" class="text-muted">Register (₹50)</a></li>
                        <li><a href="admin/login.php" class="text-muted">Admin Panel</a></li>
                        <li><a href="#services" class="text-muted">Our Services</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Payment Options</h6>
                    <p class="text-muted small">
                        <i class="bi bi-credit-card"></i> Razorpay<br>
                        <i class="bi bi-wallet2"></i> All UPI Apps<br>
                        <i class="bi bi-bank"></i> Credit/Debit Cards<br>
                        <i class="bi bi-currency-rupee"></i> Net Banking<br>
                        <small class="text-warning">⚠️ Fees are non-refundable</small>
                    </p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center text-muted small">
                &copy; <?php echo date('Y'); ?> Sans Digital Works. All rights reserved. | Secure & Encrypted
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
