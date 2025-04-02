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
    
    // Get the user's branch assignments from the database
    $query = "SELECT branch_id FROM user_branches WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $_GET['user_id']]);
    
    $branches = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $branches[] = $row['branch_id'];
    }

    // Return the branch assignments
    echo json_encode(['status' => 'success', 'branches' => $branches]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while retrieving user branch assignments']);
} 