<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'layouts/session.php';
include 'layouts/config.php';

if (!$pdo) {
    die("Connection not established: " . $pdo->errorInfo());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user ID is set in session
if (!isset($_SESSION['id'])) {
    die("User ID is not set in session.");
}

// Fetch user permissions
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$permission_query = "SELECT canedit FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch(PDO::FETCH_ASSOC);

if (!$permissions || $permissions['canedit'] != 1) {
    die("You do not have permission to restock products.");
}

if (isset($_GET['id']) && isset($_GET['quantity'])) {
    $product_id = $_GET['id'];
    $quantity = (int)$_GET['quantity'];

    $update_query = "UPDATE products SET quantity_in_stock = quantity_in_stock + :quantity WHERE product_id = :product_id";
    $update_stmt = $pdo->prepare($update_query);
    if ($update_stmt->execute(['quantity' => $quantity, 'product_id' => $product_id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => implode(", ", $update_stmt->errorInfo())]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
}
?>