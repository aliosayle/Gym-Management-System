<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>

<head>
    <title><?php echo $language["Dashboard"]; ?> | CMS</title>

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
                        include "layouts/config.php";
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

<!-- App js -->
<script src="assets/js/app.js"></script>

</body>

</html>