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

// Check if item_id is provided
if (!isset($_POST['item_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Item ID is required']);
    exit;
}

$item_id = $_POST['item_id'];

try {
    // Get item details
    $query = "SELECT * FROM inventory_items WHERE id = :item_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        exit;
    }
    
    // Get transaction history
    $query = "SELECT * FROM inventory_transactions 
              WHERE inventory_item_id = :item_id 
              ORDER BY transaction_date DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'item' => $item,
        'transactions' => $transactions
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} 