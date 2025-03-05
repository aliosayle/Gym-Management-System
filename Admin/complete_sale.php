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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'complete_sale') {
    // Complete the sale
    $cart = json_decode($_POST['cart'], true);
    $total_amount = 0;
    foreach ($cart as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Insert sale record
    $sale_query = "INSERT INTO sales (user_id, total_amount) VALUES (:user_id, :total_amount)";
    $sale_stmt = $pdo->prepare($sale_query);
    $sale_stmt->execute(['user_id' => $_SESSION['id'], 'total_amount' => $total_amount]);
    $sale_id = $pdo->lastInsertId();

    // Insert sale items and update product quantities
    foreach ($cart as $item) {
        $sale_item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (:sale_id, :product_id, :quantity, :price)";
        $sale_item_stmt = $pdo->prepare($sale_item_query);
        $sale_item_stmt->execute([
            'sale_id' => $sale_id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price']
        ]);

        $update_query = "UPDATE products SET quantity_in_stock = quantity_in_stock - :quantity WHERE product_id = :product_id";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute(['quantity' => $item['quantity'], 'product_id' => $item['product_id']]);
    }

    // Clear the cart
    unset($_SESSION['cart']);
    echo json_encode(['status' => 'success', 'message' => 'Sale completed successfully']);
    exit();
}
?>