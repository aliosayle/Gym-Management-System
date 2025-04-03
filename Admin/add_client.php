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

// Get branch ID from the request
$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 
             (isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 1);

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

// Handle form submission for inserting new client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $subscription_status = 'active';
    $package_id = $_POST['package_id'];
    $payment_id = uniqid();
    $client_branch_id = $_POST['branch_id'];
    $amount = null;
    
    // Get the custom subscription start date or use current date
    $subscription_start_date = !empty($_POST['subscription_start_date']) ? 
                               $_POST['subscription_start_date'] : 
                               date('Y-m-d');
    
    // Validate the date format
    $start_date = DateTime::createFromFormat('Y-m-d', $subscription_start_date);
    if (!$start_date) {
        die("Invalid date format. Please use YYYY-MM-DD format.");
    }

    // Fetch package price and duration
    $package_sql = "SELECT price, number_of_days FROM packages WHERE id = :package_id";
    $stmt = $pdo->prepare($package_sql);
    $stmt->execute(['package_id' => $package_id]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        die("Selected package not found.");
    }
    
    $amount = $package['price'];
    $days = $package['number_of_days'];
    
    // Calculate subscription end date based on start date and package duration
    $end_date = clone $start_date;
    $end_date->add(new DateInterval("P{$days}D"));
    $subscription_end_date = $end_date->format('Y-m-d');

    $payment_method = 'cash';
    $payment_status = 'Pending';

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Insert new client
        $client_id = bin2hex(random_bytes(16)); // Generate a 36-character UUID
        $insert_client_sql = "INSERT INTO clients (
                                client_id, name, phone_number, package_id, 
                                branch_id, subscription_status, subscription_end_date
                              ) VALUES (
                                :client_id, :name, :phone_number, :package_id, 
                                :branch_id, :subscription_status, :subscription_end_date
                              )";
        $stmt = $pdo->prepare($insert_client_sql);
        $stmt->execute([
            'client_id' => $client_id,
            'name' => $name,
            'phone_number' => $phone_number,
            'package_id' => $package_id,
            'branch_id' => $client_branch_id,
            'subscription_status' => $subscription_status,
            'subscription_end_date' => $subscription_end_date
        ]);

        // Insert new payment
        $insert_payment_sql = "INSERT INTO payments (
                                payment_id, client_id, amount, payment_method, 
                                package_id, payment_status, payment_date, branch_id
                              ) VALUES (
                                :payment_id, :client_id, :amount, :payment_method, 
                                :package_id, :payment_status, :payment_date, :branch_id
                              )";
        $stmt = $pdo->prepare($insert_payment_sql);
        $stmt->execute([
            'payment_id' => $payment_id,
            'client_id' => $client_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'package_id' => $package_id,
            'payment_status' => $payment_status,
            'payment_date' => $subscription_start_date,
            'branch_id' => $client_branch_id
        ]);

        // Commit transaction
        $pdo->commit();
        
        // Success message
        $_SESSION['message'] = "Client added successfully with subscription starting on " . $subscription_start_date;
        $_SESSION['alert_type'] = "success";
        
    } catch (Exception $e) {
        // Rollback transaction if something goes wrong
        $pdo->rollBack();
        
        // Error message
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['alert_type'] = "danger";
        
        throw $e;
    }

    // Redirect back to clients page for the same branch
    header("Location: clients.php?branch_id=" . $client_branch_id);
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
                                            <input type="hidden" name="branch_id" value="<?php echo $branch_id; ?>">

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
                                                    // Fetch packages data for the selected branch
                                                    $package_sql = "SELECT id, name FROM packages WHERE branch_id = :branch_id";
                                                    $stmt = $pdo->prepare($package_sql);
                                                    $stmt->execute(['branch_id' => $branch_id]);
                                                    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($packages as $package) {
                                                        echo '<option value="' . htmlspecialchars($package['id']) . '">' . htmlspecialchars($package['name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="subscription_start_date" class="form-label">Subscription Start Date</label>
                                                <input class="form-control" type="date" name="subscription_start_date" id="subscription_start_date" value="<?php echo date('Y-m-d'); ?>">
                                                <small class="text-muted">Leave as today's date or select a custom start date for the subscription.</small>
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
            placeholder: "Select a package",
            allowClear: true
        });
        
        // Remove the branch change handler since we no longer have a branch dropdown
    });
</script>

<script src="assets/js/app.js"></script>

</body>

</html>
