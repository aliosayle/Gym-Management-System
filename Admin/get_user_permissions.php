<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to perform this action']);
    exit;
}

// Check if the user has permission to manage users
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
include '../layouts/config.php';
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($user['isadmin']) || $user['isadmin'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'You do not have permission to manage users']);
    exit;
}

// Check if the user ID is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
    exit;
}

require_once('../includes/db.php');

try {
    // Check if the user exists
    $user_query = "SELECT id FROM users WHERE id = :id";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute(['id' => $_GET['user_id']]);
    
    if (!$user_stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    
    // Get the user permissions from the database
    $query = "SELECT * FROM user_permissions WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $_GET['user_id']]);
    $permissions = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return default permissions if none found
    if (!$permissions) {
        // Create default permissions
        $permissions = [
            'can_view_dashboard' => 0,
            'can_manage_clients' => 0,
            'can_add_client' => 0,
            'can_edit_client' => 0,
            'can_delete_client' => 0,
            'can_manage_inventory' => 0,
            'can_manage_invoices' => 0,
            'can_use_pos' => 0,
            'can_view_reports' => 0,
            'can_manage_packages' => 0,
            'can_manage_companies' => 0,
            'can_manage_branches' => 0,
            'can_manage_users' => 0
        ];
    }

    // Return the permissions
    echo json_encode(['status' => 'success', 'permissions' => $permissions]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while retrieving user permissions']);
} 