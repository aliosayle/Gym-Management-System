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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_to_cart') {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        // Fetch product details
        $product_query = "SELECT * FROM products WHERE product_id = :product_id";
        $product_stmt = $pdo->prepare($product_query);
        $product_stmt->execute(['product_id' => $product_id]);
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['quantity_in_stock'] >= $quantity) {
            // Add product to cart
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
            echo json_encode(['status' => 'success', 'message' => 'Product added to cart', 'cart' => $_SESSION['cart']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient stock for the selected product']);
        }
    } elseif ($_POST['action'] == 'remove_from_cart') {
        $product_id = $_POST['product_id'];
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['product_id'] == $product_id) {
                    unset($_SESSION['cart'][$key]);
                    break;
                }
            }
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex the array
            echo json_encode(['status' => 'success', 'message' => 'Product removed from cart', 'cart' => $_SESSION['cart']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        }
    } elseif ($_POST['action'] == 'clear_cart') {
        unset($_SESSION['cart']);
        echo json_encode(['status' => 'success', 'message' => 'Cart cleared', 'cart' => []]);
    }
    exit();
}
?>