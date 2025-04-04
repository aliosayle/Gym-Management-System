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

// Include permission checks
include 'layouts/check_permission.php';

// Get branch ID from the request
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 
            (isset($_SESSION['selected_branch_id']) ? intval($_SESSION['selected_branch_id']) : 1);

// Check if user ID is set in session
if (!isset($_SESSION['id'])) {
    die("User ID is not set in session.");
}

// Check specific permissions for this page
$can_manage_inventory = has_permission('can_manage_inventory', $pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submit']) && $can_manage_inventory) {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity_in_stock = (int)$_POST['quantity_in_stock'];
    $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : $branch_id;
    
    $insert_query = "INSERT INTO products (name, description, price, quantity_in_stock, branch_id) 
                    VALUES (:product_name, :description, :price, :quantity_in_stock, :branch_id)";
    $insert_stmt = $pdo->prepare($insert_query);
    if ($insert_stmt->execute([
        'product_name' => $product_name, 
        'description' => $description, 
        'price' => $price, 
        'quantity_in_stock' => $quantity_in_stock,
        'branch_id' => $branch_id
    ])) {
        // Make sure to update the selected branch ID in the session to match the branch we're redirecting to
        $_SESSION['selected_branch_id'] = $branch_id;
        
        // Get branch name to update in session
        $branch_query = "SELECT name FROM branches WHERE id = :branch_id";
        $branch_stmt = $pdo->prepare($branch_query);
        $branch_stmt->execute(['branch_id' => $branch_id]);
        $branch_name = $branch_stmt->fetchColumn();
        if ($branch_name) {
            $_SESSION['selected_branch_name'] = $branch_name;
        }
        
        $_SESSION['delete_message'] = "Product added successfully";
    } else {
        $_SESSION['delete_message'] = "Error adding product: " . implode(", ", $insert_stmt->errorInfo());
    }
    header("Location: products.php?branch_id=" . $branch_id);
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
                                <form method="POST" action="add_product.php?branch_id=<?php echo $branch_id; ?>">
                                    <input type="hidden" name="form_submit" value="1">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Product SKU/Barcode</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                        <small class="form-text text-muted">Enter barcode or SKU number. This is used for scanning/inventory purposes.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Product Name/Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                        <small class="form-text text-muted">Enter the actual product name. This will appear on receipts and sales reports.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price</label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity_in_stock" class="form-label">Quantity in Stock</label>
                                        <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock" required>
                                    </div>
                                    <input type="hidden" name="branch_id" value="<?php echo $branch_id; ?>">
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