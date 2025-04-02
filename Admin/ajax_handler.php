<?php
// Enable maximum error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Log all errors to a file for reference
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Helper function to debug variables
function debug_to_file($data, $label = '') {
    $output = date('Y-m-d H:i:s') . " - " . ($label ? $label . ": " : "");
    
    if (is_array($data) || is_object($data)) {
        $output .= print_r($data, true);
    } else {
        $output .= $data;
    }
    
    file_put_contents('debug.log', $output . "\n", FILE_APPEND);
}

// Start the session
session_start();
include 'layouts/config.php';

// Log the incoming request for debugging
debug_to_file($_POST, 'Incoming POST request');

// Check if user is logged in and is admin
if (!isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if user is admin
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($current_user['isadmin']) || $current_user['isadmin'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
    exit;
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if action is set
if (!isset($_POST['action']) || empty($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'];

// Process based on action
try {
    switch ($action) {
        case 'get_user':
            getUserData();
            break;
        case 'update_user':
            updateUser();
            break;
        case 'get_permissions':
            getPermissions();
            break;
        case 'update_permissions':
            updatePermissions();
            break;
        case 'get_user_branches':
            getUserBranches();
            break;
        case 'update_branches':
            updateBranches();
            break;
        case 'delete_user':
            deleteUser();
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

// Function to get user data
function getUserData() {
    global $pdo;
    
    // Check if user ID is set
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    $user_id = $_POST['user_id'];
    
    // Get user data
    $user_query = "SELECT * FROM users WHERE id = :id";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute(['id' => $user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Return user data
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $user]);
    exit;
}

// Function to update user
function updateUser() {
    global $pdo;
    
    debug_to_file("Starting updateUser function", "UPDATE_USER");
    
    try {
        // Check if user ID is set
        if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
            debug_to_file("Missing user_id", "UPDATE_USER_ERROR");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        $user_id = $_POST['user_id'];
        debug_to_file($user_id, "UPDATE_USER - User ID");
        
        // Validate username
        if (!isset($_POST['username']) || empty($_POST['username'])) {
            debug_to_file("Missing username", "UPDATE_USER_ERROR");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Username is required']);
            exit;
        }
        
        // Validate email
        if (!isset($_POST['email']) || empty($_POST['email'])) {
            debug_to_file("Missing email", "UPDATE_USER_ERROR");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $isadmin = isset($_POST['isadmin']) ? 1 : 0;
        
        debug_to_file([
            'username' => $username,
            'email' => $email,
            'isadmin' => $isadmin
        ], "UPDATE_USER - User data");
        
        // Begin transaction
        $pdo->beginTransaction();
        debug_to_file("Transaction started", "UPDATE_USER");
        
        // Check if the username is already in use by another user
        $check_username = "SELECT id FROM users WHERE username = :username AND id != :id";
        $stmt_username = $pdo->prepare($check_username);
        $stmt_username->execute(['username' => $username, 'id' => $user_id]);
        
        if ($stmt_username->rowCount() > 0) {
            debug_to_file("Username already exists", "UPDATE_USER_ERROR");
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }
        
        // Check if the email is already in use by another user
        $check_email = "SELECT id FROM users WHERE useremail = :email AND id != :id";
        $stmt_email = $pdo->prepare($check_email);
        $stmt_email->execute(['email' => $email, 'id' => $user_id]);
        
        if ($stmt_email->rowCount() > 0) {
            debug_to_file("Email already exists", "UPDATE_USER_ERROR");
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }
        
        // Update user
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            // Update with password
            debug_to_file("Updating with new password", "UPDATE_USER");
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET username = :username, useremail = :email, password = :password, isadmin = :isadmin WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_params = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'isadmin' => $isadmin,
                'id' => $user_id
            ];
            debug_to_file($update_query, "UPDATE_USER - SQL");
            debug_to_file($update_params, "UPDATE_USER - Params");
            $update_stmt->execute($update_params);
        } else {
            // Update without password
            debug_to_file("Updating without new password", "UPDATE_USER");
            $update_query = "UPDATE users SET username = :username, useremail = :email, isadmin = :isadmin WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_params = [
                'username' => $username,
                'email' => $email,
                'isadmin' => $isadmin,
                'id' => $user_id
            ];
            debug_to_file($update_query, "UPDATE_USER - SQL");
            debug_to_file($update_params, "UPDATE_USER - Params");
            $update_stmt->execute($update_params);
        }
        
        // Commit transaction
        $pdo->commit();
        debug_to_file("Transaction committed successfully", "UPDATE_USER");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        debug_to_file($e->getMessage(), "UPDATE_USER_EXCEPTION");
        debug_to_file($e->getTraceAsString(), "UPDATE_USER_EXCEPTION_TRACE");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Error updating user: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        exit;
    }
}

// Function to get user permissions
function getPermissions() {
    global $pdo;
    
    // Check if user ID is set
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    $user_id = $_POST['user_id'];
    
    // Get user data for the username
    $user_query = "SELECT username FROM users WHERE id = :id";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute(['id' => $user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Get user permissions
    $permissions_query = "SELECT * FROM user_permissions WHERE user_id = :user_id";
    $permissions_stmt = $pdo->prepare($permissions_query);
    $permissions_stmt->execute(['user_id' => $user_id]);
    $permissions = $permissions_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Set default permissions if none found
    if (!$permissions) {
        $permissions = [
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
    } else {
        // Handle discrepancies in column names (can_view_dashboard vs view_dashboard)
        $new_permissions = [];
        
        // Map from DB columns to expected properties
        $column_map = [
            'can_view_dashboard' => 'view_dashboard',
            'can_manage_clients' => 'manage_clients',
            'can_add_client' => 'add_client',
            'can_edit_client' => 'edit_client',
            'can_delete_client' => 'delete_client',
            'can_manage_inventory' => 'manage_inventory',
            'can_manage_invoices' => 'manage_invoices',
            'can_use_pos' => 'use_pos',
            'can_view_reports' => 'view_reports',
            'can_manage_packages' => 'manage_packages',
            'can_manage_companies' => 'manage_companies',
            'can_manage_branches' => 'manage_branches',
            'can_manage_users' => 'manage_users'
        ];
        
        foreach ($column_map as $db_col => $expected_prop) {
            // First check if the column exists with 'can_' prefix
            if (isset($permissions[$db_col])) {
                $new_permissions[$expected_prop] = (int)$permissions[$db_col];
            } 
            // Then check if it exists without prefix
            else if (isset($permissions[$expected_prop])) {
                $new_permissions[$expected_prop] = (int)$permissions[$expected_prop];
            } 
            // Default to 0 if neither exists
            else {
                $new_permissions[$expected_prop] = 0;
            }
        }
        
        $permissions = $new_permissions;
    }
    
    // Return permissions data
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'data' => $permissions,
        'username' => $user['username']
    ]);
    exit;
}

// Function to update permissions
function updatePermissions() {
    global $pdo;
    
    debug_to_file("Starting updatePermissions function", "UPDATE_PERMS");
    
    try {
        // Check if user ID is set
        if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
            debug_to_file("Missing user_id", "UPDATE_PERMS_ERROR");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        $user_id = $_POST['user_id'];
        debug_to_file("User ID: " . $user_id, "UPDATE_PERMS");
        
        // Get permissions from form
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        debug_to_file($permissions, "UPDATE_PERMS - Permissions from form");
        
        // Define all permission keys and their possible DB column names
        $permission_map = [
            'view_dashboard' => ['view_dashboard', 'can_view_dashboard'],
            'manage_clients' => ['manage_clients', 'can_manage_clients'],
            'add_client' => ['add_client', 'can_add_client'],
            'edit_client' => ['edit_client', 'can_edit_client'],
            'delete_client' => ['delete_client', 'can_delete_client'],
            'manage_inventory' => ['manage_inventory', 'can_manage_inventory'],
            'manage_invoices' => ['manage_invoices', 'can_manage_invoices'],
            'use_pos' => ['use_pos', 'can_use_pos'],
            'view_reports' => ['view_reports', 'can_view_reports'],
            'manage_packages' => ['manage_packages', 'can_manage_packages'],
            'manage_companies' => ['manage_companies', 'can_manage_companies'],
            'manage_branches' => ['manage_branches', 'can_manage_branches'],
            'manage_users' => ['manage_users', 'can_manage_users']
        ];
        
        // Set default values for all permissions (0 = off)
        $permission_values = [];
        foreach ($permission_map as $key => $variants) {
            $permission_values[$key] = isset($permissions[$key]) ? 1 : 0;
        }
        
        debug_to_file($permission_values, "UPDATE_PERMS - Final permission values");
        
        // Verify the user_permissions table exists
        $check_table_query = "SHOW TABLES LIKE 'user_permissions'";
        $check_table_stmt = $pdo->prepare($check_table_query);
        $check_table_stmt->execute();
        $table_exists = $check_table_stmt->rowCount() > 0;
        
        debug_to_file("user_permissions table exists: " . ($table_exists ? "Yes" : "No"), "UPDATE_PERMS");
        
        if (!$table_exists) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'The user_permissions table does not exist in the database'
            ]);
            exit;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        debug_to_file("Transaction started", "UPDATE_PERMS");
        
        // Check table structure to identify correct column names
        $table_query = "SHOW COLUMNS FROM user_permissions";
        $table_stmt = $pdo->prepare($table_query);
        $table_stmt->execute();
        $columns = $table_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        debug_to_file($columns, "UPDATE_PERMS - Table columns");
        
        // Create a map of form field names to actual DB column names
        $column_map = [];
        foreach ($permission_map as $form_key => $possible_columns) {
            foreach ($possible_columns as $col) {
                if (in_array($col, $columns)) {
                    $column_map[$form_key] = $col;
                    break;
                }
            }
            // If no match found, use the original form key
            if (!isset($column_map[$form_key])) {
                $column_map[$form_key] = $form_key;
            }
        }
        
        debug_to_file($column_map, "UPDATE_PERMS - Column mapping");
        
        // Check if user has existing permissions
        $check_query = "SELECT user_id FROM user_permissions WHERE user_id = :user_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute(['user_id' => $user_id]);
        $has_permissions = $check_stmt->rowCount() > 0;
        
        debug_to_file("User has existing permissions: " . ($has_permissions ? "Yes" : "No"), "UPDATE_PERMS");
        
        if ($has_permissions) {
            // Build update query dynamically based on actual columns
            $update_query = "UPDATE user_permissions SET ";
            $update_parts = [];
            $update_params = ['user_id' => $user_id];
            
            foreach ($permission_values as $form_key => $value) {
                if (isset($column_map[$form_key])) {
                    $db_column = $column_map[$form_key];
                    $update_parts[] = "$db_column = :$form_key";
                    $update_params[$form_key] = $value;
                }
            }
            
            $update_query .= implode(", ", $update_parts);
            $update_query .= " WHERE user_id = :user_id";
            
            debug_to_file($update_query, "UPDATE_PERMS - Update SQL");
            debug_to_file($update_params, "UPDATE_PERMS - Update params");
            
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute($update_params);
            debug_to_file("Update executed", "UPDATE_PERMS");
        } else {
            // Build insert query dynamically based on actual columns
            $insert_columns = ["user_id"];
            $insert_values = [":user_id"];
            $insert_params = ['user_id' => $user_id];
            
            foreach ($permission_values as $form_key => $value) {
                if (isset($column_map[$form_key])) {
                    $db_column = $column_map[$form_key];
                    $insert_columns[] = $db_column;
                    $insert_values[] = ":$form_key";
                    $insert_params[$form_key] = $value;
                }
            }
            
            $insert_query = "INSERT INTO user_permissions (";
            $insert_query .= implode(", ", $insert_columns);
            $insert_query .= ") VALUES (";
            $insert_query .= implode(", ", $insert_values);
            $insert_query .= ")";
            
            debug_to_file($insert_query, "UPDATE_PERMS - Insert SQL");
            debug_to_file($insert_params, "UPDATE_PERMS - Insert params");
            
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute($insert_params);
            debug_to_file("Insert executed", "UPDATE_PERMS");
        }
        
        // Commit transaction
        $pdo->commit();
        debug_to_file("Transaction committed", "UPDATE_PERMS");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User permissions updated successfully']);
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        debug_to_file($e->getMessage(), "UPDATE_PERMS_EXCEPTION");
        debug_to_file($e->getTraceAsString(), "UPDATE_PERMS_EXCEPTION_TRACE");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Error updating permissions: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        exit;
    }
}

// Function to get user branches
function getUserBranches() {
    global $pdo;
    
    debug_to_file("Starting getUserBranches function", "GET_BRANCHES");
    
    try {
        // Check if user ID is set
        if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
            debug_to_file("Missing user_id", "GET_BRANCHES_ERROR");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        $user_id = $_POST['user_id'];
        debug_to_file("User ID: " . $user_id, "GET_BRANCHES");
        
        // Get user data for the username
        $user_query = "SELECT username FROM users WHERE id = :id";
        debug_to_file($user_query, "GET_BRANCHES - User Query");
        $user_stmt = $pdo->prepare($user_query);
        $user_stmt->execute(['id' => $user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            debug_to_file("User not found", "GET_BRANCHES_ERROR");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        debug_to_file("User found: " . $user['username'], "GET_BRANCHES");
        
        // Verify the user_branches table exists
        $check_table_query = "SHOW TABLES LIKE 'user_branches'";
        $check_table_stmt = $pdo->prepare($check_table_query);
        $check_table_stmt->execute();
        $table_exists = $check_table_stmt->rowCount() > 0;
        
        debug_to_file("user_branches table exists: " . ($table_exists ? "Yes" : "No"), "GET_BRANCHES");
        
        if (!$table_exists) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'The user_branches table does not exist in the database'
            ]);
            exit;
        }
        
        // List available branches
        $branches_query = "SELECT * FROM branches";
        $branches_stmt = $pdo->prepare($branches_query);
        $branches_stmt->execute();
        $all_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        debug_to_file(count($all_branches) . " total branches in database", "GET_BRANCHES");
        debug_to_file($all_branches, "GET_BRANCHES - All branches");
        
        // Get user's branches
        $user_branches_query = "SELECT branch_id FROM user_branches WHERE user_id = :user_id";
        debug_to_file($user_branches_query, "GET_BRANCHES - SQL");
        $user_branches_stmt = $pdo->prepare($user_branches_query);
        $user_branches_stmt->execute(['user_id' => $user_id]);
        $user_branches = $user_branches_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        debug_to_file(count($user_branches) . " branches assigned to this user", "GET_BRANCHES");
        debug_to_file($user_branches, "GET_BRANCHES - User branches");
        
        // Return branches data
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'data' => $user_branches,
            'username' => $user['username'],
            'debug' => [
                'all_branches' => $all_branches,
                'assigned_branches' => $user_branches
            ]
        ]);
        exit;
    } catch (Exception $e) {
        debug_to_file($e->getMessage(), "GET_BRANCHES_EXCEPTION");
        debug_to_file($e->getTraceAsString(), "GET_BRANCHES_EXCEPTION_TRACE");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Error retrieving branches: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        exit;
    }
}

// Function to update branches
function updateBranches() {
    global $pdo;
    
    // Check if user ID is set
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    $user_id = $_POST['user_id'];
    
    // Get branches from form
    $branches = isset($_POST['branches']) ? $_POST['branches'] : [];
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete existing branch assignments
        $delete_query = "DELETE FROM user_branches WHERE user_id = :user_id";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute(['user_id' => $user_id]);
        
        // Insert new branch assignments
        if (!empty($branches)) {
            $insert_query = "INSERT INTO user_branches (user_id, branch_id) VALUES (:user_id, :branch_id)";
            $insert_stmt = $pdo->prepare($insert_query);
            
            foreach ($branches as $branch_id) {
                $insert_stmt->execute([
                    'user_id' => $user_id,
                    'branch_id' => $branch_id
                ]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User branch assignments updated successfully']);
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating branch assignments: ' . $e->getMessage()]);
        exit;
    }
}

// Function to delete user
function deleteUser() {
    global $pdo, $user_id;
    
    // Check if target user ID is set
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    $delete_user_id = $_POST['user_id'];
    
    // Make sure admin is not trying to delete themselves
    if ($delete_user_id == $user_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit;
    }
    
    // Check if user exists
    $check_query = "SELECT id, username FROM users WHERE id = :id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['id' => $delete_user_id]);
    $user = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete user permissions
        $delete_permissions = "DELETE FROM user_permissions WHERE user_id = :user_id";
        $permissions_stmt = $pdo->prepare($delete_permissions);
        $permissions_stmt->execute(['user_id' => $delete_user_id]);
        
        // Delete user branch assignments
        $delete_branches = "DELETE FROM user_branches WHERE user_id = :user_id";
        $branches_stmt = $pdo->prepare($delete_branches);
        $branches_stmt->execute(['user_id' => $delete_user_id]);
        
        // Delete user
        $delete_user = "DELETE FROM users WHERE id = :id";
        $user_stmt = $pdo->prepare($delete_user);
        $user_stmt->execute(['id' => $delete_user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User "' . $user['username'] . '" deleted successfully']);
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
        exit;
    }
}
?> 