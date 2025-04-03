<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include('layouts/config.php'); ?>
<?php include('layouts/check_permission.php'); ?>

<?php
// Configuration and session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check specific permissions for this page
$can_manage_packages = has_permission('can_manage_packages', $pdo);
$can_edit_package = has_permission('can_edit_package', $pdo) || has_permission('can_manage_packages', $pdo);

// If user doesn't have permission to edit packages, redirect them
if (!$can_edit_package) {
    $_SESSION['error_message'] = "You don't have permission to edit packages.";
    header("Location: packages.php");
    exit;
}

$package_id = $_GET['id'] ?? null;
if (!$package_id) {
    echo "<script>alert('No package ID provided.'); window.location.href = 'packages.php';</script>";
    exit;
}

// Fetch package details
$query = "SELECT * FROM packages WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $package_id]);
$package = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$package) {
    echo "<script>alert('Package not found.'); window.location.href = 'packages.php';</script>";
    exit;
}

// Handle form submission for updating package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $number_of_days = (int)$_POST['number_of_days'];

    $update_sql = "UPDATE packages SET name = :name, price = :price, number_of_days = :number_of_days WHERE id = :id";
    $stmt = $pdo->prepare($update_sql);
    if ($stmt->execute([
        'name' => $name,
        'price' => $price,
        'number_of_days' => $number_of_days,
        'id' => $package_id
    ])) {
        echo "<script>alert('Package updated successfully'); window.location.href = 'packages.php';</script>";
    } else {
        echo "<script>alert('Error updating package: " . implode(", ", $stmt->errorInfo()) . "');</script>";
    }
}
?>

<head>
    <title>Edit Package | Admin Dashboard</title>
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
                                <li class="breadcrumb-item"><a href="packages.php">Packages</a></li>
                                <li class="breadcrumb-item active">Edit Package</li>
                            </ol>
                        </div>
                        <br>
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Edit Package</h4>
                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Package Details</h4>
                                <p class="card-title-desc">Please update the details of the package.</p>
                            </div>
                            <div class="card-body p-4">

                                <form method="POST">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <input type="hidden" name="form_submitted" value="1">

                                            <div class="mb-3">
                                                <label for="name" class="form-label">Name</label>
                                                <input class="form-control" type="text" name="name" id="name" value="<?php echo htmlspecialchars($package['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="price" class="form-label">Price</label>
                                                <input class="form-control" type="number" step="0.01" name="price" id="price" value="<?php echo htmlspecialchars($package['price']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="number_of_days" class="form-label">Number of Days</label>
                                                <input class="form-control" type="number" name="number_of_days" id="number_of_days" value="<?php echo htmlspecialchars($package['number_of_days']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Update Package</button>
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