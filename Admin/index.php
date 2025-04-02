<?php
include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$admin_check_query = "SELECT isadmin, canadd, candelete, canedit FROM users WHERE id = :id";
$admin_check_stmt = $pdo->prepare($admin_check_query);
$admin_check_stmt->execute(['id' => $user_id]);
$isadmin = $admin_check_stmt->fetchColumn();

if ($isadmin != 1) {
    header("Location: clients.php");
    exit();
}

// Fetch key metrics for the dashboard
try {
    // Total Active Clients
    $active_clients_query = "SELECT COUNT(*) FROM clients WHERE subscription_status = 'active'";
    $active_clients_stmt = $pdo->query($active_clients_query);
    $active_clients_count = $active_clients_stmt->fetchColumn() ?: 0;

    // Total Clients (both active and inactive)
    $total_clients_query = "SELECT COUNT(*) FROM clients";
    $total_clients_stmt = $pdo->query($total_clients_query);
    $total_clients_count = $total_clients_stmt->fetchColumn() ?: 0;

    // Subscriptions ending in the next 7 days
    $ending_soon_query = "SELECT COUNT(*) FROM clients 
                          WHERE subscription_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                          AND subscription_end_date >= CURDATE()
                          AND subscription_status = 'active'";
    $ending_soon_stmt = $pdo->query($ending_soon_query);
    $ending_soon_count = $ending_soon_stmt->fetchColumn() ?: 0;

    // Expired subscriptions
    $expired_query = "SELECT COUNT(*) FROM clients 
                      WHERE (subscription_end_date < CURDATE() OR subscription_status = 'expired')";
    $expired_stmt = $pdo->query($expired_query);
    $expired_count = $expired_stmt->fetchColumn() ?: 0;

    // Pending payments total
    $pending_payments_query = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
                              FROM payments 
                              WHERE payment_status = 'pending'";
    $pending_payments_stmt = $pdo->query($pending_payments_query);
    $pending_payments = $pending_payments_stmt->fetch(PDO::FETCH_ASSOC);
    $pending_payments_count = $pending_payments['count'] ?: 0;
    $pending_payments_total = $pending_payments['total'] ?: 0;

    // Current date
    $current_date = date('Y-m-d');
    $current_month = date('m');
    $current_year = date('Y');
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $previous_month = date('m', strtotime('-1 month'));
    $previous_month_year = date('Y', strtotime('-1 month'));

    // Revenue Data - Last 30 days
    $monthly_revenue_query = "SELECT COALESCE(SUM(amount), 0) as revenue 
                              FROM payments 
                              WHERE payment_status = 'completed' 
                              AND payment_date BETWEEN :thirty_days_ago AND :current_date";
    $monthly_revenue_stmt = $pdo->prepare($monthly_revenue_query);
    $monthly_revenue_stmt->execute([
        'thirty_days_ago' => $thirty_days_ago,
        'current_date' => $current_date
    ]);
    $monthly_revenue = $monthly_revenue_stmt->fetchColumn() ?: 0;

    // Revenue Data - Current Month
    $current_month_query = "SELECT COALESCE(SUM(amount), 0) as revenue 
                           FROM payments 
                           WHERE payment_status = 'completed' 
                           AND MONTH(payment_date) = :current_month 
                           AND YEAR(payment_date) = :current_year";
    $current_month_stmt = $pdo->prepare($current_month_query);
    $current_month_stmt->execute([
        'current_month' => $current_month,
        'current_year' => $current_year
    ]);
    $current_month_revenue = $current_month_stmt->fetchColumn() ?: 0;

    // Revenue Data - Previous Month
    $prev_month_query = "SELECT COALESCE(SUM(amount), 0) as revenue 
                        FROM payments 
                        WHERE payment_status = 'completed' 
                        AND MONTH(payment_date) = :previous_month 
                        AND YEAR(payment_date) = :previous_month_year";
    $prev_month_stmt = $pdo->prepare($prev_month_query);
    $prev_month_stmt->execute([
        'previous_month' => $previous_month,
        'previous_month_year' => $previous_month_year
    ]);
    $prev_month_revenue = $prev_month_stmt->fetchColumn() ?: 0;

    // Calculate growth percentage
    $revenue_growth = $prev_month_revenue > 0 ? 
        round((($current_month_revenue - $prev_month_revenue) / $prev_month_revenue) * 100, 1) : 0;

    // Total Sales Today
    $today_sales_query = "SELECT COALESCE(SUM(amount), 0) FROM payments 
                          WHERE payment_status = 'completed' 
                          AND DATE(payment_date) = CURDATE()";
    $today_sales_stmt = $pdo->query($today_sales_query);
    $today_sales = $today_sales_stmt->fetchColumn() ?: 0;

    // For Chart: Create last 12 months' date labels
    $chart_labels = [];
    $revenue_data = [];
    $transaction_data = [];
    
    // Generate last 12 months
    for ($i = 11; $i >= 0; $i--) {
        $month_date = date('Y-m-01', strtotime("-$i months"));
        $month_name = date('M Y', strtotime($month_date));
        $chart_labels[] = $month_name;
        
        $month = date('m', strtotime($month_date));
        $year = date('Y', strtotime($month_date));
        
        // Query revenue for this month
        $month_revenue_query = "SELECT COALESCE(SUM(amount), 0) as revenue, COUNT(*) as count 
                               FROM payments 
                               WHERE payment_status = 'completed' 
                               AND MONTH(payment_date) = :month 
                               AND YEAR(payment_date) = :year";
        $month_revenue_stmt = $pdo->prepare($month_revenue_query);
        $month_revenue_stmt->execute(['month' => $month, 'year' => $year]);
        $month_data = $month_revenue_stmt->fetch(PDO::FETCH_ASSOC);
        
        $revenue_data[] = floatval($month_data['revenue'] ?: 0);
        $transaction_data[] = intval($month_data['count'] ?: 0);
    }

    // Get popular packages
    $packages_query = "SELECT 
                          p.name as package_name,
                          COUNT(c.client_id) as client_count,
                          p.price
                       FROM packages p
                       LEFT JOIN clients c ON p.id = c.package_id
                       GROUP BY p.id, p.name, p.price
                       ORDER BY client_count DESC
                       LIMIT 5";
    $packages_stmt = $pdo->query($packages_query);
    $popular_packages = $packages_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no packages, provide empty array to prevent warnings
    if (empty($popular_packages)) {
        $popular_packages = [];
    }

    // Recent activity - last 10 payments, ordered from most recent
    $recent_activity_query = "SELECT 
                                p.payment_id,
                                p.payment_date,
                                p.amount,
                                p.payment_status,
                                c.name as client_name,
                                pkg.name as package_name
                              FROM payments p
                              JOIN clients c ON p.client_id = c.client_id
                              JOIN packages pkg ON p.package_id = pkg.id
                              ORDER BY p.payment_date DESC
                              LIMIT 10";
    $recent_activity_stmt = $pdo->query($recent_activity_query);
    $recent_activity = $recent_activity_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no activity, provide empty array to prevent warnings
    if (empty($recent_activity)) {
        $recent_activity = [];
    }

    // Logging for debugging
    error_log("Dashboard data: Active clients: $active_clients_count, Total clients: $total_clients_count");
    error_log("Ending soon: $ending_soon_count, Expired: $expired_count");
    error_log("Monthly revenue: $monthly_revenue, Current month: $current_month_revenue");
    error_log("Popular packages count: " . count($popular_packages));
    error_log("Recent activity count: " . count($recent_activity));

} catch (PDOException $e) {
    // Handle database errors gracefully
    $error_message = "Database error: " . $e->getMessage();
    error_log("Dashboard error: " . $e->getMessage());
    
    // Initialize with empty/default values to prevent errors
    $active_clients_count = 0;
    $total_clients_count = 0;
    $ending_soon_count = 0;
    $expired_count = 0;
    $pending_payments_count = 0;
    $pending_payments_total = 0;
    $monthly_revenue = 0;
    $current_month_revenue = 0; 
    $prev_month_revenue = 0;
    $revenue_growth = 0;
    $today_sales = 0;
    $chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $revenue_data = array_fill(0, 12, 0);
    $transaction_data = array_fill(0, 12, 0);
    $popular_packages = [];
    $recent_activity = [];
}
?>

<head>
    <title><?php echo $language["Dashboard"]; ?> | GMS</title>

    <?php include 'layouts/head.php'; ?>

    <!-- Dashboard-specific CSS -->
    <link href="assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />
    
    <!-- Additional CSS -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    
    <?php include 'layouts/head-style.php'; ?>
    
    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 24px;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        .metric-card .icon-box {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .metric-card:hover .icon-box {
            transform: scale(1.1);
        }
        
        .metric-card .icon-box i {
            font-size: 28px;
        }
        
        .bg-success-subtle {
            background-color: rgba(10, 179, 156, 0.18);
        }
        
        .bg-primary-subtle {
            background-color: rgba(85, 110, 230, 0.18);
        }
        
        .bg-info-subtle {
            background-color: rgba(41, 156, 219, 0.18);
        }
        
        .bg-warning-subtle {
            background-color: rgba(241, 180, 76, 0.18);
        }
        
        .bg-danger-subtle {
            background-color: rgba(244, 106, 106, 0.18);
        }
        
        .text-success {
            color: #0ab39c !important;
        }
        
        .text-primary {
            color: #556ee6 !important;
        }
        
        .text-info {
            color: #299cdb !important;
        }
        
        .text-warning {
            color: #f1b44c !important;
        }
        
        .text-danger {
            color: #f46a6a !important;
        }
        
        .metric-card .trend-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }
        
        .metric-card .trend-badge i {
            margin-right: 5px;
            font-size: 14px;
        }
        
        .metric-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #343a40;
        }
        
        .metric-label {
            font-size: 14px;
            font-weight: 500;
            color: #74788d;
            margin-bottom: 10px;
        }
        
        .chart-container {
            position: relative;
            min-height: 350px;
        }
        
        .table-activity th, .table-activity td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
        }
        
        .table-activity tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-activity tbody tr:hover {
            background-color: rgba(85, 110, 230, 0.05);
        }
        
        .activity-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-completed {
            background-color: #0ab39c;
        }
        
        .status-pending {
            background-color: #f1b44c;
        }
        
        .table-packages th, .table-packages td {
            padding: 1rem 1.25rem;
        }
        
        .table-packages tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-packages tbody tr:hover {
            background-color: rgba(85, 110, 230, 0.05);
        }
        
        .dashboard-welcome {
            background: linear-gradient(135deg, #556ee6 0%, #1e2c69 100%);
            border-radius: 1rem;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-welcome:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .dashboard-welcome h2 {
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .dashboard-welcome p {
            opacity: 0.9;
            max-width: 700px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .welcome-actions {
            margin-top: 25px;
        }
        
        .welcome-actions .btn {
            margin-right: 12px;
            margin-bottom: 12px;
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .welcome-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .data-card {
            padding: 24px;
            height: calc(100% - 24px);
        }
        
        .data-card .card-title {
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: 600;
            color: #343a40;
            position: relative;
            padding-bottom: 12px;
        }
        
        .data-card .card-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: #556ee6;
            border-radius: 3px;
        }
        
        .performance-metric {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .performance-circle {
            min-width: 120px;
            height: 120px;
            margin-right: 30px;
        }
        
        .badges-container {
            display: flex;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .metric-badge {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-right: 12px;
            margin-bottom: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .metric-badge:hover {
            background-color: #eef0f2;
            transform: translateY(-2px);
        }
        
        .metric-badge i {
            margin-right: 8px;
            font-size: 16px;
        }
        
        /* Table improvements */
        .table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 12px 15px;
        }
        
        .progress {
            height: 6px;
            overflow: visible;
            background-color: #eef0f2;
        }
        
        .progress-bar {
            position: relative;
            border-radius: 6px;
        }
        
        /* Member status section improvements */
        .member-status-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .member-status-item i {
            margin-right: 10px;
            font-size: 14px;
        }
        
        .member-status-item .label {
            flex-grow: 1;
            font-weight: 500;
        }
        
        .member-status-item .value {
            font-weight: 600;
            min-width: 50px;
            text-align: right;
        }
        
        /* Button improvements */
        .btn {
            font-weight: 500;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-sm {
            padding: 6px 16px;
            font-size: 13px;
        }
        
        /* Quick Actions */
        .quick-actions {
            margin-top: 10px;
            margin-bottom: 30px;
        }
        
        .quick-action-card {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }
        
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .quick-action-icon {
            width: 60px;
            height: 60px;
            background-color: #f8f9fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .quick-action-icon i {
            font-size: 24px;
            color: #556ee6;
        }
        
        .quick-action-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 5px;
        }
        
        .quick-action-desc {
            font-size: 13px;
            color: #74788d;
        }
        
        /* Date Range Selector */
        .date-range-selector {
            background-color: white;
            border-radius: 8px;
            padding: 10px 15px;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
        }
        
        .date-range-selector i {
            margin-right: 10px;
            color: #556ee6;
        }
        
        .date-range-selector select {
            border: none;
            background: transparent;
            font-weight: 500;
            color: #343a40;
            cursor: pointer;
        }
        
        @media (max-width: 767.98px) {
            .dashboard-welcome {
                padding: 25px;
            }
            
            .dashboard-welcome h2 {
                font-size: 24px;
            }
            
            .metric-value {
                font-size: 24px;
            }
            
            .performance-circle {
                min-width: 80px;
                height: 80px;
                margin-right: 15px;
            }
            
            .performance-metric {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .performance-circle {
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<?php include 'layouts/body.php'; ?>

<!-- Begin page -->
<div id="layout-wrapper">

    <?php include 'layouts/menu.php'; ?>

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Dashboard</h4>

                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
                                    <li class="breadcrumb-item active">Dashboard</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <!-- Welcome Section -->
                <div class="dashboard-welcome">
                    <h2>Welcome to Gym Management System</h2>
                    <p>Your fitness business command center provides real-time insights and analytics to help you make data-driven decisions. Track membership trends, financial performance, and critical metrics all in one place.</p>
                    <div class="welcome-actions">
                        <a href="clients.php" class="btn btn-light">
                            <i class="mdi mdi-account-group me-1"></i> Members
                        </a>
                        <a href="add_client.php" class="btn btn-light">
                            <i class="mdi mdi-account-plus me-1"></i> Add Member
                        </a>
                        <a href="packages.php" class="btn btn-light">
                            <i class="mdi mdi-package-variant me-1"></i> Packages
                        </a>
                        <a href="setup_subscription_cron.php" class="btn btn-light">
                            <i class="mdi mdi-clock-outline me-1"></i> Auto Renewal
                        </a>
                    </div>
                </div>

                <!-- Add this after Welcome Section -->
                <div class="row quick-actions">
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="clients.php?filter=ending_soon" style="text-decoration: none; color: inherit;">
                            <div class="quick-action-card">
                                <div class="quick-action-icon" style="background-color: rgba(241, 180, 76, 0.18);">
                                    <i class="mdi mdi-clock-alert-outline" style="color: #f1b44c;"></i>
                                </div>
                                <h5 class="quick-action-title">Expiring Subscriptions</h5>
                                <p class="quick-action-desc">View members with soon-to-expire subscriptions</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="clients.php?filter=pending_payments" style="text-decoration: none; color: inherit;">
                            <div class="quick-action-card">
                                <div class="quick-action-icon" style="background-color: rgba(244, 106, 106, 0.18);">
                                    <i class="mdi mdi-cash-multiple" style="color: #f46a6a;"></i>
                                </div>
                                <h5 class="quick-action-title">Pending Payments</h5>
                                <p class="quick-action-desc">Handle outstanding payment requests</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="add_client.php" style="text-decoration: none; color: inherit;">
                            <div class="quick-action-card">
                                <div class="quick-action-icon" style="background-color: rgba(10, 179, 156, 0.18);">
                                    <i class="mdi mdi-account-plus" style="color: #0ab39c;"></i>
                                </div>
                                <h5 class="quick-action-title">Add New Member</h5>
                                <p class="quick-action-desc">Register a new gym member</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="setup_subscription_cron.php" style="text-decoration: none; color: inherit;">
                            <div class="quick-action-card">
                                <div class="quick-action-icon" style="background-color: rgba(85, 110, 230, 0.18);">
                                    <i class="mdi mdi-cog-outline" style="color: #556ee6;"></i>
                                </div>
                                <h5 class="quick-action-title">System Settings</h5>
                                <p class="quick-action-desc">Configure subscription automation</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Add this before Revenue Chart for data range selection -->
                <div class="date-range-selector">
                    <i class="mdi mdi-calendar-range"></i>
                    <select id="dateRangeSelect" onchange="updateChartRange(this.value)">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 3 Months</option>
                        <option value="180">Last 6 Months</option>
                        <option value="365">Last Year</option>
                    </select>
                </div>

                <!-- Key Metrics -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="icon-box bg-primary-subtle">
                                            <i class="mdi mdi-account-multiple text-primary"></i>
                                        </div>
                                        <h4 class="metric-value"><?php echo number_format($active_clients_count); ?></h4>
                                        <p class="metric-label">Active Members</p>
                                        <span class="trend-badge bg-primary-subtle text-primary">
                                            <i class="mdi mdi-trending-up"></i> <?php echo round(($active_clients_count / ($total_clients_count ?: 1)) * 100); ?>% of total
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="icon-box bg-warning-subtle">
                                            <i class="mdi mdi-clock-alert-outline text-warning"></i>
                                        </div>
                                        <h4 class="metric-value"><?php echo number_format($ending_soon_count); ?></h4>
                                        <p class="metric-label">Subscriptions Ending Soon</p>
                                        <span class="trend-badge bg-warning-subtle text-warning">
                                            <i class="mdi mdi-calendar"></i> Next 7 days
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="icon-box bg-info-subtle">
                                            <i class="mdi mdi-cash-multiple text-info"></i>
                                        </div>
                                        <h4 class="metric-value">$<?php echo number_format($monthly_revenue, 2); ?></h4>
                                        <p class="metric-label">Revenue (Last 30 Days)</p>
                                        <span class="trend-badge bg-<?php echo $revenue_growth >= 0 ? 'success' : 'danger'; ?>-subtle text-<?php echo $revenue_growth >= 0 ? 'success' : 'danger'; ?>">
                                            <i class="mdi mdi-trending-<?php echo $revenue_growth >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($revenue_growth); ?>% vs last month
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="icon-box bg-danger-subtle">
                                            <i class="mdi mdi-credit-card-outline text-danger"></i>
                                        </div>
                                        <h4 class="metric-value">$<?php echo number_format($pending_payments_total, 2); ?></h4>
                                        <p class="metric-label">Pending Payments</p>
                                        <span class="trend-badge bg-danger-subtle text-danger">
                                            <i class="mdi mdi-account-alert"></i> <?php echo number_format($pending_payments_count); ?> payments awaiting
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts & Data -->
                <div class="row">
                    <!-- Revenue Chart -->
                    <div class="col-xl-8">
                        <div class="card data-card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Revenue & Transactions</h4>
                                <div class="badges-container">
                                    <div class="metric-badge">
                                        <i class="mdi mdi-cash text-success"></i>
                                        <span>Today: $<?php echo number_format($today_sales, 2); ?></span>
                                    </div>
                                    <div class="metric-badge">
                                        <i class="mdi mdi-calendar text-primary"></i>
                                        <span>This Month: $<?php echo number_format($current_month_revenue, 2); ?></span>
                                    </div>
                                    <div class="metric-badge">
                                        <i class="mdi mdi-account-convert text-info"></i>
                                        <span>Active Rate: <?php echo round(($active_clients_count / ($total_clients_count ?: 1)) * 100); ?>%</span>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <!-- Add chart error container -->
                                    <div id="chart-error"></div>
                                    <canvas id="revenueChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Popular Packages -->
                    <div class="col-xl-4">
                        <div class="card data-card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Popular Packages</h4>
                                <div class="table-responsive">
                                    <table class="table table-packages align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Package</th>
                                                <th>Price</th>
                                                <th>Members</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($popular_packages)): ?>
                                                <?php 
                                                // Calculate total in top packages for percentage
                                                $total_in_top_packages = array_sum(array_column($popular_packages, 'client_count'));
                                                ?>
                                                <?php foreach ($popular_packages as $package): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                                                    <td>$<?php echo number_format($package['price'], 2); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="me-2"><?php echo $package['client_count']; ?></span>
                                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                                <?php 
                                                                // Calculate percentage for progress bar
                                                                $percentage = $total_in_top_packages > 0 ? ($package['client_count'] / $total_in_top_packages) * 100 : 0;
                                                                ?>
                                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No package data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-4">
                                    <a href="packages.php" class="btn btn-primary btn-sm">View All Packages</a>
                                    <a href="add_package.php" class="btn btn-outline-primary btn-sm ms-2">Add Package</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Member Status -->
                    <div class="col-xl-4">
                        <div class="card data-card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Membership Status</h4>
                                <div class="performance-metric">
                                    <div class="performance-circle">
                                        <canvas id="membershipChart" width="100" height="100"></canvas>
                                    </div>
                                    <div>
                                        <div class="member-status-item">
                                            <i class="mdi mdi-circle text-primary"></i>
                                            <span class="label">Active</span>
                                            <span class="value"><?php echo $active_clients_count; ?></span>
                                        </div>
                                        <div class="member-status-item">
                                            <i class="mdi mdi-circle text-warning"></i>
                                            <span class="label">Expiring Soon</span>
                                            <span class="value"><?php echo $ending_soon_count; ?></span>
                                        </div>
                                        <div class="member-status-item">
                                            <i class="mdi mdi-circle text-danger"></i>
                                            <span class="label">Expired</span>
                                            <span class="value"><?php echo $expired_count; ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Add an insights section -->
                                <div class="mt-4 pt-3 border-top">
                                    <h6 class="text-uppercase text-muted fs-12 mb-3">Membership Insights</h6>
                                    
                                    <?php 
                                    $retention_rate = $total_clients_count > 0 ? round(($active_clients_count / $total_clients_count) * 100) : 0;
                                    $renewal_alert_class = $ending_soon_count > 5 ? 'text-danger' : ($ending_soon_count > 2 ? 'text-warning' : 'text-success');
                                    ?>
                                    
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-account-check text-success fs-24 me-2"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">Retention Rate</h6>
                                            <p class="text-muted mb-0">
                                                <span class="fw-semibold"><?php echo $retention_rate; ?>%</span> of members active
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="mdi mdi-bell-ring-outline <?php echo $renewal_alert_class; ?> fs-24 me-2"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">Renewal Alert</h6>
                                            <p class="text-muted mb-0">
                                                <span class="fw-semibold <?php echo $renewal_alert_class; ?>"><?php echo $ending_soon_count; ?> memberships</span> need renewal
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <a href="clients.php" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-account-details me-1"></i> Member Details
                                    </a>
                                    <a href="clients.php?filter=expired" class="btn btn-outline-danger btn-sm ms-2">
                                        <i class="mdi mdi-account-convert me-1"></i> View Expired
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-xl-8">
                        <div class="card data-card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Recent Activity</h4>
                                <div class="table-responsive">
                                    <table class="table table-activity align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Client</th>
                                                <th>Package</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recent_activity)): ?>
                                                <?php foreach ($recent_activity as $activity): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($activity['client_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['package_name']); ?></td>
                                                    <td>$<?php echo number_format($activity['amount'], 2); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($activity['payment_date'])); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="activity-status status-<?php echo strtolower($activity['payment_status']); ?>"></span>
                                                            <?php echo ucfirst($activity['payment_status']); ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No recent activity available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (empty($recent_activity)): ?>
                                    <div class="text-center mt-4">
                                        <a href="add_client.php" class="btn btn-primary btn-sm">Add New Client</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        <?php include 'layouts/footer.php'; ?>
    </div>
    <!-- end main content-->

</div>
<!-- END layout-wrapper -->

<!-- Right Sidebar -->
<?php include 'layouts/right-sidebar.php'; ?>
<!-- /Right-bar -->

<!-- JAVASCRIPT -->
<?php include 'layouts/vendor-scripts.php'; ?>

<!-- apexcharts -->
<script src="assets/libs/apexcharts/apexcharts.min.js"></script>

<!-- Plugins js-->
<script src="assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js"></script>

<!-- Prevent dashboard.init.js from running as we have our own charts -->
<script>
// Override the dashboard.init.js script to prevent errors
window.addEventListener('load', function() {
    // Define getChartColorsArray function that dashboard.init.js expects
    window.getChartColorsArray = function(id) {
        return ["#556ee6", "#f1b44c", "#34c38f"];
    };
});
</script>

<!-- Add Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Error handling function for charts
    function initializeChart(chartFunc) {
        try {
            chartFunc();
        } catch (error) {
            console.error("Chart initialization error:", error);
            // Display error on UI for admin - could be removed in production
            document.getElementById('chart-error').innerHTML = 
                '<div class="alert alert-warning">Chart data could not be loaded. Please check the console for details.</div>';
        }
    }
    
    // Revenue Chart
    initializeChart(function() {
        var revenueCtx = document.getElementById('revenueChart').getContext('2d');
        
        // Safely parse chart data with fallbacks
        var chartLabels = <?php echo json_encode($chart_labels) ?: '[]'; ?>;
        var revenueData = <?php echo json_encode($revenue_data) ?: '[]'; ?>;
        var transactionData = <?php echo json_encode($transaction_data) ?: '[]'; ?>;
        
        // Verify data integrity
        if (!Array.isArray(chartLabels) || !chartLabels.length) {
            chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        }
        
        if (!Array.isArray(revenueData) || !revenueData.length) {
            revenueData = Array(chartLabels.length).fill(0);
        }
        
        if (!Array.isArray(transactionData) || !transactionData.length) {
            transactionData = Array(chartLabels.length).fill(0);
        }
        
        var revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: revenueData,
                        backgroundColor: 'rgba(85, 110, 230, 0.7)',
                        borderColor: 'rgb(85, 110, 230)',
                        borderWidth: 1,
                        order: 1
                    },
                    {
                        label: 'Transactions',
                        data: transactionData,
                        type: 'line',
                        backgroundColor: 'rgba(241, 180, 76, 0.3)',
                        borderColor: 'rgb(241, 180, 76)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgb(241, 180, 76)',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        tension: 0.3,
                        order: 0,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Transactions'
                        },
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 20
                        }
                    }
                }
            }
        });
        
        // Attach chart to window object for use by date range selector
        window.revenueChart = revenueChart;
    });
    
    // Membership Doughnut Chart
    initializeChart(function() {
        var membershipCtx = document.getElementById('membershipChart').getContext('2d');
        
        // Get data with proper parsing and fallback
        var active = <?php echo max(0, $active_clients_count - $ending_soon_count); ?> || 0;
        var endingSoon = <?php echo max(0, $ending_soon_count); ?> || 0;
        var expired = <?php echo max(0, $expired_count); ?> || 0;
        
        // If all values are 0, add dummy data to avoid empty chart
        if (active === 0 && endingSoon === 0 && expired === 0) {
            active = 1; // Add at least one active to show something
        }
        
        var memberData = [active, endingSoon, expired];
        
        var membershipChart = new Chart(membershipCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Ending Soon', 'Expired'],
                datasets: [{
                    data: memberData,
                    backgroundColor: [
                        'rgba(85, 110, 230, 0.8)',
                        'rgba(241, 180, 76, 0.8)',
                        'rgba(244, 106, 106, 0.8)'
                    ],
                    borderColor: [
                        'rgb(85, 110, 230)',
                        'rgb(241, 180, 76)',
                        'rgb(244, 106, 106)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
});
</script>

<!-- Add this JavaScript for the date range selector functionality -->
<script>
function updateChartRange(days) {
    // Convert to integer
    days = parseInt(days);
    
    // Create form data
    var formData = new FormData();
    formData.append('days', days);
    
    // Show loading indicator
    document.getElementById('revenueChart').style.opacity = 0.5;
    
    // Make AJAX request to get new chart data
    fetch('ajax/get_revenue_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // If we have Chart.js object defined
        if (window.revenueChart) {
            // Update chart data
            window.revenueChart.data.labels = data.labels || [];
            window.revenueChart.data.datasets[0].data = data.revenue || [];
            window.revenueChart.data.datasets[1].data = data.transactions || [];
            window.revenueChart.update();
        }
        document.getElementById('revenueChart').style.opacity = 1;
    })
    .catch(error => {
        console.error('Error updating chart:', error);
        alert('Could not update chart data. Please try again.');
        document.getElementById('revenueChart').style.opacity = 1;
    });
}
</script>

<!-- App js -->
<script src="assets/js/app.js"></script>

</body>
</html>