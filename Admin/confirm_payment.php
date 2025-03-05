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

// Check if the user has permission to confirm payments
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$permission_query = "SELECT canedit FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch();

if ($permissions['canedit'] == 0) {
    echo "<script>alert('You do not have permission to confirm payments.'); window.location.href = 'clients.php';</script>";
    exit;
}

// Get the payment ID from the query parameter
$payment_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($payment_id) {
    // Update the payment status to 'completed'
    $update_query = "UPDATE payments SET payment_status = 'completed' WHERE payment_id = :payment_id";
    $update_stmt = $pdo->prepare($update_query);
    if ($update_stmt->execute(['payment_id' => $payment_id])) {
        $_SESSION['confirm_message'] = "Payment confirmed successfully.";
    } else {
        $_SESSION['confirm_message'] = "Error confirming payment: " . implode(", ", $update_stmt->errorInfo());
    }
} else {
    $_SESSION['confirm_message'] = "Invalid payment ID.";
}

// Redirect back to the clients page
header("Location: clients.php");
exit;
?>