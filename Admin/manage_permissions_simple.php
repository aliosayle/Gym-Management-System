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
$user_permissions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    // Get the user ID and permissions
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $error_message = "User ID is required";
    } else {
        $target_user_id = $_POST['user_id'];
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        
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
            
            // Define all permission keys for consistent processing
            $permission_keys = [
                'view_dashboard', 'manage_clients', 'add_client', 
                'edit_client', 'delete_client', 'manage_inventory', 
                'manage_invoices', 'use_pos', 'view_reports',
                'manage_packages', 'manage_companies', 'manage_branches', 
                'manage_users'
            ];
            
            // Convert to 0/1 values for each permission
            $permission_values = [];
            foreach ($permission_keys as $key) {
                $permission_values[$key] = isset($permissions[$key]) ? 1 : 0;
            }
            
            // Check if user_permissions table exists
            $check_table_query = "SHOW TABLES LIKE 'user_permissions'";
            $check_table_stmt = $pdo->prepare($check_table_query);
            $check_table_stmt->execute();
            $table_exists = $check_table_stmt->rowCount() > 0;
            
            if (!$table_exists) {
                // Create the table if it doesn't exist
                $create_table_query = "
                    CREATE TABLE user_permissions (
                        id INT(11) AUTO_INCREMENT PRIMARY KEY,
                        user_id INT(11) NOT NULL,
                        view_dashboard TINYINT(1) DEFAULT 0,
                        manage_clients TINYINT(1) DEFAULT 0,
                        add_client TINYINT(1) DEFAULT 0,
                        edit_client TINYINT(1) DEFAULT 0,
                        delete_client TINYINT(1) DEFAULT 0,
                        manage_inventory TINYINT(1) DEFAULT 0,
                        manage_invoices TINYINT(1) DEFAULT 0,
                        use_pos TINYINT(1) DEFAULT 0,
                        view_reports TINYINT(1) DEFAULT 0,
                        manage_packages TINYINT(1) DEFAULT 0,
                        manage_companies TINYINT(1) DEFAULT 0,
                        manage_branches TINYINT(1) DEFAULT 0,
                        manage_users TINYINT(1) DEFAULT 0,
                        UNIQUE KEY unique_user_id (user_id)
                    )
                ";
                $pdo->exec($create_table_query);
            }
            
            // Get column names from the table to ensure we're using the right names
            $columns_query = "SHOW COLUMNS FROM user_permissions";
            $columns_stmt = $pdo->prepare($columns_query);
            $columns_stmt->execute();
            $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Filter out id and user_id columns
            $columns = array_filter($columns, function($col) {
                return $col != 'id' && $col != 'user_id';
            });
            
            // Check if the user already has permissions
            $check_query = "SELECT * FROM user_permissions WHERE user_id = :user_id";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute(['user_id' => $target_user_id]);
            $exists = $check_stmt->rowCount() > 0;
            
            if ($exists) {
                // Update existing record
                $sql_parts = [];
                $params = ['user_id' => $target_user_id];
                
                foreach ($permission_keys as $key) {
                    // Check if the column exists in the table
                    if (in_array($key, $columns)) {
                        $sql_parts[] = "$key = :$key";
                        $params[$key] = $permission_values[$key];
                    }
                }
                
                $update_query = "UPDATE user_permissions SET " . implode(', ', $sql_parts) . " WHERE user_id = :user_id";
                
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute($params);
            } else {
                // Insert new record
                $columns_sql = ['user_id'];
                $values_sql = [':user_id'];
                $params = ['user_id' => $target_user_id];
                
                foreach ($permission_keys as $key) {
                    // Check if the column exists in the table
                    if (in_array($key, $columns)) {
                        $columns_sql[] = $key;
                        $values_sql[] = ":$key";
                        $params[$key] = $permission_values[$key];
                    }
                }
                
                $insert_query = "INSERT INTO user_permissions (" . implode(', ', $columns_sql) . ") VALUES (" . implode(', ', $values_sql) . ")";
                
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->execute($params);
            }
            
            // Commit transaction
            $pdo->commit();
            
            $success_message = "Permissions updated successfully for " . $target_user['username'];
            
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
        // Get user's permissions
        $permissions_query = "SELECT * FROM user_permissions WHERE user_id = :user_id";
        $permissions_stmt = $pdo->prepare($permissions_query);
        $permissions_stmt->execute(['user_id' => $target_user_id]);
        $permissions = $permissions_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($permissions) {
            // Store the permissions for display
            $user_permissions = $permissions;
        }
    }
}

// Check if user_permissions table exists
$table_query = "SHOW TABLES LIKE 'user_permissions'";
$table_stmt = $pdo->prepare($table_query);
$table_stmt->execute();
$table_exists = $table_stmt->rowCount() > 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Permissions</title>
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
            max-height: 300px;
            overflow: auto;
        }
        .permission-item {
            padding: 10px;
            margin-bottom: 5px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        .permission-item.enabled {
            background: #e7f3ff;
            border-color: #b0d7ff;
        }
        .permission-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .permission-section h5 {
            margin-bottom: 15px;
            color: #495057;
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
                        <div class="col-xl-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <?php echo $target_user ? 'Manage Permissions for ' . htmlspecialchars($target_user['username']) : 'Select a User'; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!$target_user): ?>
                                        <p>Please select a user from the <a href="users.php">Users page</a>.</p>
                                    <?php else: ?>
                                        <form action="manage_permissions_simple.php" method="post">
                                            <input type="hidden" name="user_id" value="<?php echo $target_user['id']; ?>">
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="permission-section">
                                                        <h5>Dashboard & Clients</h5>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['view_dashboard']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="view_dashboard" 
                                                                    name="permissions[view_dashboard]" value="1"
                                                                    <?php echo !empty($user_permissions['view_dashboard']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="view_dashboard">View Dashboard</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['manage_clients']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="manage_clients" 
                                                                    name="permissions[manage_clients]" value="1"
                                                                    <?php echo !empty($user_permissions['manage_clients']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="manage_clients">Manage Clients</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['add_client']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="add_client" 
                                                                    name="permissions[add_client]" value="1"
                                                                    <?php echo !empty($user_permissions['add_client']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="add_client">Add Client</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['edit_client']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="edit_client" 
                                                                    name="permissions[edit_client]" value="1"
                                                                    <?php echo !empty($user_permissions['edit_client']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="edit_client">Edit Client</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['delete_client']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="delete_client" 
                                                                    name="permissions[delete_client]" value="1"
                                                                    <?php echo !empty($user_permissions['delete_client']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="delete_client">Delete Client</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="permission-section">
                                                        <h5>Sales & Operations</h5>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['manage_inventory']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="manage_inventory" 
                                                                    name="permissions[manage_inventory]" value="1"
                                                                    <?php echo !empty($user_permissions['manage_inventory']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="manage_inventory">Manage Inventory</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['manage_invoices']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="manage_invoices" 
                                                                    name="permissions[manage_invoices]" value="1"
                                                                    <?php echo !empty($user_permissions['manage_invoices']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="manage_invoices">Manage Invoices</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['use_pos']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="use_pos" 
                                                                    name="permissions[use_pos]" value="1"
                                                                    <?php echo !empty($user_permissions['use_pos']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="use_pos">Use POS</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="permission-section">
                                                        <h5>Reports & Administration</h5>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['view_reports']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="view_reports" 
                                                                    name="permissions[view_reports]" value="1"
                                                                    <?php echo !empty($user_permissions['view_reports']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="view_reports">View Reports</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['manage_packages']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="manage_packages" 
                                                                    name="permissions[manage_packages]" value="1"
                                                                    <?php echo !empty($user_permissions['manage_packages']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="manage_packages">Manage Packages</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['manage_companies']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="manage_companies" 
                                                                    name="permissions[manage_companies]" value="1"
                                                                    <?php echo !empty($user_permissions['manage_companies']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="manage_companies">Manage Companies</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['manage_branches']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="manage_branches" 
                                                                    name="permissions[manage_branches]" value="1"
                                                                    <?php echo !empty($user_permissions['manage_branches']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="manage_branches">Manage Branches</label>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="permission-item <?php echo !empty($user_permissions['manage_users']) ? 'enabled' : ''; ?>">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="manage_users" 
                                                                    name="permissions[manage_users]" value="1"
                                                                    <?php echo !empty($user_permissions['manage_users']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="manage_users">Manage Users</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3 mt-4">
                                                <button type="submit" name="save_permissions" class="btn btn-primary">Save Permissions</button>
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
        // Simple animation for permission toggles
        $('.permission-item').on('click', function(e) {
            if (!$(e.target).is('input')) { // Don't toggle if clicking directly on the checkbox
                $(this).toggleClass('enabled');
                
                // Also toggle the checkbox inside
                const checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
        });
        
        // Update background when checkbox is clicked directly
        $('.form-check-input').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).closest('.permission-item').addClass('enabled');
            } else {
                $(this).closest('.permission-item').removeClass('enabled');
            }
        });
    });
    </script>
</body>
</html> 