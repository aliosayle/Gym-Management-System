<?php
// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include database config
session_start();
require_once 'layouts/config.php';

// Check user authentication
if (!isset($_SESSION['id'])) {
    die("<p>You must be logged in. <a href='auth-login.php'>Login here</a></p>");
}

// Check admin privileges
try {
    $check_admin = $pdo->prepare("SELECT isadmin FROM users WHERE id = ?");
    $check_admin->execute([$_SESSION['id']]);
    $is_admin = $check_admin->fetchColumn();
    
    if (!$is_admin) {
        die("<p>Admin privileges required.</p>");
    }
} catch (PDOException $e) {
    die("<p>Database error: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Get user data
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_data = null;

if ($user_id > 0) {
    try {
        $user_stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_data) {
            die("<p>User not found. <a href='users.php'>Return to users list</a></p>");
        }
    } catch (PDOException $e) {
        die("<p>Error fetching user: " . htmlspecialchars($e->getMessage()) . "</p>");
    }
}

// Define standard permissions
$permissions_list = [
    'view_dashboard' => 'View Dashboard',
    'manage_clients' => 'Manage Clients',
    'add_client' => 'Add Client',
    'edit_client' => 'Edit Client',
    'delete_client' => 'Delete Client',
    'manage_inventory' => 'Manage Inventory',
    'manage_invoices' => 'Manage Invoices',
    'use_pos' => 'Use POS',
    'view_reports' => 'View Reports',
    'manage_packages' => 'Manage Packages',
    'manage_companies' => 'Manage Companies',
    'manage_branches' => 'Manage Branches',
    'manage_users' => 'Manage Users'
];

// Create user_permissions table if it doesn't exist
try {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE";
    
    // Add permission columns
    foreach ($permissions_list as $permission => $label) {
        $create_table_sql .= ", $permission TINYINT(1) DEFAULT 0";
    }
    
    $create_table_sql .= ")";
    $pdo->exec($create_table_sql);
} catch (PDOException $e) {
    die("<p>Error creating permissions table: " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Process form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    try {
        $pdo->beginTransaction();
        
        // Check if the user already has permissions
        $check_stmt = $pdo->prepare("SELECT id FROM user_permissions WHERE user_id = ?");
        $check_stmt->execute([$user_id]);
        $has_permissions = $check_stmt->fetch();
        
        // Prepare values array for SQL
        $values = ['user_id' => $user_id];
        
        // Set permission values from form
        foreach ($permissions_list as $permission => $label) {
            $values[$permission] = isset($_POST['permissions'][$permission]) ? 1 : 0;
        }
        
        if ($has_permissions) {
            // Update existing permissions
            $update_sql = "UPDATE user_permissions SET ";
            $update_parts = [];
            
            foreach ($permissions_list as $permission => $label) {
                $update_parts[] = "$permission = :$permission";
            }
            
            $update_sql .= implode(', ', $update_parts) . " WHERE user_id = :user_id";
            
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($values);
        } else {
            // Insert new permissions
            $insert_sql = "INSERT INTO user_permissions (user_id, " . implode(', ', array_keys($permissions_list)) . ")
                          VALUES (:user_id, :" . implode(', :', array_keys($permissions_list)) . ")";
            
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute($values);
        }
        
        $pdo->commit();
        $message = '<div style="color:green;margin:10px 0;">Permissions updated successfully</div>';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = '<div style="color:red;margin:10px 0;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Get user's current permissions
$user_permissions = [];
if ($user_id > 0) {
    try {
        $perm_stmt = $pdo->prepare("SELECT * FROM user_permissions WHERE user_id = ?");
        $perm_stmt->execute([$user_id]);
        $user_permissions = $perm_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $message .= '<div style="color:red;margin:10px 0;">Error fetching permissions: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage User Permissions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; }
        .card { border: 1px solid #ddd; border-radius: 4px; padding: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        .permissions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .permission-item { padding: 10px; border: 1px solid #eee; border-radius: 4px; }
        .permission-item:hover { background-color: #f5f5f5; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        .debug-info { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; margin-top: 20px; }
        .section { margin-bottom: 25px; }
        .section-title { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage User Permissions</h1>
        
        <?php echo $message; ?>
        
        <div class="card">
            <h2><?php echo $user_data ? 'Permissions for ' . htmlspecialchars($user_data['username']) : 'Select a User'; ?></h2>
            
            <?php if (!$user_data): ?>
                <p>Please select a user from the <a href="users.php">users list</a>.</p>
            <?php else: ?>
                <form method="post">
                    <div class="section">
                        <h3 class="section-title">Dashboard & Clients</h3>
                        <div class="permissions-grid">
                            <?php 
                            $client_permissions = [
                                'view_dashboard', 'manage_clients', 'add_client', 'edit_client', 'delete_client'
                            ];
                            foreach ($client_permissions as $permission): 
                            ?>
                                <div class="permission-item">
                                    <label>
                                        <input type="checkbox" name="permissions[<?php echo $permission; ?>]" value="1"
                                            <?php echo !empty($user_permissions[$permission]) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($permissions_list[$permission]); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3 class="section-title">Sales & Operations</h3>
                        <div class="permissions-grid">
                            <?php 
                            $sales_permissions = [
                                'manage_inventory', 'manage_invoices', 'use_pos', 'view_reports'
                            ];
                            foreach ($sales_permissions as $permission): 
                            ?>
                                <div class="permission-item">
                                    <label>
                                        <input type="checkbox" name="permissions[<?php echo $permission; ?>]" value="1"
                                            <?php echo !empty($user_permissions[$permission]) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($permissions_list[$permission]); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3 class="section-title">Administration</h3>
                        <div class="permissions-grid">
                            <?php 
                            $admin_permissions = [
                                'manage_packages', 'manage_companies', 'manage_branches', 'manage_users'
                            ];
                            foreach ($admin_permissions as $permission): 
                            ?>
                                <div class="permission-item">
                                    <label>
                                        <input type="checkbox" name="permissions[<?php echo $permission; ?>]" value="1"
                                            <?php echo !empty($user_permissions[$permission]) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($permissions_list[$permission]); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="save_permissions">Save Permissions</button>
                        <a href="users.php" style="margin-left: 10px; text-decoration: none;">
                            <button type="button" class="btn-secondary">Back to Users</button>
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 