<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

if (!$pdo) {
    die("Connection not established: " . $pdo->errorInfo());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize the cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get the selected branch
$selected_branch_id = null;

if (isset($_POST['branch_id'])) {
    $selected_branch_id = $_POST['branch_id'];
} elseif (isset($_GET['branch_id'])) {
    $selected_branch_id = $_GET['branch_id'];
} elseif (isset($_SESSION['selected_branch_id'])) {
    $selected_branch_id = $_SESSION['selected_branch_id'];
} elseif (isset($user_branches) && !empty($user_branches)) {
    $selected_branch_id = $user_branches[0]['id'];
}

// Ensure we have a valid branch ID
if (empty($selected_branch_id) && isset($user_branches) && !empty($user_branches)) {
    $selected_branch_id = $user_branches[0]['id'];
} elseif (empty($selected_branch_id)) {
    $selected_branch_id = 1; // Default to branch ID 1 if nothing else is available
}

// Store selected branch in session
$_SESSION['selected_branch_id'] = $selected_branch_id;

// Get branch name for display
$branch_name = "";
if (!empty($selected_branch_id)) {
    try {
        $branch_query = "SELECT name FROM branches WHERE id = ?";
        $branch_stmt = $pdo->prepare($branch_query);
        $branch_stmt->execute([$selected_branch_id]);
        $branch = $branch_stmt->fetch(PDO::FETCH_ASSOC);
        if ($branch) {
            $branch_name = $branch['name'];
            $_SESSION['selected_branch_name'] = $branch_name;
        }
    } catch (PDOException $e) {
        error_log("Error fetching branch name: " . $e->getMessage());
    }
}

// Display flash messages
if (isset($_SESSION['pos_message'])) {
    $alert_type = strpos($_SESSION['pos_message'], 'successfully') !== false ? 'success' : 'danger';
    echo "<div class='alert alert-$alert_type alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['pos_message']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    unset($_SESSION['pos_message']); // Unset after displaying the message
}

// Fetch products from the products table filtered by branch_id
$products = [];
try {
    $query = "SELECT product_id, name, description, price, quantity_in_stock as quantity 
              FROM products 
              WHERE branch_id = :branch_id
              ORDER BY name ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['branch_id' => $selected_branch_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
}

// Calculate current cart totals
$cart_total = 0;
$cart_items = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_total += $item['price'] * $item['quantity'];
        $cart_items += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>POS System | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
    <style>
        /* Main POS styling */
        .pos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .pos-products {
            flex: 1;
            min-width: 350px;
        }
        
        .pos-cart {
            flex: 1;
            min-width: 350px;
        }
        
        /* Product card styles */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .product-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
            height: 38px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-description {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 10px;
            flex-grow: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        .product-price {
            color: #2563eb;
            font-weight: 700;
        }
        
        .product-stock {
            font-size: 12px;
            color: #6b7280;
        }
        
        .product-stock.low {
            color: #ef4444;
        }
        
        /* Cart styles */
        .cart-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 0;
        }
        
        .cart-total {
            font-size: 18px;
            font-weight: 700;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
        }
        
        .quantity-input {
            width: 60px;
        }
        
        /* Loading overlay */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        #loading-overlay img {
            width: 80px;
            height: 80px;
        }
        
        /* Search bar */
        .search-container {
            margin-bottom: 15px;
        }
        
        /* Barcode input */
        #barcode-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #3b82f6;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        /* Tabs */
        .category-tabs .nav-link {
            padding: 8px 15px;
            border-radius: 20px;
            margin-right: 8px;
            font-size: 14px;
            color: #4b5563;
        }
        
        .category-tabs .nav-link.active {
            background-color: #3b82f6;
            color: white;
        }
        
        /* Receipt */
        .receipt {
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
            font-family: 'Courier New', monospace;
            line-height: 1.4;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 10px;
        }
        
        .receipt-items {
            margin-bottom: 15px;
        }
        
        .receipt-total {
            border-top: 1px dashed #e2e8f0;
            padding-top: 10px;
            font-weight: bold;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 10px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <?php include 'layouts/body.php'; ?>

    <div id="layout-wrapper">
        <?php include 'layouts/menu.php'; ?>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- ========== Start Page Title ========== -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">
                                    POS System
                                    <?php if (!empty($branch_name)): ?>
                                        - <?php echo htmlspecialchars($branch_name); ?>
                                    <?php endif; ?>
                                </h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Point of Sale</a></li>
                                        <li class="breadcrumb-item active">POS System</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ========== End Page Title ========== -->

                    <!-- Alert area for messages -->
                    <div id="alert-container"></div>

                    <!-- POS Container -->
                    <div class="pos-container">
                        <!-- Product selection section -->
                        <div class="card pos-products">
                                <div class="card-body">
                                <h4 class="card-title">Products</h4>
                                
                                <!-- Barcode scanner input -->
                                        <div class="mb-3">
                                    <input type="text" id="barcode-input" class="form-control" placeholder="Scan barcode or enter product code..." autocomplete="off">
                                </div>
                                
                                <!-- Search and filter -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="search-container">
                                            <input type="text" id="product-search" class="form-control" placeholder="Search products...">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Product grid display -->
                                <div class="product-grid" id="product-grid">
                                    <?php if (empty($products)): ?>
                                    <div class="col-12 text-center">
                                        <p>No products available. Please add products to the inventory.</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                        <div class="product-card" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                            <div class="product-image">
                                                <i class="fas fa-box fa-3x text-gray-300"></i>
                                            </div>
                                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></div>
                                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                            <div class="product-stock <?php echo $product['quantity'] <= 5 ? 'low' : ''; ?>">
                                                Stock: <?php echo $product['quantity']; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Cart section -->
                        <div class="card pos-cart">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Shopping Cart</h4>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Qty</th>
                                                <th>Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="cart-items">
                                            <tr id="empty-cart-message">
                                                <td colspan="5" class="text-center">Cart is empty</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="cart-total d-flex justify-content-between">
                                    <span>Total:</span>
                                    <span id="cart-total">$0.00</span>
                                </div>
                                
                                <div class="d-flex mt-4">
                                    <button id="clear-cart" class="btn btn-warning me-2" disabled>Clear Cart</button>
                                    <button id="checkout-btn" class="btn btn-success flex-grow-1" disabled>Checkout</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'layouts/footer.php'; ?>
        </div>
    </div>

    <!-- Loading overlay -->
    <div id="loading-overlay">
        <img src="assets/images/loading.gif" alt="Loading...">
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkout-modal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkoutModalLabel">Complete Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="checkout-form">
                        <div class="mb-3">
                            <label for="sale-notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="sale-notes" rows="2" placeholder="Optional notes about the sale"></textarea>
                        </div>
                        
                        <div class="cart-summary">
                            <h6>Order Summary</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th class="text-end">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modal-cart-items">
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2">Total</th>
                                            <th class="text-end" id="modal-cart-total">$0.00</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="complete-sale-btn">Complete Sale</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receipt-modal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="receipt" id="receipt-content">
                        <!-- Receipt content will be populated dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="print-receipt-btn">Print Receipt</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'layouts/vendor-scripts.php'; ?>
    
    <!-- Required js -->
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    
    <script>
    $(document).ready(function() {
            // Initialize cart
            let cart = <?php echo json_encode($_SESSION['cart'] ?? []); ?>;
            updateCartDisplay();
            
            // Product card click to add to cart
            $(document).on('click', '.product-card', function() {
                const productId = $(this).data('id');
                addToCart(productId, 1);
            });
            
            // Barcode input handling
            $('#barcode-input').on('keydown', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    const barcode = $(this).val().trim();
                    if (barcode) {
                        searchByBarcode(barcode);
                        $(this).val('');
                    }
                }
            });
            
            // Focus barcode input on pageload
            setTimeout(function() {
                $('#barcode-input').focus();
            }, 500);
            
            // Search products functionality
            $('#product-search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                filterProducts(searchTerm);
            });
            
            // Clear cart button
            $('#clear-cart').on('click', function() {
                Swal.fire({
                    title: 'Clear cart?',
                    text: "This will remove all items from the cart",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, clear it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        clearCart();
                    }
                });
            });
            
            // Remove item from cart
            $(document).on('click', '.remove-item-btn', function() {
                const productId = $(this).data('id');
                removeFromCart(productId);
            });
            
            // Update quantity
            $(document).on('change', '.quantity-input', function() {
                const productId = $(this).data('id');
                const quantity = parseInt($(this).val());
                
                if (quantity <= 0) {
                    $(this).val(1);
                    return;
                }
                
                updateCartItem(productId, quantity);
            });
            
            // Checkout button
            $('#checkout-btn').on('click', function() {
                // Populate modal with cart items
                let modalCartItems = '';
                let cartTotal = 0;
                
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    cartTotal += itemTotal;
                    
                    modalCartItems += `<tr>
                        <td>${item.name}</td>
                        <td>${item.quantity}</td>
                        <td class="text-end">$${itemTotal.toFixed(2)}</td>
                    </tr>`;
                });
                
                $('#modal-cart-items').html(modalCartItems);
                $('#modal-cart-total').text('$' + cartTotal.toFixed(2));
                
                // Show checkout modal
                $('#checkout-modal').modal('show');
            });
            
            // Complete sale button
            $('#complete-sale-btn').on('click', function() {
                completeSale();
            });
            
            // Print receipt button
            $('#print-receipt-btn').on('click', function() {
                printReceipt();
            });
            
            // Function to search by barcode
            function searchByBarcode(barcode) {
                // Show loading
                $('#loading-overlay').css('display', 'flex');
                
            $.ajax({
                    url: 'ajax/pos_functions.php',
                type: 'POST',
                data: {
                        action: 'search_products',
                        search: barcode
                },
                dataType: 'json',
                success: function(response) {
                    $('#loading-overlay').hide();
                        
                    if (response.status === 'success') {
                            if (response.products && response.products.length > 0) {
                                // If products found, add the first one to cart
                                addToCart(response.products[0].product_id, 1);
                            } else {
                                showAlert('No products found with that barcode', 'warning');
                            }
                    } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-overlay').hide();
                        console.error("AJAX Error:", status, error);
                        showAlert('An error occurred while searching products', 'danger');
                    }
                });
            }
            
            // Function to add to cart
            function addToCart(productId, quantity) {
                // Show loading
                $('#loading-overlay').css('display', 'flex');
                
                $.ajax({
                    url: 'ajax/pos_functions.php',
                    type: 'POST',
                    data: {
                        action: 'add_to_cart',
                        product_id: productId,
                        quantity: quantity
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-overlay').hide();
                        
                        if (response.status === 'success') {
                            cart = response.cart;
                            updateCartDisplay();
                            showAlert(response.message, 'success');
                            
                            // Focus on barcode input
                            $('#barcode-input').focus();
                            
                        } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                    $('#loading-overlay').hide();
                        console.error("AJAX Error:", status, error);
                        console.log("Response Text:", xhr.responseText);
                        try {
                            const errorObj = JSON.parse(xhr.responseText);
                            showAlert(errorObj.message || 'An error occurred while adding to cart', 'danger');
                        } catch (e) {
                            showAlert('An error occurred while adding to cart: ' + xhr.responseText.substring(0, 100), 'danger');
                        }
                    }
                });
            }
            
            // Function to update cart item
            function updateCartItem(productId, quantity) {
                // Show loading
                $('#loading-overlay').css('display', 'flex');
                
                $.ajax({
                    url: 'ajax/pos_functions.php',
                    type: 'POST',
                    data: {
                        action: 'update_cart_item',
                        product_id: productId,
                        quantity: quantity
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-overlay').hide();
                        
                        if (response.status === 'success') {
                            cart = response.cart;
                            updateCartDisplay();
                        } else {
                            showAlert(response.message, 'danger');
                            // Reset to the previous quantity
                            updateCartDisplay();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-overlay').hide();
                        console.error("AJAX Error:", status, error);
                        showAlert('An error occurred while updating the cart', 'danger');
                        updateCartDisplay();
                    }
                });
            }
            
            // Function to remove from cart
            function removeFromCart(productId) {
                // Show loading
                $('#loading-overlay').css('display', 'flex');
                
                $.ajax({
                    url: 'ajax/pos_functions.php',
                    type: 'POST',
                    data: {
                        action: 'remove_from_cart',
                        product_id: productId
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-overlay').hide();
                        
                        if (response.status === 'success') {
                            cart = response.cart;
                            updateCartDisplay();
                            showAlert('Item removed from cart', 'success');
            } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-overlay').hide();
                        console.error("AJAX Error:", status, error);
                        showAlert('An error occurred while removing from cart', 'danger');
                    }
                });
            }
            
            // Function to clear cart
            function clearCart() {
                // Show loading
                $('#loading-overlay').css('display', 'flex');
                
                $.ajax({
                    url: 'ajax/pos_functions.php',
                    type: 'POST',
                    data: {
                        action: 'clear_cart'
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-overlay').hide();
                        
                        if (response.status === 'success') {
                            cart = [];
                            updateCartDisplay();
                            showAlert('Cart cleared', 'success');
                        } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-overlay').hide();
                        console.error("AJAX Error:", status, error);
                        showAlert('An error occurred while clearing the cart', 'danger');
                    }
                });
            }
            
            // Function to complete sale
            function completeSale() {
                // Get form data
                const notes = $('#sale-notes').val();
                
                // Show loading
                $('#loading-overlay').css('display', 'flex');
                
                $.ajax({
                    url: 'ajax/pos_functions.php',
                    type: 'POST',
                    data: {
                        action: 'complete_sale',
                        notes: notes
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-overlay').hide();
                        
                        if (response.status === 'success') {
                            // Close checkout modal
                            $('#checkout-modal').modal('hide');
                            
                            // Generate receipt
                            generateReceipt(response.sale_id, response.total_amount);
                            
                            // Clear cart
                            cart = [];
                            updateCartDisplay();
                            
                            // Show success message
                            Swal.fire({
                                title: 'Sale Complete!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-overlay').hide();
                        console.error("AJAX Error:", status, error);
                        showAlert('An error occurred while completing the sale', 'danger');
                    }
                });
            }
            
            // Function to generate receipt
            function generateReceipt(saleId, totalAmount) {
                const date = new Date();
                const formattedDate = date.toLocaleDateString();
                const formattedTime = date.toLocaleTimeString();
                
                let receiptHtml = `
                    <div class="receipt-header">
                        <h4>Gym Management System</h4>
                        <p>Sale Receipt</p>
                        <p>Date: ${formattedDate} ${formattedTime}</p>
                        <p>Receipt #: ${saleId}</p>
                    </div>
                    <div class="receipt-items">
                        <table style="width:100%">
                            <thead>
                                <tr>
                                    <th style="text-align:left">Item</th>
                                    <th style="text-align:right">Qty</th>
                                    <th style="text-align:right">Price</th>
                                    <th style="text-align:right">Total</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                // Add cart items
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    receiptHtml += `
                        <tr>
                            <td style="text-align:left">${item.name}</td>
                            <td style="text-align:right">${item.quantity}</td>
                            <td style="text-align:right">$${item.price.toFixed(2)}</td>
                            <td style="text-align:right">$${itemTotal.toFixed(2)}</td>
                        </tr>`;
                });
                
                receiptHtml += `
                            </tbody>
                        </table>
                    </div>
                    <div class="receipt-total">
                        <table style="width:100%">
                            <tr>
                                <td style="text-align:left">Total:</td>
                                <td style="text-align:right">$${parseFloat(totalAmount).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td style="text-align:left">Payment Method:</td>
                                <td style="text-align:right">CASH</td>
                            </tr>
                        </table>
                    </div>
                    <div class="receipt-footer">
                        <p>Thank you for your purchase!</p>
                        <p>Please come again.</p>
                    </div>`;
                
                // Set receipt content
                $('#receipt-content').html(receiptHtml);
                
                // Show receipt modal
                $('#receipt-modal').modal('show');
            }
            
            // Function to print receipt
            function printReceipt() {
                const receiptContent = document.getElementById('receipt-content').innerHTML;
                const printWindow = window.open('', '', 'height=600,width=800');
                
                printWindow.document.write('<html><head><title>Receipt</title>');
                printWindow.document.write('<style>');
                printWindow.document.write(`
                    body { font-family: 'Courier New', monospace; line-height: 1.4; margin: 0; padding: 20px; }
                    .receipt-header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #000; }
                    .receipt-items { margin-bottom: 15px; }
                    .receipt-total { border-top: 1px dashed #000; padding-top: 10px; font-weight: bold; }
                    .receipt-footer { text-align: center; margin-top: 15px; border-top: 1px dashed #000; padding-top: 10px; font-size: 12px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 5px; }
                `);
                printWindow.document.write('</style></head><body>');
                printWindow.document.write(receiptContent);
                printWindow.document.write('</body></html>');
                
                printWindow.document.close();
                printWindow.focus();
                
                // Print after a short delay to ensure content is loaded
                setTimeout(function() {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            }
            
            // Function to filter products
            function filterProducts(searchTerm) {
                $('.product-card').each(function() {
                    const productName = $(this).data('name');
                    
                    if (productName && productName.toString().toLowerCase().includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            // Function to update cart display
            function updateCartDisplay() {
                let cartHtml = '';
                let cartTotal = 0;
                
                if (cart.length > 0) {
                    cart.forEach(item => {
                        const itemTotal = item.price * item.quantity;
                        cartTotal += itemTotal;
                        
                        cartHtml += `
                            <tr>
                                <td>${item.name}</td>
                                <td>$${item.price.toFixed(2)}</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm quantity-input" 
                                           value="${item.quantity}" min="1" max="${item.max_quantity}" 
                                           data-id="${item.product_id}">
                                </td>
                                <td>$${itemTotal.toFixed(2)}</td>
                                <td>
                                    <button class="btn btn-sm btn-danger remove-item-btn" data-id="${item.product_id}">
                                        <i class="mdi mdi-trash-can"></i>
                                    </button>
                                </td>
                            </tr>`;
                    });
                    
                    $('#empty-cart-message').hide();
                    $('#clear-cart').prop('disabled', false);
                    $('#checkout-btn').prop('disabled', false);
                } else {
                    $('#empty-cart-message').show();
                    $('#clear-cart').prop('disabled', true);
                    $('#checkout-btn').prop('disabled', true);
                }
                
                $('#cart-items').html(cartHtml);
                $('#cart-total').text('$' + cartTotal.toFixed(2));
                
                // Hide the empty cart message if there are items
                if (cart.length > 0) {
                    $('#empty-cart-message').hide();
                } else {
                    $('#empty-cart-message').show();
                }
            }
            
            // Function to show alert messages
            function showAlert(message, type) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;
                
                $('#alert-container').html(alertHtml);
                
                // Auto-dismiss after 3 seconds
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 3000);
            }
    });
    </script>
</body>
</html>