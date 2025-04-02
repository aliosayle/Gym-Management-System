<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include '../layouts/session.php';
include '../layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to continue']);
    exit;
}

// Check permissions
if (!isset($_SESSION['permissions']) || !in_array('manage_inventory', $_SESSION['permissions'])) {
    echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete inventory items']);
    exit;
}

// Check if item_id is provided
if (!isset($_POST['item_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Item ID is required']);
    exit;
}

$item_id = $_POST['item_id'];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // First check if the item exists
    $check_query = "SELECT * FROM inventory_items WHERE id = :item_id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->bindParam(':item_id', $item_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        exit;
    }
    
    // Delete transaction history
    $delete_transactions_query = "DELETE FROM inventory_transactions WHERE inventory_item_id = :item_id";
    $delete_transactions_stmt = $pdo->prepare($delete_transactions_query);
    $delete_transactions_stmt->bindParam(':item_id', $item_id);
    $delete_transactions_stmt->execute();
    
    // Delete inventory item
    $delete_item_query = "DELETE FROM inventory_items WHERE id = :item_id";
    $delete_item_stmt = $pdo->prepare($delete_item_query);
    $delete_item_stmt->bindParam(':item_id', $item_id);
    $delete_item_stmt->execute();
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Inventory item deleted successfully'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} 