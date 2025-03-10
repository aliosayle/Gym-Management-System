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
?>

<head>
    <title><?php echo $language["Dashboard"]; ?> | GMS</title>

    <?php include 'layouts/head.php'; ?>

    <link href="assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet"
        type="text/css" />

    <?php include 'layouts/head-style.php'; ?>
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
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Login</a></li>
                                    <li class="breadcrumb-item active">Dashboard</li>
                                </ol>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">

                    <div class="col-xl-3 col-md-6">
                        <?php
                        // Fetch the total number of clients
                        $query = "SELECT COUNT(*) AS count FROM clients";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $row = $stmt->fetch();
                        $count = $row['count'];
                        ?>
                        <a href="clients.php">
                            <div class="card card-h-100">
                                <!-- card body -->
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-6">
                                            <span class="text-muted mb-3 lh-1 d-block">Total Clients</span>
                                            <h4 class="mb-3">
                                                <span><?php echo htmlspecialchars($count); ?></span>
                                            </h4>
                                        </div>

                                    </div>

                                </div><!-- end card body -->
                            </div><!-- end card -->
                        </a>
                    </div><!-- end col -->

                    <div class="col-xl-3 col-md-6">
                        <?php
                        // Fetch the number of clients with subscriptions ending soon
                        $query = "SELECT COUNT(*) AS count FROM clients WHERE subscription_end_date <= DATE_ADD(NOW(), INTERVAL 3 DAY)";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $row = $stmt->fetch();
                        $count = $row['count'];
                        ?>
                        <a href="clients.php?filter=ending_soon">
                            <div class="card card-h-100">
                                <!-- card body -->
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-6">
                                            <span class="text-muted mb-3 lh-1 d-block">Subscriptions Ending Soon</span>
                                            <h4 class="mb-3">
                                                <span><?php echo htmlspecialchars($count); ?></span>
                                            </h4>
                                        </div>

                                    </div>

                                </div><!-- end card body -->
                            </div><!-- end card -->
                        </a>
                    </div><!-- end col -->

                    <div class="col-xl-3 col-md-6">
                        <?php
                        // Fetch the sum of pending payments
                        $query = "SELECT SUM(amount) AS total_pending FROM payments WHERE payment_status = 'pending'";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $row = $stmt->fetch();
                        $total_pending = $row['total_pending'];
                        ?>
                        <a href="clients.php?filter=pending_payments">
                            <div class="card card-h-100">
                                <!-- card body -->
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-6">
                                            <span class="text-muted mb-3 lh-1 d-block">Pending Payments</span>
                                            <h4 class="mb-3">
                                                <span>$<?php echo htmlspecialchars(number_format($total_pending, 2)); ?></span>
                                            </h4>
                                        </div>

                                    </div>

                                </div><!-- end card body -->
                            </div><!-- end card -->
                        </a>
                    </div><!-- end col -->

                    <div class="col-xl-3 col-md-6">
                        <?php
                        // Fetch the total sales amount for today
                        $query = "SELECT SUM(si.price * si.quantity) AS total_sales_today
                                  FROM sale_items si
                                  JOIN sales s ON si.sale_id = s.sale_id
                                  WHERE DATE(s.sale_date) = CURDATE()";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $row = $stmt->fetch();
                        $total_sales_today = $row['total_sales_today'];
                        ?>
                        <a href="sales.php?from_date=<?php echo date('Y-m-d'); ?>&to_date=<?php echo date('Y-m-d'); ?>">
                            <div class="card card-h-100">
                                <!-- card body -->
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-6">
                                            <span class="text-muted mb-3 lh-1 d-block">Total Sales Today</span>
                                            <h4 class="mb-3">
                                                <span>$<?php echo htmlspecialchars(number_format($total_sales_today, 2)); ?></span>
                                            </h4>
                                        </div>

                                    </div>

                                </div><!-- end card body -->
                            </div><!-- end card -->
                        </a>
                    </div><!-- end col -->

                </div><!-- end row-->

                <div class="row">
                    <div class="col-12">
                        <?php
                        // Fetch the number of subscribers for each month and package
                        $query = "SELECT MONTH(c.created_at) AS month, p.name AS package_name, COUNT(*) AS count
                                  FROM clients c
                                  JOIN packages p ON c.package_id = p.id
                                  WHERE c.subscription_status = 'active'
                                  GROUP BY MONTH(c.created_at), p.name";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $subscribers_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Prepare data for the chart
                        $packages = [];
                        $months = array_fill(1, 12, []);

                        foreach ($subscribers_data as $data) {
                            $package_name = $data['package_name'];
                            $month = (int)$data['month'];
                            $count = (int)$data['count'];

                            if (!isset($packages[$package_name])) {
                                $packages[$package_name] = array_fill(1, 12, 0);
                            }
                            $packages[$package_name][$month] = $count;
                        }

                        // Fetch the total sales amount for each month
                        $sales_query = "SELECT MONTH(s.sale_date) AS month, SUM(si.price * si.quantity) AS total_sales
                                        FROM sales s
                                        JOIN sale_items si ON s.sale_id = si.sale_id
                                        GROUP BY MONTH(s.sale_date)";
                        $sales_stmt = $pdo->prepare($sales_query);
                        $sales_stmt->execute();
                        $sales_data = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Prepare data for the sales chart
                        $sales_months = array_fill(1, 12, 0);
                        foreach ($sales_data as $data) {
                            $sales_months[(int)$data['month']] = (float)$data['total_sales'];
                        }
                        ?>
                        <div class="row">
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">Subscribers Throughout the Year</h4>
                                        <canvas id="subscribersChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">Sales Throughout the Year</h4>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Month</th>
                                                    <th>Total Sales</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $months_names = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                                    foreach ($sales_months as $month => $total_sales) {
                                                        $from_date = date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
                                                        $to_date = date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime($from_date));
                                                        echo "<tr onclick=\"window.location.href='sales.php?from_date=$from_date&to_date=$to_date'\" style='cursor:pointer;'>";
                                                        echo "<td>" . $months_names[$month - 1] . "</td>";
                                                        echo "<td>$" . number_format($total_sales, 2) . "</td>";
                                                        echo "</tr>";
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- end row-->


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
<script>
console.log("%cSTOP! 🛑", "color: red; font-size: 50px;");
console.log("Why are you snooping around? Here's something for you:");
console.log("https://www.youtube.com/watch?v=dQw4w9WgXcQ");
</script>

<!-- apexcharts -->
<script src="assets/libs/apexcharts/apexcharts.min.js"></script>

<!-- Plugins js-->
<script src="assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js"></script>

<!-- dashboard init -->
<script src="assets/js/pages/dashboard.init.js"></script>

<!-- Add Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('subscribersChart').getContext('2d');
    var colors = [
        'rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)',
        'rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 159, 64, 0.2)'
    ];
    var borderColors = [
        'rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)',
        'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)'
    ];
    var datasets = [];
    <?php $index = 0; foreach ($packages as $package_name => $data): ?>
    datasets.push({
        label: '<?php echo htmlspecialchars($package_name); ?>',
        data: <?php echo json_encode(array_values($data)); ?>,
        backgroundColor: colors[<?php echo $index; ?> % colors.length],
        borderColor: borderColors[<?php echo $index; ?> % borderColors.length],
        borderWidth: 1,
        fill: true
    });
    <?php $index++; endforeach; ?>
    
    var subscribersChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
                'September', 'October', 'November', 'December'
            ],
            datasets: datasets
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<!-- App js -->
<script src="assets/js/app.js"></script>

</body>

</html>