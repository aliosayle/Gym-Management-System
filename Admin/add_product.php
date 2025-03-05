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

// Check if user ID is set in session
if (!isset($_SESSION['id'])) {
    die("User ID is not set in session.");
}

// Fetch user permissions
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$permission_query = "SELECT canadd FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch(PDO::FETCH_ASSOC);

if (!$permissions) {
    die("User permissions not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submit']) && $permissions['canadd'] == 1) {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity_in_stock = (int)$_POST['quantity_in_stock'];
    $insert_query = "INSERT INTO products (name, description, price, quantity_in_stock) VALUES (:product_name, :description, :price, :quantity_in_stock)";
    $insert_stmt = $pdo->prepare($insert_query);
    if ($insert_stmt->execute(['product_name' => $product_name, 'description' => $description, 'price' => $price, 'quantity_in_stock' => $quantity_in_stock])) {
        $_SESSION['delete_message'] = "Product added successfully";
    } else {
        $_SESSION['delete_message'] = "Error adding product: " . implode(", ", $insert_stmt->errorInfo());
    }
    header("Location: products.php");
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['delete_message'] = "You do not have permission to add products.";
    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Product | Admin Template</title>
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
                                <h4 class="card-title">Add New Product</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="add_product.php">
                                    <input type="hidden" name="form_submit" value="1">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Product Name</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price</label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity_in_stock" class="form-label">Quantity in Stock</label>
                                        <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Product</button>
                                </form>
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