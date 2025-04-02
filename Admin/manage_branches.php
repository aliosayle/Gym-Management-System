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

// Get all branches
$all_branches_query = "SELECT * FROM branch ORDER BY branch_name ASC";
$all_branches_stmt = $pdo->prepare($all_branches_query);
$all_branches_stmt->execute();
$all_branches = $all_branches_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's branches
$user_branches_query = "SELECT branch_id FROM user_branch WHERE user_id = :user_id";
$user_branches_stmt = $pdo->prepare($user_branches_query);
$user_branches_stmt->execute(['user_id' => $edit_user_id]);
$user_branch_rows = $user_branches_stmt->fetchAll(PDO::FETCH_ASSOC);

$user_branches = [];
foreach ($user_branch_rows as $row) {
    $user_branches[] = $row['branch_id'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage User Branches | Gym Management</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
    <style>
        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border-color: #ced4da;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #556ee6;
            border-color: #556ee6;
            color: #fff;
            padding: 2px 8px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
            margin-right: 5px;
        }
    </style>
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
                                <h4 class="mb-sm-0">Manage User Branches</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                                        <li class="breadcrumb-item active">Manage Branches</li>
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
                                    <h5 class="card-title">Branch Access for: <?php echo htmlspecialchars($edit_user['username']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <form action="process_user.php" method="post">
                                        <input type="hidden" name="action" value="update_branches">
                                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                                        
                                        <div class="mb-4">
                                            <p class="text-muted">Select the branches this user can access. Hold down the Ctrl (Windows) or Command (Mac) button to select multiple options.</p>
                                            
                                            <label for="branches" class="form-label">Assigned Branches</label>
                                            <select class="select2 form-control select2-multiple" id="branches" name="branches[]" multiple="multiple" data-placeholder="Choose branches...">
                                                <?php foreach ($all_branches as $branch): ?>
                                                <option value="<?php echo $branch['branch_id']; ?>" <?php echo in_array($branch['branch_id'], $user_branches) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                                            <button type="submit" class="btn btn-primary">Save Branch Access</button>
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
    <script src="assets/libs/select2/js/select2.min.js"></script>
    <?php include 'layouts/vendor-scripts.php'; ?>
    <script src="assets/js/app.js"></script>
    
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
</body>
</html> 