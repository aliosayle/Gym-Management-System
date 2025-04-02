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

// Get branch ID (either from POST or GET)
$branch_id = isset($_POST['branch_id']) ? $_POST['branch_id'] : 
             (isset($_GET['branch_id']) ? $_GET['branch_id'] : 1); // Default to branch 1 if not specified

// Get user's assigned branches for dropdown
$user_id = $_SESSION['id'];
$branches_query = "SELECT b.* FROM branches b 
                  JOIN user_branches ub ON b.id = ub.branch_id 
                  WHERE ub.user_id = :user_id";
$branches_stmt = $pdo->prepare($branches_query);
$branches_stmt->execute(['user_id' => $user_id]);
$user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);

// If admin with no branches assigned, get all branches
$isadmin_query = "SELECT isadmin FROM users WHERE id = :id";
$isadmin_stmt = $pdo->prepare($isadmin_query);
$isadmin_stmt->execute(['id' => $user_id]);
$is_admin = $isadmin_stmt->fetchColumn();

if ($is_admin && empty($user_branches)) {
    $branches_query = "SELECT * FROM branches";
    $branches_stmt = $pdo->prepare($branches_query);
    $branches_stmt->execute();
    $user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission for inserting new package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $number_of_days = (int)$_POST['number_of_days'];
    $package_branch_id = $_POST['branch_id'];

    $insert_sql = "INSERT INTO packages (name, price, number_of_days, branch_id) 
                  VALUES (:name, :price, :number_of_days, :branch_id)";
    $stmt = $pdo->prepare($insert_sql);
    $stmt->execute([
        'name' => $name,
        'price' => $price,
        'number_of_days' => $number_of_days,
        'branch_id' => $package_branch_id
    ]);

    // Redirect back to packages page for the same branch
    header("Location: packages.php?branch_id=" . $package_branch_id);
    exit;
}
?>

<head>
    <title>Add Package | Admin Dashboard</title>
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
                                <li class="breadcrumb-item active">Add Package</li>
                            </ol>
                        </div>
                        <br>
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Add Package</h4>
                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Package Details</h4>
                                <p class="card-title-desc">Please enter the details of the new package.</p>
                            </div>
                            <div class="card-body p-4">

                                <form method="POST">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <input type="hidden" name="form_submitted" value="1">
                                            <input type="hidden" name="branch_id" value="<?php echo $branch_id; ?>">

                                            <div class="mb-3">
                                                <label for="name" class="form-label">Name</label>
                                                <input class="form-control" type="text" name="name" id="name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="price" class="form-label">Price</label>
                                                <input class="form-control" type="number" step="0.01" name="price" id="price" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="number_of_days" class="form-label">Number of Days</label>
                                                <input class="form-control" type="number" name="number_of_days" id="number_of_days" required>
                                            </div>
                                        </div>
                                    </div>

                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Add Package</button>
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
            placeholder: "Select a branch",
            allowClear: true
        });                         
    });
</script>

<script src="assets/js/app.js"></script>

</body>

</html>