<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();
include 'layouts/config.php';
include 'layouts/session.php';
include 'layouts/head-main.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['id'])) {
    header("location: auth-login.php");
    exit;
}

// Check if user is admin
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($current_user['isadmin']) || $current_user['isadmin'] != 1) {
    header("location: clients.php");
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'User ID is required';
    header('Location: users.php');
    exit;
}

$edit_user_id = $_GET['id'];

// Get user data
$user_query = "SELECT * FROM users WHERE id = :id";
$user_stmt = $pdo->prepare($user_query);
$user_stmt->execute(['id' => $edit_user_id]);
$edit_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$edit_user) {
    $_SESSION['error'] = 'User not found';
    header('Location: users.php');
    exit;
}

// Get user permissions
$permissions_query = "SELECT * FROM user_permissions WHERE user_id = :user_id";
$permissions_stmt = $pdo->prepare($permissions_query);
$permissions_stmt->execute(['user_id' => $edit_user_id]);
$user_permissions = $permissions_stmt->fetch(PDO::FETCH_ASSOC);

// Set default permissions if none found
if (!$user_permissions) {
    $user_permissions = [
        'view_dashboard' => 0,
        'manage_clients' => 0,
        'add_client' => 0,
        'edit_client' => 0,
        'delete_client' => 0,
        'manage_inventory' => 0,
        'manage_invoices' => 0,
        'use_pos' => 0,
        'view_reports' => 0,
        'manage_packages' => 0,
        'manage_companies' => 0,
        'manage_branches' => 0,
        'manage_users' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage User Permissions | Gym Management</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
</head>

<body>
    <?php include 'layouts/body.php'; ?>
    <div id="layout-wrapper">
        <?php include 'layouts/menu.php'; ?>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">Manage User Permissions</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                                        <li class="breadcrumb-item active">Manage Permissions</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Success and error messages -->
                    <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Permissions for: <?php echo htmlspecialchars($edit_user['username']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <form action="process_user.php" method="post">
                                        <input type="hidden" name="action" value="update_permissions">
                                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="view_dashboard" name="permissions[view_dashboard]" value="1" <?php echo (isset($user_permissions['view_dashboard']) && $user_permissions['view_dashboard'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="view_dashboard">View Dashboard</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="manage_clients" name="permissions[manage_clients]" value="1" <?php echo (isset($user_permissions['manage_clients']) && $user_permissions['manage_clients'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="manage_clients">Manage Clients</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="add_client" name="permissions[add_client]" value="1" <?php echo (isset($user_permissions['add_client']) && $user_permissions['add_client'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="add_client">Add Clients</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="edit_client" name="permissions[edit_client]" value="1" <?php echo (isset($user_permissions['edit_client']) && $user_permissions['edit_client'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="edit_client">Edit Clients</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="delete_client" name="permissions[delete_client]" value="1" <?php echo (isset($user_permissions['delete_client']) && $user_permissions['delete_client'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="delete_client">Delete Clients</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="manage_inventory" name="permissions[manage_inventory]" value="1" <?php echo (isset($user_permissions['manage_inventory']) && $user_permissions['manage_inventory'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="manage_inventory">Manage Inventory</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="manage_invoices" name="permissions[manage_invoices]" value="1" <?php echo (isset($user_permissions['manage_invoices']) && $user_permissions['manage_invoices'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="manage_invoices">Manage Invoices</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="use_pos" name="permissions[use_pos]" value="1" <?php echo (isset($user_permissions['use_pos']) && $user_permissions['use_pos'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="use_pos">Use POS</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="view_reports" name="permissions[view_reports]" value="1" <?php echo (isset($user_permissions['view_reports']) && $user_permissions['view_reports'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="view_reports">View Reports</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="manage_packages" name="permissions[manage_packages]" value="1" <?php echo (isset($user_permissions['manage_packages']) && $user_permissions['manage_packages'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="manage_packages">Manage Packages</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="manage_companies" name="permissions[manage_companies]" value="1" <?php echo (isset($user_permissions['manage_companies']) && $user_permissions['manage_companies'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="manage_companies">Manage Companies</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="manage_branches" name="permissions[manage_branches]" value="1" <?php echo (isset($user_permissions['manage_branches']) && $user_permissions['manage_branches'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="manage_branches">Manage Branches</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="manage_users" name="permissions[manage_users]" value="1" <?php echo (isset($user_permissions['manage_users']) && $user_permissions['manage_users'] == 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="manage_users">Manage Users</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-3">
                                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                                            <button type="submit" class="btn btn-primary">Save Permissions</button>
                                        </div>
                                    </form>
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

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <?php include 'layouts/vendor-scripts.php'; ?>
    <script src="assets/js/app.js"></script>
    
</body>
</html> 