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

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'User ID is required';
    header('Location: users.php');
    exit;
}

$delete_user_id = $_GET['id'];

// Make sure admin is not trying to delete themselves
if ($delete_user_id == $user_id) {
    $_SESSION['error'] = 'You cannot delete your own account';
    header('Location: users.php');
    exit;
}

// Check if user exists
$check_query = "SELECT id FROM users WHERE id = :id";
$check_stmt = $pdo->prepare($check_query);
$check_stmt->execute(['id' => $delete_user_id]);
$user_exists = $check_stmt->fetch();

if (!$user_exists) {
    $_SESSION['error'] = 'User not found';
    header('Location: users.php');
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
    $delete_branches = "DELETE FROM user_branch WHERE user_id = :user_id";
    $branches_stmt = $pdo->prepare($delete_branches);
    $branches_stmt->execute(['user_id' => $delete_user_id]);
    
    // Delete user
    $delete_user = "DELETE FROM users WHERE id = :id";
    $user_stmt = $pdo->prepare($delete_user);
    $user_stmt->execute(['id' => $delete_user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success'] = 'User deleted successfully';
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $_SESSION['error'] = 'Error deleting user: ' . $e->getMessage();
}

header('Location: users.php');
exit;
?> 