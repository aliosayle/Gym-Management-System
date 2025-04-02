<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include '../layouts/session.php';
include '../layouts/config.php';

// Set appropriate content type for JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    die(json_encode(['error' => 'User not logged in']));
}

// Check if sale_id is provided
if (!isset($_GET['sale_id'])) {
    die(json_encode(['error' => 'Sale ID not provided']));
}

$sale_id = $_GET['sale_id'];

try {
    // Get sale details
    $query = "SELECT s.*, 
              GROUP_CONCAT(CONCAT(p.description, ' x', si.quantity) SEPARATOR ', ') as items_list
              FROM sales s
              LEFT JOIN sale_items si ON s.sale_id = si.sale_id
              LEFT JOIN products p ON si.product_id = p.product_id
              WHERE s.sale_id = :sale_id
              GROUP BY s.sale_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['sale_id' => $sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        die(json_encode(['error' => 'Sale not found']));
    }

    // Format the response
    $response = [
        'sale_id' => $sale['sale_id'],
        'date' => date('Y-m-d H:i:s', strtotime($sale['sale_date'])),
        'items' => $sale['items_list'] ?? 'No items',
        'total_amount' => number_format($sale['total_amount'], 2),
        'status' => $sale['status'] ?? 'Completed'
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
} 