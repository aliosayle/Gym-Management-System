<?php
// Enable maximum error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start the session
session_start();
include 'layouts/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['id'])) {
    die("Authentication required. Please <a href='auth-login.php'>login</a> first.");
}

$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($current_user['isadmin']) || $current_user['isadmin'] != 1) {
    die("Admin privileges required. You don't have permission to access this page.");
}

// Process form submission
$success_message = '';
$error_message = '';
$target_user = null;
$user_branches = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_branches'])) {
    // Get the user ID and branches
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $error_message = "User ID is required";
    } else {
        $target_user_id = $_POST['user_id'];
        $branches = isset($_POST['branches']) ? $_POST['branches'] : [];
        
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Check if the user exists
            $user_query = "SELECT username FROM users WHERE id = :id";
            $user_stmt = $pdo->prepare($user_query);
            $user_stmt->execute(['id' => $target_user_id]);
            $target_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$target_user) {
                throw new Exception("User not found with ID: $target_user_id");
            }
            
            // Delete existing branch assignments
            $delete_query = "DELETE FROM user_branches WHERE user_id = :user_id";
            $delete_stmt = $pdo->prepare($delete_query);
            $delete_stmt->execute(['user_id' => $target_user_id]);
            
            // Insert new branch assignments
            if (!empty($branches)) {
                $insert_query = "INSERT INTO user_branches (user_id, branch_id) VALUES (:user_id, :branch_id)";
                $insert_stmt = $pdo->prepare($insert_query);
                
                foreach ($branches as $branch_id) {
                    $insert_stmt->execute([
                        'user_id' => $target_user_id,
                        'branch_id' => $branch_id
                    ]);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            $success_message = "Branch assignments updated successfully for " . $target_user['username'];
            
            // Update the user ID for the form display
            $_GET['id'] = $target_user_id;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get user data for display
if (isset($_GET['id'])) {
    $target_user_id = $_GET['id'];
    
    // Get user data
    $user_query = "SELECT id, username FROM users WHERE id = :id";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute(['id' => $target_user_id]);
    $target_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        $error_message = "User not found";
    } else {
        // Get user's branches
        $branches_query = "SELECT branch_id FROM user_branches WHERE user_id = :user_id";
        $branches_stmt = $pdo->prepare($branches_query);
        $branches_stmt->execute(['user_id' => $target_user_id]);
        $user_branches = $branches_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Get all branches for the form
$all_branches_query = "SELECT * FROM branches ORDER BY id ASC";
$all_branches_stmt = $pdo->prepare($all_branches_query);
$all_branches_stmt->execute();
$all_branches = $all_branches_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user_branches table exists
$table_query = "SHOW TABLES LIKE 'user_branches'";
$table_stmt = $pdo->prepare($table_query);
$table_stmt->execute();
$table_exists = $table_stmt->rowCount() > 0;

// Check if branches table exists
$branches_table_query = "SHOW TABLES LIKE 'branches'";
$branches_table_stmt = $pdo->prepare($branches_table_query);
$branches_table_stmt->execute();
$branches_table_exists = $branches_table_stmt->rowCount() > 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Branches</title>
    <?php include 'layouts/head.php'; ?>
    <?php include 'layouts/head-style.php'; ?>
    <style>
        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            font-family: monospace;
        }
        .branch-item {
            padding: 10px;
            margin-bottom: 5px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        .branch-item.selected {
            background: #e7f3ff;
            border-color: #b0d7ff;
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
                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Main content -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <?php echo $target_user ? 'Manage Branches for ' . htmlspecialchars($target_user['username']) : 'Select a User'; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!$target_user): ?>
                                        <p>Please select a user from the <a href="users.php">Users page</a>.</p>
                                    <?php elseif (empty($all_branches)): ?>
                                        <p>No branches are available in the system. Please add branches first.</p>
                                    <?php else: ?>
                                        <form action="manage_branches_simple.php" method="post">
                                            <input type="hidden" name="user_id" value="<?php echo $target_user['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Select Branches</label>
                                                <p class="text-muted small">Select all branches this user should have access to.</p>
                                                
                                                <div class="branch-options">
                                                    <?php foreach ($all_branches as $branch): ?>
                                                    <div class="branch-item <?php echo in_array($branch['id'], $user_branches) ? 'selected' : ''; ?>">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                name="branches[]" 
                                                                id="branch_<?php echo $branch['id']; ?>" 
                                                                value="<?php echo $branch['id']; ?>"
                                                                <?php echo in_array($branch['id'], $user_branches) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="branch_<?php echo $branch['id']; ?>">
                                                                <?php echo htmlspecialchars($branch['name'] ?? $branch['branch_name'] ?? 'Branch '.$branch['id']); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <button type="submit" name="save_branches" class="btn btn-primary">Save Branch Access</button>
                                                <a href="users.php" class="btn btn-secondary">Back to Users</a>
                                            </div>
                                        </form>
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
    
    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <?php include 'layouts/vendor-scripts.php'; ?>
    
    <script>
    $(document).ready(function() {
        // Simple animation for branch selection
        $('.branch-item').on('click', function() {
            $(this).toggleClass('selected');
            
            // Also toggle the checkbox inside
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
        });
        
        // Ensure clicking the checkbox doesn't trigger the branch-item click again
        $('.form-check-input').on('click', function(e) {
            e.stopPropagation();
            $(this).closest('.branch-item').toggleClass('selected');
        });
    });
    </script>
</body>
</html> 