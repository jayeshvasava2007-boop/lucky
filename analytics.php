<?php
/**
 * Admin Analytics Dashboard
 * SDW SaaS - Charts and Statistics
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

setupSecureSession();
session_start();
requireAdmin();

$db = Database::getInstance()->getConnection();
$adminId = getCurrentAdminId();

// Get statistics for current month
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

// Overall Statistics
$stats = $db->query("
    SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN payment_status = 'success' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN application_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN application_status = 'Processing' THEN 1 ELSE 0 END) as processing_count,
        SUM(CASE WHEN application_status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN payment_status = 'success' THEN (s.fees + s.registration_fees) ELSE 0 END) as total_revenue,
        COALESCE(SUM(sr.commission_deducted), 0) as total_commission_paid
    FROM service_requests sr
    JOIN servicesand s ON sr.service_id = s.id
")->fetch();

// Daily data for last 30 days (for charts)
$dailyData = $db->query("
    SELECT 
        DATE(sr.created_at) as date,
        COUNT(*) as applications,
        SUM(CASE WHEN sr.payment_status = 'success' THEN (s.fees + s.registration_fees) ELSE 0 END) as revenue
    FROM service_requests sr
    JOIN servicesand s ON sr.service_id = s.id
    WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(sr.created_at)
    ORDER BY date ASC
")->fetchAll();

// Service-wise statistics
$serviceStats = $db->query("
    SELECT 
        s.service_name,
        COUNT(sr.id) as total_applications,
        SUM(CASE WHEN sr.payment_status = 'success' THEN 1 ELSE 0 END) as paid_applications,
        SUM(s.fees + s.registration_fees) as total_revenue
    FROM servicesand s
    LEFT JOIN service_requests sr ON s.id = sr.service_id
    GROUP BY s.id, s.service_name
    ORDER BY total_applications DESC
    LIMIT 10
")->fetchAll();

// Recent transactions (if wallet system exists)
try {
    $recentTransactions = $db->query("
        SELECT t.*, w.user_type, w.user_id
        FROM transactions t
        JOIN wallets w ON t.wallet_id = w.id
        ORDER BY t.created_at DESC
        LIMIT 20
    ")->fetchAll();
} catch (Exception $e) {
    $recentTransactions = [];
}

// Monthly comparison
$monthlyComparison = $db->query("
    SELECT 
        DATE_FORMAT(sr.created_at, '%Y-%m') as month,
        COUNT(*) as applications,
        SUM(CASE WHEN sr.payment_status = 'success' THEN (s.fees + s.registration_fees) ELSE 0 END) as revenue
    FROM service_requests sr
    JOIN servicesand s ON sr.service_id = s.id
    WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(sr.created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .stat-card { border-radius: 12px; color: white; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0a58ca); }
        .bg-gradient-success { background: linear-gradient(45deg, #198754, #146c43); }
        .bg-gradient-warning { background: linear-gradient(45deg, #ffc107, #ffb300); }
        .bg-gradient-info { background: linear-gradient(45deg, #0dcaf0, #0aa2c0); }
        .chart-container { position: relative; height: 300px; margin-bottom: 30px; }
        .navbar { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand fw-bold" href="index.php">
            <img src="log.png" style="height:35px; margin-right:10px;">
            <?php echo SITE_NAME; ?> Analytics
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="text-white me-3">
                👨‍💼 Lucky
            </span>
            <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-graph-up"></i> Analytics Dashboard</h2>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card bg-gradient-primary">
                    <h3><i class="bi bi-file-earmark-text"></i> <?php echo number_format($stats['total_applications']); ?></h3>
                    <p>Total Applications</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-success">
                    <h3><i class="bi bi-currency-rupee"></i> ₹<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-warning">
                    <h3><i class="bi bi-clock-history"></i> <?php echo number_format($stats['pending_count']); ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-gradient-info">
                    <h3><i class="bi bi-check-circle"></i> <?php echo number_format($stats['completed_count']); ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card p-3">
                    <h5><i class="bi bi-graph-up"></i> Daily Applications (Last 30 Days)</h5>
                    <div class="chart-container">
                        <canvas id="dailyApplicationsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <h5><i class="bi bi-pie-chart"></i> Application Status</h5>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card p-3">
                    <h5><i class="bi bi-bar-chart"></i> Top Services by Revenue</h5>
                    <div class="chart-container">
                        <canvas id="serviceRevenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3">
                    <h5><i class="bi bi-graph-up-arrow"></i> Monthly Revenue Trend</h5>
                    <div class="chart-container">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service-wise Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card p-4">
                    <h5><i class="bi bi-table"></i> Service-wise Performance</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Total Applications</th>
                                <th>Paid Applications</th>
                                <th>Total Revenue</th>
                                <th>Avg Order Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serviceStats as $service): ?>
                            <tr>
                                <td><?php echo sanitizeOutput($service['service_name']); ?></td>
                                <td><?php echo number_format($service['total_applications']); ?></td>
                                <td><?php echo number_format($service['paid_applications']); ?></td>
                                <td>₹<?php echo number_format($service['total_revenue'], 2); ?></td>
                                <td>₹<?php echo $service['total_applications'] > 0 ? number_format($service['total_revenue'] / $service['total_applications'], 2) : '0.00'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for charts
        const dailyData = <?php echo json_encode($dailyData); ?>;
        const serviceStats = <?php echo json_encode($serviceStats); ?>;
        const monthlyData = <?php echo json_encode($monthlyComparison); ?>;

        // Daily Applications Chart
        new Chart(document.getElementById('dailyApplicationsChart'), {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date.substring(5)),
                datasets: [{
                    label: 'Applications',
                    data: dailyData.map(d => d.applications),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true } }
            }
        });

        // Status Pie Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Processing', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $stats['pending_count']; ?>,
                        <?php echo $stats['processing_count']; ?>,
                        <?php echo $stats['completed_count']; ?>
                    ],
                    backgroundColor: ['#ffc107', '#0dcaf0', '#198754']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Service Revenue Bar Chart
        new Chart(document.getElementById('serviceRevenueChart'), {
            type: 'bar',
            data: {
                labels: serviceStats.map(s => s.service_name.substring(0, 15) + '...'),
                datasets: [{
                    label: 'Revenue (₹)',
                    data: serviceStats.map(s => s.total_revenue),
                    backgroundColor: '#198754'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        // Monthly Revenue Line Chart
        new Chart(document.getElementById('monthlyRevenueChart'), {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [{
                    label: 'Monthly Revenue (₹)',
                    data: monthlyData.map(d => d.revenue),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true } }
            }
        });
    </script>
</body>
</html>
