<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

if (!$pdo) {
    die("Connection not established: " . $pdo->errorInfo());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['pos_message'])) {
    $alert_type = strpos($_SESSION['pos_message'], 'successfully') !== false ? 'success' : 'danger';
    echo "<div class='alert alert-$alert_type alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['pos_message']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    unset($_SESSION['pos_message']); // Unset after displaying the message
}

// Fetch products
$query = "SELECT * FROM products";
$stmt = $pdo->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>POS System | Admin Template</title>
    <?php include 'layouts/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include 'layouts/head-style.php'; ?>
    <style>
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
            width: 100px;
            height: 100px;
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
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">POS System</h4>
                                </div>
                                <div class="card-body">
                                    <form id="add-to-cart-form">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <div class="mb-3">
                                            <label for="product_id" class="form-label">Select Product</label>
                                            <select class="form-select" id="product_id" name="product_id" required>
                                                <option value="" disabled selected>Select a product</option>
                                                <?php foreach ($products as $product): ?>
                                                <option value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                                    <?php echo htmlspecialchars($product['name']) . " - " . htmlspecialchars($product['description']) . " - $" . htmlspecialchars($product['price']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>

                                        </div>
                                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                                    </form>

                                    <h4 class="mt-4">Cart</h4>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cart-items">
                                            <tr>
                                                <td colspan="5">No items in cart</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <button id="clear-cart" class="btn btn-warning" style="display: none;">Clear
                                        All</button>
                                    <form id="complete-sale-form" class="mt-3" style="display: none;">
                                        <input type="hidden" name="action" value="complete_sale">
                                        <button type="submit" class="btn btn-success">Complete Sale</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loading-overlay">
        <img src="assets/images/loading.gif" alt="Loading...">
    </div>

    <?php include 'layouts/footer.php'; ?>
    </div>

    <?php include 'layouts/vendor-scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
$(document).ready(function() {
    // Initialize Select2
    $('#product_id').select2({
        placeholder: 'Select a product',
        allowClear: true
    }).on('select2:open', function() {
        // Focus on the search input field inside Select2 dropdown
        setTimeout(function() {
            $('.select2-search__field').focus();
        }, 200); // Increase timeout to make sure the input is fully rendered
    });

    // Add product to cart on Enter key press
    $('.select2-search__field').on('keypress', function(e) {
        if (e.which === 13) { // 13 is the Enter key
            $('#add-to-cart-form').submit();
        }
    });

    var cart = [];

    // Handle the Add to Cart form submission
    $('#add-to-cart-form').on('submit', function(e) {
        e.preventDefault();
        var productId = $('#product_id').val();
        var productName = $('#product_id option:selected').text().split(' - $')[0];
        var productPrice = parseFloat($('#product_id option:selected').text().split(' - $')[1]);

        var existingItem = cart.find(item => item.product_id == productId);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                product_id: productId,
                name: productName,
                price: productPrice,
                quantity: 1
            });
        }

        updateCart();
        focusSelect2();
    });

    // Remove item from cart
    $('#cart-items').on('click', '.remove-item', function() {
        var productId = $(this).data('product-id');
        cart = cart.filter(item => item.product_id != productId);
        updateCart();
        focusSelect2();
    });

    // Handle quantity change in cart
    $('#cart-items').on('input', '.quantity-input', function() {
        var productId = $(this).data('product-id');
        var quantity = parseInt($(this).val());
        var item = cart.find(item => item.product_id == productId);
        if (item) {
            item.quantity = quantity;
            updateCart();
            focusSelect2();
        }
    });

    // Clear cart button
    $('#clear-cart').on('click', function() {
        cart = [];
        updateCart();
        focusSelect2();
    });

    // Complete Sale form
    $('#complete-sale-form').on('submit', function(e) {
        e.preventDefault();
        $('#loading-overlay').show();
        $.ajax({
            type: 'POST',
            url: 'complete_sale.php',
            data: {
                action: 'complete_sale',
                cart: JSON.stringify(cart)
            },
            dataType: 'json',
            success: function(response) {
                $('#loading-overlay').hide();
                if (response.status === 'success') {
                    alert(response.message);
                    cart = [];
                    updateCart();
                } else {
                    alert(response.message);
                }
                focusSelect2();
            },
            error: function() {
                $('#loading-overlay').hide();
                alert('An error occurred while completing the sale.');
                focusSelect2();
            }
        });
    });

    // Update the cart UI
    function updateCart() {
        var cartItems = $('#cart-items');
        cartItems.empty();
        if (cart.length > 0) {
            cart.forEach(function(item) {
                cartItems.append(
                    '<tr>' +
                    '<td>' + item.name + '</td>' +
                    '<td>' + item.price + '</td>' +
                    '<td><input type="number" class="form-control quantity-input" data-product-id="' +
                    item.product_id + '" value="' + item.quantity + '" min="1"></td>' +
                    '<td>' + (item.price * item.quantity).toFixed(2) + '</td>' +
                    '<td><button class="btn btn-danger btn-sm remove-item" data-product-id="' +
                    item.product_id + '">Remove</button></td>' +
                    '</tr>'
                );
            });
            $('#clear-cart').show();
            $('#complete-sale-form').show();
        } else {
            cartItems.append('<tr><td colspan="5">No items in cart</td></tr>');
            $('#clear-cart').hide();
            $('#complete-sale-form').hide();
        }
    }

    // Function to open Select2 and focus on the input
    function focusSelect2() {
        $('#product_id').select2('open');
    }

    // Initial focus on Select2 input field
    focusSelect2();
});

    </script>
</body>

</html>