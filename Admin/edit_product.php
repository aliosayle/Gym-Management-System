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

// Check if user ID is set in session
if (!isset($_SESSION['id'])) {
    die("User ID is not set in session.");
}

// Fetch user permissions
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$permission_query = "SELECT canedit FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch(PDO::FETCH_ASSOC);

if (!$permissions || $permissions['canedit'] != 1) {
    die("You do not have permission to edit products.");
}

// Check if product ID is set in GET parameters
if (!isset($_GET['id'])) {
    die("Product ID is not set.");
}

$product_id = $_GET['id'];

// Fetch product details
$product_query = "SELECT * FROM products WHERE product_id = :product_id";
$product_stmt = $pdo->prepare($product_query);
$product_stmt->execute(['product_id' => $product_id]);
$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submit'])) {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity_in_stock = (int)$_POST['quantity_in_stock'];
    $update_query = "UPDATE products SET name = :product_name, description = :description, price = :price, quantity_in_stock = :quantity_in_stock WHERE product_id = :product_id";
    $update_stmt = $pdo->prepare($update_query);
    if ($update_stmt->execute(['product_name' => $product_name, 'description' => $description, 'price' => $price, 'quantity_in_stock' => $quantity_in_stock, 'product_id' => $product_id])) {
        $_SESSION['delete_message'] = "Product updated successfully";
    } else {
        $_SESSION['delete_message'] = "Error updating product: " . implode(", ", $update_stmt->errorInfo());
    }
    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Product | Admin Template</title>
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
                                <h4 class="card-title">Edit Product</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="edit_product.php?id=<?php echo htmlspecialchars($product_id); ?>">
                                    <input type="hidden" name="form_submit" value="1">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Product Name</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price</label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity_in_stock" class="form-label">Quantity in Stock</label>
                                        <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock" value="<?php echo htmlspecialchars($product['quantity_in_stock']); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Product</button>
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