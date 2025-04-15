<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include session and configuration files
include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

// This feature has been disabled
$_SESSION['message'] = 'The permissions setup feature has been disabled.';
header('Location: index.php');
exit;

// Ensure only admin can access this page
if (!isset($_SESSION['id'])) {
    header("location: auth-login.php");
    exit;
}

// Check if user is admin
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($user['isadmin']) || $user['isadmin'] != 1) {
    header("location: index.php");
    exit;
}

$success_message = '';
$error_message = '';

// If the setup form is submitted
if (isset($_POST['setup_permissions'])) {
    try {
        // Read and execute the SQL script
        $sql_file = file_get_contents('scripts/create_permission_tables.sql');
        $pdo->exec($sql_file);
        $success_message = 'Permission system has been set up successfully!';
    } catch (PDOException $e) {
        $error_message = 'Error setting up permission system: ' . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Setup Permissions | Gym Management</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
</head>

<body>
    <?php include 'layouts/body.php'; ?>
    <div id="layout-wrapper">
        <?php include 'layouts/menu.php'; ?>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Breadcrumb -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">Setup Permission System</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Setup Permissions</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Setup Card -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Setup User Permission System</h5>
                                </div>
                                <div class="card-body">
                                    <p>This will set up the user permission system for your Gym Management System. The following actions will be taken:</p>
                                    <ul>
                                        <li>Create the <code>user_permissions</code> table if it doesn't exist</li>
                                        <li>Create the <code>user_branches</code> table if it doesn't exist</li>
                                        <li>Assign full permissions to existing admin users</li>
                                        <li>Assign basic permissions to existing non-admin users</li>
                                    </ul>
                                    <p>After setting up, you can manage user permissions through the <a href="users.php">User Management</a> page.</p>
                                    
                                    <form method="POST" action="">
                                        <div class="mt-4">
                                            <button type="submit" name="setup_permissions" class="btn btn-primary">Setup Permission System</button>
                                            <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Permission System Overview</h5>
                                </div>
                                <div class="card-body">
                                    <p>The permission system enables you to:</p>
                                    <ul>
                                        <li>Define granular permissions for each user</li>
                                        <li>Assign users to specific branches</li>
                                        <li>Control access to different parts of the system</li>
                                    </ul>
                                    <p><strong>Available Permissions:</strong></p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul>
                                                <li>View Dashboard</li>
                                                <li>Manage Clients</li>
                                                <li>Add Clients</li>
                                                <li>Edit Clients</li>
                                                <li>Delete Clients</li>
                                                <li>Manage Inventory</li>
                                                <li>Manage Invoices</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul>
                                                <li>Use POS</li>
                                                <li>View Reports</li>
                                                <li>Manage Packages</li>
                                                <li>Manage Companies</li>
                                                <li>Manage Branches</li>
                                                <li>Manage Users</li>
                                            </ul>
                                        </div>
                                    </div>
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

    <?php include 'layouts/vendor-scripts.php'; ?>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="assets/js/pages/sweet-alerts.init.js"></script>

</body>
</html> 