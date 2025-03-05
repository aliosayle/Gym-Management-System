<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include('layouts/config.php'); ?>

<?php
// Configuration and session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission for inserting new client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $subscription_status = $_POST['subscription_status'];
    $package_id = $_POST['package_id'];
    $payment_id = uniqid();
    $amount = null;

    // Fetch package price
    $package_price_sql = "SELECT price FROM packages WHERE id = :package_id";
    $stmt = $pdo->prepare($package_price_sql);
    $stmt->execute(['package_id' => $package_id]);
    $amount = $stmt->fetchColumn();

    $payment_method = 'cash';
    $payment_status = 'Pending';

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Insert new client
        $client_id = bin2hex(random_bytes(16)); // Generate a 36-character UUID
        $insert_client_sql = "INSERT INTO clients (client_id, name, phone_number, package_id) VALUES (:client_id, :name, :phone_number, :package_id)";
        $stmt = $pdo->prepare($insert_client_sql);
        $stmt->execute([
            'client_id' => $client_id,
            'name' => $name,
            'phone_number' => $phone_number,
            'package_id' => $package_id
        ]);

        // Insert new payment
        $insert_payment_sql = "INSERT INTO payments (payment_id, client_id, amount, payment_method, package_id, payment_status) VALUES (:payment_id, :client_id, :amount, :payment_method, :package_id, :payment_status)";
        $stmt = $pdo->prepare($insert_payment_sql);
        $stmt->execute([
            'payment_id' => $payment_id,
            'client_id' => $client_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'package_id' => $package_id,
            'payment_status' => $payment_status
        ]);

        // Commit transaction
        $pdo->commit();
    } catch (Exception $e) {
        // Rollback transaction if something goes wrong
        $pdo->rollBack();
        throw $e;
    }

    header("Location: clients.php");
    exit;
}
?>

<head>
    <title>Add Client | Admin Dashboard</title>
    <?php include 'layouts/head.php'; ?>
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
            <div class="organisation-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>
                                <li class="breadcrumb-item active">Add Client</li>
                            </ol>
                        </div>
                        <br>
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Add Client</h4>
                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Client Details</h4>
                                <p class="card-title-desc">Please enter the details of the new client.</p>
                            </div>
                            <div class="card-body p-4">

                                <form method="POST">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <input type="hidden" name="form_submitted" value="1">

                                            <div class="mb-3">
                                                <label for="name" class="form-label">Name</label>
                                                <input class="form-control" type="text" name="name" id="name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="phone_number" class="form-label">Phone Number</label>
                                                <input class="form-control" type="text" name="phone_number" id="phone_number" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="package_id" class="form-label">Package</label>
                                                <select class="form-control" name="package_id" id="package_id" required>
                                                    <?php
                                                    // Fetch packages data
                                                    $package_sql = "SELECT id, name FROM packages";
                                                    $stmt = $pdo->prepare($package_sql);
                                                    $stmt->execute();
                                                    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($packages as $package) {
                                                        echo '<option value="' . htmlspecialchars($package['id']) . '">' . htmlspecialchars($package['name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                        </div>
                                    </div>

                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Add Client</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- organisation-fluid -->
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
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Select a site",
            allowClear: true
        });
    });
</script>

<script src="assets/js/app.js"></script>

</body>

</html>
