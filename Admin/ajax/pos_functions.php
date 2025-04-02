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
error_log("Session in pos_functions.php: " . print_r($_SESSION, true));

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to perform this action'
    ]);
    exit;
}

// Ensure user_id is set for compatibility with the rest of the script
if (!isset($_SESSION['user_id']) && isset($_SESSION['id'])) {
    $_SESSION['user_id'] = $_SESSION['id'];
}

// Check if action is set
if (!isset($_POST['action'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No action specified'
    ]);
    exit;
}

// Process action
$action = $_POST['action'];

switch ($action) {
    case 'add_to_cart':
        $result = addToCart();
        break;
    case 'update_cart_item':
        $result = updateCartItem();
        break;
    case 'remove_from_cart':
        $result = removeFromCart();
        break;
    case 'clear_cart':
        $result = clearCart();
        break;
    case 'complete_sale':
        $result = completeSale();
        break;
    case 'get_product_details':
        $result = getProductDetails();
        break;
    case 'search_products':
        $result = searchProducts();
        break;
    case 'check_inventory':
        $result = checkInventory();
        break;
    default:
        $result = [
            'status' => 'error',
            'message' => 'Invalid action'
        ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
exit;

// Function to add product to cart
function addToCart() {
    global $pdo;
    
    // Validate product ID
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        return [
            'status' => 'error',
            'message' => 'Product ID is required'
        ];
    }
    
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate quantity
    if ($quantity <= 0) {
        return [
            'status' => 'error',
            'message' => 'Quantity must be greater than zero'
        ];
    }
    
    try {
        // Check if product exists and has enough stock
        $query = "SELECT * FROM products WHERE product_id = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$product_id]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'status' => 'error',
                'message' => 'Product not found'
            ];
        }
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check inventory
        if ($product['quantity_in_stock'] < $quantity) {
            return [
                'status' => 'error',
                'message' => 'Not enough stock available'
            ];
        }
        
        // Initialize cart if it doesn't exist
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if product already exists in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                // Update quantity
                $new_quantity = $item['quantity'] + $quantity;
                
                // Check if new quantity exceeds stock
                if ($new_quantity > $product['quantity_in_stock']) {
                    return [
                        'status' => 'error',
                        'message' => 'The requested quantity exceeds available stock'
                    ];
                }
                
                $item['quantity'] = $new_quantity;
                $item['max_quantity'] = $product['quantity_in_stock'];
                $found = true;
                break;
            }
        }
        
        // Add new item to cart if not found
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'quantity' => $quantity,
                'max_quantity' => $product['quantity_in_stock']
            ];
        }
        
        // Calculate cart total
        $cart_total = 0;
        $item_count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cart_total += $item['price'] * $item['quantity'];
            $item_count += $item['quantity'];
        }
        
        return [
            'status' => 'success',
            'message' => 'Product added to cart',
            'cart' => $_SESSION['cart'],
            'cart_total' => $cart_total,
            'item_count' => $item_count
        ];
        
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Error adding product to cart: ' . $e->getMessage()
        ];
    }
}

// Function to update cart item quantity
function updateCartItem() {
    // Validate product ID
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        return [
            'status' => 'error',
            'message' => 'Product ID is required'
        ];
    }
    
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate quantity
    if ($quantity <= 0) {
        return [
            'status' => 'error',
            'message' => 'Quantity must be greater than zero'
        ];
    }
    
    // Check if cart exists
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [
            'status' => 'error',
            'message' => 'Cart is empty'
        ];
    }
    
    try {
        global $pdo;
        
        // Check product stock
        $query = "SELECT quantity_in_stock FROM products WHERE product_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$product_id]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'status' => 'error',
                'message' => 'Product not found'
            ];
        }
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if requested quantity exceeds stock
        if ($quantity > $product['quantity_in_stock']) {
            return [
                'status' => 'error',
                'message' => 'The requested quantity exceeds available stock'
            ];
        }
        
        // Update cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] = $quantity;
                $item['max_quantity'] = $product['quantity_in_stock'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return [
                'status' => 'error',
                'message' => 'Product not found in cart'
            ];
        }
        
        // Calculate cart total
        $cart_total = 0;
        $item_count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cart_total += $item['price'] * $item['quantity'];
            $item_count += $item['quantity'];
        }
        
        return [
            'status' => 'success',
            'message' => 'Cart updated successfully',
            'cart' => $_SESSION['cart'],
            'cart_total' => $cart_total,
            'item_count' => $item_count
        ];
        
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Error updating cart: ' . $e->getMessage()
        ];
    }
}

// Function to remove item from cart
function removeFromCart() {
    // Validate product ID
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        return [
            'status' => 'error',
            'message' => 'Product ID is required'
        ];
    }
    
    $product_id = $_POST['product_id'];
    
    // Check if cart exists
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [
            'status' => 'error',
            'message' => 'Cart is empty'
        ];
    }
    
    // Remove item from cart
    $found = false;
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['product_id'] == $product_id) {
            unset($_SESSION['cart'][$index]);
            $found = true;
            break;
        }
    }
    
    // Reindex array
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    if (!$found) {
        return [
            'status' => 'error',
            'message' => 'Product not found in cart'
        ];
    }
    
    // Calculate cart total
    $cart_total = 0;
    $item_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_total += $item['price'] * $item['quantity'];
        $item_count += $item['quantity'];
    }
    
    return [
        'status' => 'success',
        'message' => 'Product removed from cart',
        'cart' => $_SESSION['cart'],
        'cart_total' => $cart_total,
        'item_count' => $item_count
    ];
}

// Function to clear cart
function clearCart() {
    $_SESSION['cart'] = [];
    
    return [
        'status' => 'success',
        'message' => 'Cart cleared successfully',
        'cart' => [],
        'cart_total' => 0,
        'item_count' => 0
    ];
}

// Function to complete sale
function completeSale() {
    global $pdo;
    
    // Check if cart is empty
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [
            'status' => 'error',
            'message' => 'Cart is empty'
        ];
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get current cart
        $cart = $_SESSION['cart'];
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($cart as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        // Set default values
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Insert sale record
        $sale_query = "INSERT INTO sales (user_id, total_amount, sale_date) VALUES (?, ?, NOW())";
        $sale_stmt = $pdo->prepare($sale_query);
        $sale_stmt->execute([$_SESSION['user_id'], $total_amount]);
        
        if ($sale_stmt->rowCount() === 0) {
            throw new PDOException("Failed to create sale record");
        }
        
        // Get the sale ID
        $sale_id = $pdo->lastInsertId();
        
        // Insert sale items
        foreach ($cart as $item) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            
            // Insert sale item
            $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $item_stmt = $pdo->prepare($item_query);
            $item_stmt->execute([$sale_id, $product_id, $quantity, $price]);
            
            if ($item_stmt->rowCount() === 0) {
                throw new PDOException("Failed to insert sale item");
            }
            
            // Update product inventory
            $update_query = "UPDATE products SET quantity_in_stock = quantity_in_stock - ? WHERE product_id = ? AND quantity_in_stock >= ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$quantity, $product_id, $quantity]);
            
            // Record transaction in inventory_transactions
            $transaction_query = "INSERT INTO inventory_transactions (product_id, quantity, transaction_type, transaction_date) VALUES (?, ?, 'sale', NOW())";
            $transaction_stmt = $pdo->prepare($transaction_query);
            $neg_quantity = -$quantity; // Negative quantity for sales
            $transaction_stmt->execute([$product_id, $neg_quantity]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        return [
            'status' => 'success',
            'message' => 'Sale completed successfully',
            'sale_id' => $sale_id,
            'total_amount' => $total_amount
        ];
        
    } catch (PDOException $e) {
        // Rollback transaction
        $pdo->rollBack();
        
        return [
            'status' => 'error',
            'message' => 'Error completing sale: ' . $e->getMessage()
        ];
    }
}

// Function to get product details
function getProductDetails() {
    global $pdo;
    
    // Validate product ID
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        return [
            'status' => 'error',
            'message' => 'Product ID is required'
        ];
    }
    
    $product_id = $_POST['product_id'];
    
    try {
        // Get product details
        $query = "SELECT * FROM products WHERE product_id = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$product_id]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'status' => 'error',
                'message' => 'Product not found'
            ];
        }
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'product' => $product
        ];
        
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Error getting product details: ' . $e->getMessage()
        ];
    }
}

// Function to search products
function searchProducts() {
    global $pdo;
    
    // Get search term
    $search = isset($_POST['search']) ? $_POST['search'] : '';
    
    try {
        // Search products
        $query = "SELECT * FROM products 
                 WHERE name LIKE ? OR description LIKE ?
                 ORDER BY name
                 LIMIT 20";
        
        $search_param = "%$search%";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$search_param, $search_param]);
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'products' => $products
        ];
        
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Error searching products: ' . $e->getMessage()
        ];
    }
}

// Function to check inventory
function checkInventory() {
    global $pdo;
    
    // Validate product ID
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        return [
            'status' => 'error',
            'message' => 'Product ID is required'
        ];
    }
    
    $product_id = $_POST['product_id'];
    
    try {
        // Get product quantity
        $query = "SELECT quantity_in_stock FROM products WHERE product_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$product_id]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'status' => 'error',
                'message' => 'Product not found'
            ];
        }
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'quantity' => $product['quantity_in_stock']
        ];
        
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Error checking inventory: ' . $e->getMessage()
        ];
    }
}