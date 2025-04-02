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
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
    exit;
}

require_once('../includes/db.php');

try {
    // Get the user data from the database
    $query = "SELECT id, username, useremail, isadmin FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    // Return the user data
    echo json_encode(['status' => 'success', 'user' => $user]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while retrieving user data']);
} 