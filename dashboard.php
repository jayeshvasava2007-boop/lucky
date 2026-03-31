<?php
/**
 * User Dashboard
 * Sans Digital Work - SDW
 * Central hub for users to manage services and applications
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

// Get user statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN application_status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN application_status = 'Processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN application_status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN payment_status = 'success' THEN 1 ELSE 0 END) as paid
    FROM service_requests
    WHERE user_id = ?
";
$stmt = $db->prepare($statsQuery);
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// Get recent applications
$recentQuery = "
    SELECT sr.*, s.service_name, s.fees, s.registration_fees
    FROM service_requests sr
    JOIN servicesand s ON sr.service_id = s.id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC
    LIMIT 5
";
$stmt = $db->prepare($recentQuery);
$stmt->execute([$userId]);
$recentApplications = $stmt->fetchAll();

// Get available services
$servicesQuery = "SELECT * FROM servicesand WHERE status = 'active' ORDER BY id";
$services = $db->query($servicesQuery)->fetchAll();

$userName = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .stat-card {
            border-radius: 15px;
            padding: 25px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 3rem;
            opacity: 0.3;
        }
        .bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-gradient-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .bg-gradient-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .bg-gradient-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .service-card {
            border-radius: 12px;
            padding: 25px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        .service-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .service-icon i {
            font-size: 1.8rem;
            color: white;
        }
        .application-card {
            border-radius: 12px;
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .quick-action-btn {
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <img src="log.png" style="height:40px; margin-right:10px;">
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-applications.php"><i class="bi bi-folder-check"></i> My Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary px-4" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">👋 Welcome back, <?php echo sanitizeOutput($userName); ?>!</h1>
                    <p class="mb-0">Manage your services and track applications</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="apply-service.php?id=1" class="btn btn-light btn-lg">
                        <i class="bi bi-plus-circle"></i> New Application
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="stat-card bg-gradient-primary">
                    <h3><?php echo $stats['total_applications']; ?></h3>
                    <p>Total Applications</p>
                    <i class="bi bi-folder stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-warning">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending Review</p>
                    <i class="bi bi-clock-history stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-info">
                    <h3><?php echo $stats['processing']; ?></h3>
                    <p>In Progress</p>
                    <i class="bi bi-gear stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-success">
                    <h3><?php echo $stats['completed']; ?></h3>
                    <p>Completed</p>
                    <i class="bi bi-check-circle stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="fw-bold mb-4"><i class="bi bi-lightning-charge"></i> Quick Actions</h3>
            </div>
            <div class="col-md-4 mb-3">
                <a href="apply-service.php?id=1" class="btn btn-outline-primary w-100 quick-action-btn">
                    <i class="bi bi-file-earmark-text"></i><br>Apply for PAN Card
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="my-applications.php" class="btn btn-outline-primary w-100 quick-action-btn">
                    <i class="bi bi-search"></i><br>Track Application
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="profile.php" class="btn btn-outline-primary w-100 quick-action-btn">
                    <i class="bi bi-person-gear"></i><br>Update Profile
                </a>
            </div>
        </div>

        <!-- Available Services -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="fw-bold mb-4"><i class="bi bi-grid-3x3-gap"></i> Our Services</h3>
            </div>
            <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="bi bi-<?php echo getServiceIcon($service['id']); ?>"></i>
                        </div>
                        <h5 class="fw-bold mb-2"><?php echo sanitizeOutput($service['service_name']); ?></h5>
                        <p class="text-muted small mb-3"><?php echo sanitizeOutput($service['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary">₹<?php echo number_format($service['fees'], 2); ?></span>
                                <small class="text-muted">+ ₹<?php echo number_format($service['registration_fees'], 2); ?> reg</small>
                            </div>
                            <a href="apply-service.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">
                                Apply <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Applications -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0"><i class="bi bi-clock-counterclockwise"></i> Recent Applications</h3>
                    <a href="my-applications.php" class="btn btn-outline-primary">View All</a>
                </div>
                
                <?php if (count($recentApplications) > 0): ?>
                    <?php foreach ($recentApplications as $app): ?>
                        <div class="application-card">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="fw-bold mb-1"><?php echo sanitizeOutput($app['service_name']); ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($app['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <span class="fw-bold">₹<?php echo number_format($app['fees'] + $app['registration_fees'], 2); ?></span>
                                </div>
                                <div class="col-md-2">
                                    <?php
                                    $statusClass = match($app['application_status']) {
                                        'Pending' => 'bg-warning text-dark',
                                        'Processing' => 'bg-info text-white',
                                        'Completed' => 'bg-success text-white',
                                        'Rejected' => 'bg-danger text-white',
                                        default => 'bg-secondary text-white'
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $app['application_status']; ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <a href="my-applications.php" class="btn btn-sm btn-outline-primary">
                                        View Details <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You haven't submitted any applications yet. 
                            <a href="apply-service.php?id=1" class="alert-link">Apply now</a> for our services!
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function getServiceIcon($serviceId) {
    $icons = [
        1 => 'person-badge',      // PAN Card
        2 => 'mortarboard',       // School Admission
        3 => 'person-check',      // Voter ID
        4 => 'car-front',         // Driving License
        5 => 'bank',              // Bank & Financial
        6 => 'briefcase',         // Job Placement
        7 => 'book',              // School/College Admission
        8 => 'person-lines-fill'  // Other
    ];
    return $icons[$serviceId] ?? 'file-earmark';
}
?>
