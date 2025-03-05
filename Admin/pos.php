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
        } else {
            $_SESSION['pos_message'] = "Insufficient stock for the selected product.";
        }
    } elseif ($_POST['action'] == 'complete_sale') {
        // Complete the sale
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }

        // Insert sale record
        $sale_query = "INSERT INTO sales (user_id, total_amount) VALUES (:user_id, :total_amount)";
        $sale_stmt = $pdo->prepare($sale_query);
        $sale_stmt->execute(['user_id' => $_SESSION['id'], 'total_amount' => $total_amount]);
        $sale_id = $pdo->lastInsertId();

        // Insert sale items and update product quantities
        foreach ($_SESSION['cart'] as $item) {
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
        $_SESSION['pos_message'] = "Sale completed successfully";
    }
    header("Location: pos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>POS System | Admin Template</title>
    <?php include 'layouts/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include 'layouts/head-style.php'; ?>
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
                                <form method="POST" action="pos.php">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <div class="mb-3">
                                        <label for="product_id" class="form-label">Select Product</label>
                                        <select class="form-select" id="product_id" name="product_id" required>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                                    <?php echo htmlspecialchars($product['name']) . " - $" . htmlspecialchars($product['price']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                            <?php foreach ($_SESSION['cart'] as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['price']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['price'] * $item['quantity']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4">No items in cart</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                    <form method="POST" action="pos.php">
                                        <input type="hidden" name="action" value="complete_sale">
                                        <button type="submit" class="btn btn-success">Complete Sale</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
</div>

<?php include 'layouts/vendor-scripts.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>