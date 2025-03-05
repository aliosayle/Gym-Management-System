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

// Fetch user permissions
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$permission_query = "SELECT candelete FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch(PDO::FETCH_ASSOC);

if ($permissions['candelete'] == 0) {
    $_SESSION['delete_message'] = "You do not have permission to delete clients.";
    header("Location: clients.php");
    exit;
}

$client_id = $_GET['id'] ?? null;
if (!$client_id) {
    $_SESSION['delete_message'] = "No client ID provided.";
    header("Location: clients.php");
    exit;
}

$delete_query = "DELETE FROM clients WHERE client_id = :client_id";
$delete_stmt = $pdo->prepare($delete_query);
if ($delete_stmt->execute(['client_id' => $client_id])) {
    $_SESSION['delete_message'] = "Client deleted successfully.";
} else {
    $_SESSION['delete_message'] = "Error deleting client: " . implode(", ", $delete_stmt->errorInfo());
}

header("Location: clients.php");
exit;
?>