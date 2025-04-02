<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once 'layouts/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check permission
$user_id = $_SESSION['id'];

$permission_query = "SELECT canedit FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$can_edit = (int)$permission_stmt->fetchColumn();

if ($can_edit !== 1) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You do not have permission to update inventory'
    ]);
    exit;
}

// Get parameters
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 0;
$notes = isset($_GET['notes']) ? trim($_GET['notes']) : 'Stock added manually';

// Validate input
if ($product_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid product ID'
    ]);
    exit;
}

if ($quantity <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Quantity must be greater than zero'
    ]);
    exit;
}

try {
    // Get product details
    $product_query = "SELECT name, price, quantity_in_stock FROM products WHERE product_id = :id";
    $product_stmt = $pdo->prepare($product_query);
    $product_stmt->execute(['id' => $product_id]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Update product quantity
    $new_quantity = $product['quantity_in_stock'] + $quantity;
    $update_query = "UPDATE products SET quantity_in_stock = :quantity WHERE product_id = :id";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->execute([
        'quantity' => $new_quantity,
        'id' => $product_id
    ]);
    
    // Add inventory transaction
    $transaction_query = "INSERT INTO inventory_transactions 
                          (product_id, quantity, transaction_type, notes, user_id, transaction_date) 
                          VALUES (:product_id, :quantity, :transaction_type, :notes, :user_id, NOW())";
    
    $transaction_stmt = $pdo->prepare($transaction_query);
    $transaction_stmt->execute([
        'product_id' => $product_id,
        'quantity' => $quantity,
        'transaction_type' => 'purchase',
        'notes' => $notes,
        'user_id' => $user_id
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => "Successfully added $quantity units of {$product['name']}",
        'new_quantity' => $new_quantity
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Inventory add stock error: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 