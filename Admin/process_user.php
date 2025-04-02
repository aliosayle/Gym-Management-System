<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();
include 'layouts/config.php';
include 'layouts/session.php';

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

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: users.php');
    exit;
}

// Check if action is set
if (!isset($_POST['action']) || empty($_POST['action'])) {
    $_SESSION['error'] = 'No action specified';
    header('Location: users.php');
    exit;
}

$action = $_POST['action'];

// Process based on action
switch ($action) {
    case 'update_user':
        processUpdateUser();
        break;
    case 'update_permissions':
        processUpdatePermissions();
        break;
    case 'update_branches':
        processUpdateBranches();
        break;
    default:
        $_SESSION['error'] = 'Invalid action';
        header('Location: users.php');
        exit;
}

// Function to process user update
function processUpdateUser() {
    global $pdo;
    
    // Check if user ID is set
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $_SESSION['error'] = 'User ID is required';
        header('Location: users.php');
        exit;
    }
    
    $user_id = $_POST['user_id'];
    
    // Validate username
    if (!isset($_POST['username']) || empty($_POST['username'])) {
        $_SESSION['error'] = 'Username is required';
        header('Location: edit_user.php?id=' . $user_id);
        exit;
    }
    
    // Validate email
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        $_SESSION['error'] = 'Email is required';
        header('Location: edit_user.php?id=' . $user_id);
        exit;
    }
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $isadmin = isset($_POST['isadmin']) ? 1 : 0;
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if the username is already in use by another user
        $check_username = "SELECT id FROM users WHERE username = :username AND id != :id";
        $stmt_username = $pdo->prepare($check_username);
        $stmt_username->execute(['username' => $username, 'id' => $user_id]);
        
        if ($stmt_username->rowCount() > 0) {
            $_SESSION['error'] = 'Username already exists';
            header('Location: edit_user.php?id=' . $user_id);
            exit;
        }
        
        // Check if the email is already in use by another user
        $check_email = "SELECT id FROM users WHERE useremail = :email AND id != :id";
        $stmt_email = $pdo->prepare($check_email);
        $stmt_email->execute(['email' => $email, 'id' => $user_id]);
        
        if ($stmt_email->rowCount() > 0) {
            $_SESSION['error'] = 'Email already exists';
            header('Location: edit_user.php?id=' . $user_id);
            exit;
        }
        
        // Update user
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            // Update with password
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET username = :username, useremail = :email, password = :password, isadmin = :isadmin WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'isadmin' => $isadmin,
                'id' => $user_id
            ]);
        } else {
            // Update without password
            $update_query = "UPDATE users SET username = :username, useremail = :email, isadmin = :isadmin WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                'username' => $username,
                'email' => $email,
                'isadmin' => $isadmin,
                'id' => $user_id
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = 'User updated successfully';
        header('Location: users.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = 'Error updating user: ' . $e->getMessage();
        header('Location: edit_user.php?id=' . $user_id);
        exit;
    }
}

// Function to process permissions update
function processUpdatePermissions() {
    global $pdo;
    
    // Check if user ID is set
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $_SESSION['error'] = 'User ID is required';
        header('Location: users.php');
        exit;
    }
    
    $user_id = $_POST['user_id'];
    
    // Get permissions from form
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Define all permission keys
    $permission_keys = [
        'view_dashboard', 'manage_clients', 'add_client', 'edit_client', 'delete_client',
        'manage_inventory', 'manage_invoices', 'use_pos', 'view_reports',
        'manage_packages', 'manage_companies', 'manage_branches', 'manage_users'
    ];
    
    // Set default values for all permissions (0 = off)
    $permission_values = [];
    foreach ($permission_keys as $key) {
        $permission_values[$key] = isset($permissions[$key]) ? 1 : 0;
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if user has existing permissions
        $check_query = "SELECT user_id FROM user_permissions WHERE user_id = :user_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute(['user_id' => $user_id]);
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing permissions
            $update_query = "UPDATE user_permissions SET 
                view_dashboard = :view_dashboard,
                manage_clients = :manage_clients,
                add_client = :add_client,
                edit_client = :edit_client,
                delete_client = :delete_client,
                manage_inventory = :manage_inventory,
                manage_invoices = :manage_invoices,
                use_pos = :use_pos,
                view_reports = :view_reports,
                manage_packages = :manage_packages,
                manage_companies = :manage_companies,
                manage_branches = :manage_branches,
                manage_users = :manage_users
                WHERE user_id = :user_id";
                
            $update_stmt = $pdo->prepare($update_query);
            $update_params = array_merge($permission_values, ['user_id' => $user_id]);
            $update_stmt->execute($update_params);
        } else {
            // Insert new permissions
            $insert_query = "INSERT INTO user_permissions (
                user_id, view_dashboard, manage_clients, add_client, edit_client, delete_client,
                manage_inventory, manage_invoices, use_pos, view_reports,
                manage_packages, manage_companies, manage_branches, manage_users
            ) VALUES (
                :user_id, :view_dashboard, :manage_clients, :add_client, :edit_client, :delete_client,
                :manage_inventory, :manage_invoices, :use_pos, :view_reports,
                :manage_packages, :manage_companies, :manage_branches, :manage_users
            )";
            
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_params = array_merge($permission_values, ['user_id' => $user_id]);
            $insert_stmt->execute($insert_params);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = 'User permissions updated successfully';
        header('Location: users.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = 'Error updating permissions: ' . $e->getMessage();
        header('Location: manage_permissions.php?id=' . $user_id);
        exit;
    }
}

// Function to process branch assignments
function processUpdateBranches() {
    global $pdo;
    
    // Check if user ID is set
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $_SESSION['error'] = 'User ID is required';
        header('Location: users.php');
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
        
        $_SESSION['success'] = 'User branch assignments updated successfully';
        header('Location: users.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = 'Error updating branch assignments: ' . $e->getMessage();
        header('Location: manage_branches.php?id=' . $user_id);
        exit;
    }
} 