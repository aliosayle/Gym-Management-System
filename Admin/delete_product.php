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
$permission_query = "SELECT candelete FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch(PDO::FETCH_ASSOC);

if (!$permissions || $permissions['candelete'] != 1) {
    die("You do not have permission to delete products.");
}

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    $delete_query = "DELETE FROM products WHERE product_id = :product_id";
    $delete_stmt = $pdo->prepare($delete_query);
    if ($delete_stmt->execute(['product_id' => $product_id])) {
        $_SESSION['delete_message'] = "Product deleted successfully";
    } else {
        $_SESSION['delete_message'] = "Error deleting product: " . implode(", ", $delete_stmt->errorInfo());
    }
    header("Location: products.php");
    exit();
} else {
    $_SESSION['delete_message'] = "Invalid product ID";
    header("Location: products.php");
    exit();
}
?>