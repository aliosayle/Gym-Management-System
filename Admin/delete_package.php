<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include 'layouts/session.php';
include 'layouts/config.php';
include 'layouts/check_permission.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check permissions
$can_manage_packages = has_permission('can_manage_packages', $pdo);
$can_delete_package = has_permission('can_delete_package', $pdo) || has_permission('can_manage_packages', $pdo);

// If user doesn't have permission to delete packages, redirect them
if (!$can_delete_package) {
    $_SESSION['delete_message'] = "You don't have permission to delete packages.";
    header("Location: packages.php");
    exit;
}

// Get package ID from URL parameter
$package_id = isset($_GET['id']) ? $_GET['id'] : null;
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : 
            (isset($_SESSION['selected_branch_id']) ? $_SESSION['selected_branch_id'] : 1);

// Validate package ID
if (!$package_id) {
    $_SESSION['delete_message'] = "No package ID provided.";
    header("Location: packages.php?branch_id=$branch_id");
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if package exists
    $check_query = "SELECT * FROM packages WHERE id = :id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['id' => $package_id]);
    $package = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        throw new Exception("Package not found.");
    }
    
    // Delete the package
    $delete_query = "DELETE FROM packages WHERE id = :id";
    $delete_stmt = $pdo->prepare($delete_query);
    $delete_stmt->execute(['id' => $package_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Set success message
    $_SESSION['delete_message'] = "Package deleted successfully.";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Set error message
    $_SESSION['delete_message'] = "Error deleting package: " . $e->getMessage();
}

// Redirect back to packages page
header("Location: packages.php?branch_id=$branch_id");
exit;
?> 