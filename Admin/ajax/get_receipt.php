<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session parameters to match the main app
ini_set('session.cookie_path', '/');
ini_set('session.name', 'PHPSESSID');

// Start session
session_start();

// Include database connection - correct path
include_once '../layouts/config.php';

// Check if the connection exists
if (!isset($pdo)) {
    // Load the database configuration directly as fallback
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=gms', 'root', 'goldfish@2025');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Debug session info
error_log("Session in get_receipt.php: " . print_r($_SESSION, true));

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to view receipts'
    ]);
    exit;
}

// Ensure user_id is set for compatibility with the rest of the script
if (!isset($_SESSION['user_id']) && isset($_SESSION['id'])) {
    $_SESSION['user_id'] = $_SESSION['id'];
}

// Check if sale_id is provided
if (!isset($_POST['sale_id']) || empty($_POST['sale_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Sale ID is required'
    ]);
    exit;
}

$sale_id = $_POST['sale_id'];

try {
    // Get sale details
    $sale_query = "SELECT s.*, u.username 
                  FROM sales s 
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE s.sale_id = ?";
    
    $sale_stmt = $pdo->prepare($sale_query);
    $sale_stmt->execute([$sale_id]);
    
    if ($sale_stmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Sale not found'
        ]);
        exit;
    }
    
    $sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get sale items
    $items_query = "SELECT si.*, p.name 
                   FROM sale_items si 
                   LEFT JOIN products p ON si.product_id = p.product_id
                   WHERE si.sale_id = ?";
    
    $items_stmt = $pdo->prepare($items_query);
    $items_stmt->execute([$sale_id]);
    
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => [
            'sale_id' => $sale['sale_id'],
            'sale_date' => $sale['sale_date'],
            'total_amount' => $sale['total_amount'],
            'notes' => $sale['notes'] ?? '',
            'cashier' => $sale['username'],
            'items' => $items
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error retrieving receipt: ' . $e->getMessage()
    ]);
}
?> 