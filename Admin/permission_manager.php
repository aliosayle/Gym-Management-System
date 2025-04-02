<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();
require 'layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: auth-login.php");
    exit;
}

// Check if user is admin
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($user['isadmin']) || $user['isadmin'] != 1) {
    header("Location: clients.php");
    exit;
}

// Check if user_permissions table exists, create if not
try {
    $checkTableQuery = "SHOW TABLES LIKE 'user_permissions'";
    $checkTableStmt = $pdo->prepare($checkTableQuery);
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() == 0) {
        // Table doesn't exist, create it
        $createTableQuery = "CREATE TABLE `user_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `can_view_dashboard` tinyint(1) DEFAULT 0,
            `can_manage_clients` tinyint(1) DEFAULT 0,
            `can_add_client` tinyint(1) DEFAULT 0,
            `can_edit_client` tinyint(1) DEFAULT 0,
            `can_delete_client` tinyint(1) DEFAULT 0,
            `can_manage_inventory` tinyint(1) DEFAULT 0,
            `can_manage_invoices` tinyint(1) DEFAULT 0,
            `can_use_pos` tinyint(1) DEFAULT 0,
            `can_view_reports` tinyint(1) DEFAULT 0,
            `can_manage_packages` tinyint(1) DEFAULT 0,
            `can_manage_companies` tinyint(1) DEFAULT 0,
            `can_manage_branches` tinyint(1) DEFAULT 0,
            `can_manage_users` tinyint(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($createTableQuery);
    }
} catch (PDOException $e) {
    $tableError = "Error checking/creating permissions table: " . $e->getMessage();
}

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    try {
        $userId = $_POST['user_id'];
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        
        // First check if the user exists
        $checkUserQuery = "SELECT username FROM users WHERE id = :id";
        $checkUserStmt = $pdo->prepare($checkUserQuery);
        $checkUserStmt->execute(['id' => $userId]);
        $userData = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            throw new Exception("User not found");
        }
        
        // Get table columns for permissions
        $columnsQuery = "SHOW COLUMNS FROM user_permissions";
        $columnsStmt = $pdo->prepare($columnsQuery);
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filter out id and user_id columns
        $permissionColumns = array_filter($columns, function($col) {
            return $col != 'id' && $col != 'user_id';
        });
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if user already has permissions
        $checkPermissionsQuery = "SELECT * FROM user_permissions WHERE user_id = :user_id";
        $checkPermissionsStmt = $pdo->prepare($checkPermissionsQuery);
        $checkPermissionsStmt->execute(['user_id' => $userId]);
        $hasPermissions = $checkPermissionsStmt->rowCount() > 0;
        
        // Prepare permission values
        $permissionValues = [];
        foreach ($permissionColumns as $column) {
            $permissionValues[$column] = isset($permissions[$column]) ? 1 : 0;
        }
        
        if ($hasPermissions) {
            // Update existing permissions
            $updateParts = [];
            $updateParams = ['user_id' => $userId];
            
            foreach ($permissionValues as $column => $value) {
                $updateParts[] = "$column = :$column";
                $updateParams[$column] = $value;
            }
            
            $updateQuery = "UPDATE user_permissions SET " . implode(', ', $updateParts) . " WHERE user_id = :user_id";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute($updateParams);
        } else {
            // Insert new permissions
            $columns = array_keys($permissionValues);
            $placeholders = array_map(function($col) { return ":$col"; }, $columns);
            
            $insertQuery = "INSERT INTO user_permissions (user_id, " . implode(', ', $columns) . ") 
                           VALUES (:user_id, " . implode(', ', $placeholders) . ")";
            
            $insertParams = array_merge(['user_id' => $userId], $permissionValues);
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute($insertParams);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $message = "Permissions updated successfully for " . $userData['username'];
        $messageType = 'success';
    } catch (Exception $e) {
        // Roll back transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get user ID from URL
if (isset($_GET['user_id'])) {
    $selectedUserId = $_GET['user_id'];
    
    // Get user data
    $userQuery = "SELECT id, username FROM users WHERE id = :id";
    $userStmt = $pdo->prepare($userQuery);
    $userStmt->execute(['id' => $selectedUserId]);
    $selectedUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user permissions
    if ($selectedUser) {
        $permissionsQuery = "SELECT * FROM user_permissions WHERE user_id = :user_id";
        $permissionsStmt = $pdo->prepare($permissionsQuery);
        $permissionsStmt->execute(['user_id' => $selectedUserId]);
        $userPermissions = $permissionsStmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $userPermissions = [];
    }
} else {
    $selectedUserId = null;
    $selectedUser = null;
    $userPermissions = [];
}

// Get all users
$usersQuery = "SELECT id, username FROM users ORDER BY username ASC";
$usersStmt = $pdo->prepare($usersQuery);
$usersStmt->execute();
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get permission columns
$columnsQuery = "SHOW COLUMNS FROM user_permissions";
$columnsStmt = $pdo->prepare($columnsQuery);
$columnsStmt->execute();
$columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

// Filter out id and user_id columns for permission list
$permissionColumns = array_filter($columns, function($col) {
    return $col['Field'] != 'id' && $col['Field'] != 'user_id';
});

// Group permissions by category
$permissionGroups = [
    'Dashboard & Clients' => [
        'can_view_dashboard' => 'View Dashboard',
        'can_manage_clients' => 'Manage Clients',
        'can_add_client' => 'Add Client',
        'can_edit_client' => 'Edit Client',
        'can_delete_client' => 'Delete Client'
    ],
    'Sales & Operations' => [
        'can_manage_inventory' => 'Manage Inventory',
        'can_manage_invoices' => 'Manage Invoices',
        'can_use_pos' => 'Use POS',
        'can_view_reports' => 'View Reports',
        'can_manage_packages' => 'Manage Packages'
    ],
    'Administration' => [
        'can_manage_companies' => 'Manage Companies',
        'can_manage_branches' => 'Manage Branches',
        'can_manage_users' => 'Manage Users'
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Permission Manager | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <?php include 'layouts/head-style.php'; ?>
    
    <style>
        .permission-item {
            padding: 10px;
            margin-bottom: 8px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        
        .permission-item:hover {
            background-color: #f8f9fa;
        }
        
        .permission-item.enabled {
            background-color: #e6f2ff;
            border-color: #99c2ff;
        }
        
        .debug-section {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .permission-group {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .permission-group-title {
            margin-bottom: 12px;
            font-weight: 600;
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
                    <!-- Start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">Permission Manager</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                                        <li class="breadcrumb-item active">Permission Manager</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End page title -->
                    
                    <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Select User</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <select id="userSelector" class="form-select">
                                                <option value="">Select a user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" <?php echo ($selectedUserId == $user['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" id="loadUserBtn" class="btn btn-primary">Load User</button>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="users.php" class="btn btn-secondary">Back to User List</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($selectedUser): ?>
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Manage Permissions for <?php echo htmlspecialchars($selectedUser['username']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <form action="permission_manager.php" method="post">
                                        <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>">
                                        
                                        <?php foreach ($permissionGroups as $groupName => $permissions): ?>
                                        <div class="permission-group">
                                            <h5 class="permission-group-title"><?php echo $groupName; ?></h5>
                                            
                                            <?php foreach ($permissions as $permKey => $permLabel): ?>
                                            <?php $isEnabled = !empty($userPermissions) && isset($userPermissions[$permKey]) && $userPermissions[$permKey] == 1; ?>
                                            <div class="permission-item <?php echo $isEnabled ? 'enabled' : ''; ?>">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" class="form-check-input permission-checkbox" 
                                                           id="<?php echo $permKey; ?>" 
                                                           name="permissions[<?php echo $permKey; ?>]" 
                                                           value="1" 
                                                           <?php echo $isEnabled ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="<?php echo $permKey; ?>">
                                                        <?php echo htmlspecialchars($permLabel); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="mt-4">
                                            <button type="submit" name="save_permissions" class="btn btn-success">Save Permissions</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php include 'layouts/footer.php'; ?>
        </div>
    </div>
    
    <?php include 'layouts/vendor-scripts.php'; ?>
    
    <script>
    $(document).ready(function() {
        // Handle user selection
        $('#loadUserBtn').on('click', function() {
            var userId = $('#userSelector').val();
            if (userId) {
                window.location.href = 'permission_manager.php?user_id=' + userId;
            }
        });
        
        // Make the whole permission item clickable
        $('.permission-item').on('click', function(e) {
            // Don't trigger if clicking directly on checkbox
            if (!$(e.target).is('input[type="checkbox"]')) {
                var checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked'));
                $(this).toggleClass('enabled', checkbox.prop('checked'));
            }
        });
        
        // Update selected class when checkbox changes
        $('.permission-checkbox').on('change', function() {
            $(this).closest('.permission-item').toggleClass('enabled', $(this).prop('checked'));
        });
    });
    </script>
</body>
</html> 