<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once '../layouts/config.php';

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

// Get product ID
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Validate input
if ($product_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid product ID'
    ]);
    exit;
}

try {
    // Check if the product exists
    $product_query = "SELECT name FROM products WHERE product_id = :id";
    $product_stmt = $pdo->prepare($product_query);
    $product_stmt->execute(['id' => $product_id]);
    
    if (!$product_stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    // Get transaction history
    $transaction_query = "
        SELECT 
            it.transaction_id,
            it.product_id,
            it.quantity,
            it.transaction_type,
            it.transaction_date,
            it.notes,
            u.username AS created_by
        FROM inventory_transactions it
        LEFT JOIN users u ON it.user_id = u.id
        WHERE it.product_id = :product_id
        ORDER BY it.transaction_date DESC
    ";
    
    $transaction_stmt = $pdo->prepare($transaction_query);
    $transaction_stmt->execute(['product_id' => $product_id]);
    $transactions = $transaction_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'transactions' => $transactions
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    
    error_log("Error fetching product transactions: " . $e->getMessage());
}
?> 