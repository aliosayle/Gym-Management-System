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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['delete_message'])) {
    $alert_type = strpos($_SESSION['delete_message'], 'successfully') !== false ? 'success' : 'danger';
    echo "<div class='alert alert-$alert_type alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['delete_message']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    unset($_SESSION['delete_message']); // Unset after displaying the message
}

// Fetch user permissions
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null; // Ensure user_id is set

if ($user_id === null) {
    die("User ID is not set in the session.");
}

// Check specific permissions for this page
$can_manage_inventory = has_permission('can_manage_inventory', $pdo);

// Set action permissions based on inventory management permission
$canedit = $can_manage_inventory ? 1 : 0;
$candelete = $can_manage_inventory ? 1 : 0;
$canadd = $can_manage_inventory ? 1 : 0;

// Get the user's assigned branches
$branches_query = "SELECT b.* FROM branches b 
                  JOIN user_branches ub ON b.id = ub.branch_id 
                  WHERE ub.user_id = :user_id";
$branches_stmt = $pdo->prepare($branches_query);
$branches_stmt->execute(['user_id' => $user_id]);
$user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);

// If admin with no branches assigned, get all branches
$isadmin_query = "SELECT isadmin FROM users WHERE id = :id";
$isadmin_stmt = $pdo->prepare($isadmin_query);
$isadmin_stmt->execute(['id' => $user_id]);
$is_admin = $isadmin_stmt->fetchColumn();

if ($is_admin && empty($user_branches)) {
    $branches_query = "SELECT * FROM branches";
    $branches_stmt = $pdo->prepare($branches_query);
    $branches_stmt->execute();
    $user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get the selected branch (from POST, GET, session, or default to the first branch)
$selected_branch_id = null;

if (isset($_POST['branch_id'])) {
    $selected_branch_id = $_POST['branch_id'];
} elseif (isset($_GET['branch_id'])) {
    $selected_branch_id = $_GET['branch_id'];
} elseif (isset($_SESSION['selected_branch_id'])) {
    $selected_branch_id = $_SESSION['selected_branch_id'];
} elseif (!empty($user_branches)) {
    $selected_branch_id = $user_branches[0]['id'];
}

// Ensure we have a valid branch ID
if (empty($selected_branch_id) && !empty($user_branches)) {
    $selected_branch_id = $user_branches[0]['id'];
} elseif (empty($selected_branch_id)) {
    $selected_branch_id = 1; // Default to branch ID 1 if nothing else is available
}

// Store selected branch in session
$_SESSION['selected_branch_id'] = $selected_branch_id;

// Get inventory statistics
$total_products = 0;
$total_value = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;

try {
    // Total products count
    $count_query = "SELECT COUNT(*) FROM products WHERE branch_id = :branch_id";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute(['branch_id' => $selected_branch_id]);
    $total_products = $count_stmt->fetchColumn();
    
    // Total inventory value
    $value_query = "SELECT SUM(price * quantity_in_stock) FROM products WHERE branch_id = :branch_id";
    $value_stmt = $pdo->prepare($value_query);
    $value_stmt->execute(['branch_id' => $selected_branch_id]);
    $total_value = $value_stmt->fetchColumn() ?: 0;
    
    // Low stock count (less than 10 items)
    $low_stock_query = "SELECT COUNT(*) FROM products WHERE quantity_in_stock BETWEEN 1 AND 10 AND branch_id = :branch_id";
    $low_stock_stmt = $pdo->prepare($low_stock_query);
    $low_stock_stmt->execute(['branch_id' => $selected_branch_id]);
    $low_stock_count = $low_stock_stmt->fetchColumn();
    
    // Out of stock count
    $out_of_stock_query = "SELECT COUNT(*) FROM products WHERE quantity_in_stock = 0 AND branch_id = :branch_id";
    $out_stock_stmt = $pdo->prepare($out_of_stock_query);
    $out_stock_stmt->execute(['branch_id' => $selected_branch_id]);
    $out_of_stock_count = $out_stock_stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error getting inventory statistics: " . $e->getMessage());
}

// Protect POST actions with permission checks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_name']) && $canadd == 1) {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity_in_stock = (int)$_POST['quantity_in_stock'];
    $insert_query = "INSERT INTO products (name, description, price, quantity_in_stock, branch_id) 
                    VALUES (:product_name, :description, :price, :quantity_in_stock, :branch_id)";
    $insert_stmt = $pdo->prepare($insert_query);
    if ($insert_stmt->execute([
        'product_name' => $product_name, 
        'description' => $description, 
        'price' => $price, 
        'quantity_in_stock' => $quantity_in_stock,
        'branch_id' => $selected_branch_id
    ])) {
        echo "<script>alert('New product added successfully');</script>";
    } else {
        echo "<script>alert('Error adding product: " . implode(", ", $insert_stmt->errorInfo()) . "');</script>";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<script>alert('You do not have permission to add products.');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Inventory Management | Admin Dashboard</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
    <style>
        .card-stats {
            transition: all 0.3s ease;
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 100;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .bg-low-stock {
            background-color: #fff3cd;
        }
        .bg-out-of-stock {
            background-color: #f8d7da;
        }
        /* Quantity control styles */
        .quantity-control {
            display: flex;
            align-items: center;
        }
        .quantity-control .form-control {
            width: 80px;
            text-align: center;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
        }
        /* Tooltip styles */
        .tooltip-inner {
            max-width: 300px;
            padding: 10px;
        }
        /* Transaction history modal */
        .transaction-item {
            border-left: 3px solid #ccc;
            padding-left: 10px;
            margin-bottom: 10px;
        }
        .transaction-item.increase {
            border-left-color: #28a745;
        }
        .transaction-item.decrease {
            border-left-color: #dc3545;
        }
        .transaction-item .transaction-date {
            font-size: 12px;
            color: #6c757d;
        }
        .transaction-item .transaction-details {
            font-size: 14px;
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
                <!-- Page title and breadcrumb -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">
                                Inventory Management
                                <?php 
                                // Show selected branch name
                                if ($selected_branch_id) {
                                    $branch_name = '';
                                    foreach ($user_branches as $branch) {
                                        if ($branch['id'] == $selected_branch_id) {
                                            $branch_name = isset($branch['name']) ? $branch['name'] : 
                                                        (isset($branch['branch_name']) ? $branch['branch_name'] : 'Branch ' . $branch['id']);
                                            break;
                                        }
                                    }
                                    if (!empty($branch_name)) {
                                        echo ' - ' . htmlspecialchars($branch_name);
                                    }
                                }
                                ?>
                            </h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Inventory Management</li>
                            </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics cards -->
                <div class="row">
                    <!-- Total Products -->
                    <div class="col-md-3">
                        <div class="card card-stats mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">Total Products</h5>
                                        <span class="h2 font-weight-bold mb-0"><?php echo number_format($total_products); ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inventory Value -->
                    <div class="col-md-3">
                        <div class="card card-stats mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">Total Value</h5>
                                        <span class="h2 font-weight-bold mb-0">$<?php echo number_format($total_value, 2); ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow">
                                            <i class="fas fa-dollar-sign"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock -->
                    <div class="col-md-3">
                        <div class="card card-stats mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">Low Stock</h5>
                                        <span class="h2 font-weight-bold mb-0"><?php echo number_format($low_stock_count); ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Out of Stock -->
                    <div class="col-md-3">
                        <div class="card card-stats mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">Out of Stock</h5>
                                        <span class="h2 font-weight-bold mb-0"><?php echo number_format($out_of_stock_count); ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-gradient-danger text-white rounded-circle shadow">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title mb-0">Inventory Management</h4>
                                    <div>
                                        <a href="add_product.php?branch_id=<?php echo $selected_branch_id; ?>" class="btn btn-primary" <?php if ($canadd == 0) echo 'disabled'; ?>>
                                    <i class="fas fa-plus me-2"></i> Add New Product
                                </a>
                                        <button type="button" class="btn btn-success ms-2" id="btnExportInventory">
                                            <i class="fas fa-file-export me-2"></i> Export
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filterCategory">Category</label>
                                            <select class="form-select" id="filterCategory">
                                                <option value="">All Categories</option>
                                                <!-- Add categories here if you have them -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filterStock">Stock Status</label>
                                            <select class="form-select" id="filterStock">
                                                <option value="">All</option>
                                                <option value="low">Low Stock</option>
                                                <option value="out">Out of Stock</option>
                                                <option value="in">In Stock</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="searchProduct">Search</label>
                                            <input type="text" class="form-control" id="searchProduct" placeholder="Search by name, description...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="row" id="productsGrid">
                    <?php 
                    $query = "SELECT * FROM products WHERE branch_id = :branch_id ORDER BY name ASC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(['branch_id' => $selected_branch_id]);
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($products) {
                        foreach ($products as $product) {
                            // Determine stock status
                            $stock_class = '';
                            $stock_badge = '';
                            if ($product['quantity_in_stock'] <= 0) {
                                $stock_class = 'bg-out-of-stock';
                                $stock_badge = '<span class="badge bg-danger stock-badge">Out of Stock</span>';
                            } elseif ($product['quantity_in_stock'] <= 10) {
                                $stock_class = 'bg-low-stock';
                                $stock_badge = '<span class="badge bg-warning stock-badge">Low Stock</span>';
                            }
                    ?>
                    <div class="col-md-4 col-lg-3 mb-4 product-item" data-category="" data-stock="<?php echo $product['quantity_in_stock'] <= 0 ? 'out' : ($product['quantity_in_stock'] <= 10 ? 'low' : 'in'); ?>">
                        <div class="card product-card <?php echo $stock_class; ?>">
                            <?php echo $stock_badge; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted small"><?php echo substr(htmlspecialchars($product['description']), 0, 100) . (strlen($product['description']) > 100 ? '...' : ''); ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="badge bg-<?php echo $product['quantity_in_stock'] <= 0 ? 'danger' : ($product['quantity_in_stock'] <= 10 ? 'warning' : 'success'); ?>">
                                        Stock: <?php echo $product['quantity_in_stock']; ?>
                                    </span>
                                </div>
                                <div class="action-buttons">
                                    <!-- Stock Management Buttons -->
                                    <button type="button" class="btn btn-success btn-sm stock-action" data-action="add" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" <?php if ($canedit == 0) echo 'disabled'; ?>>
                                        <i class="fas fa-plus-circle"></i> Add
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm stock-action" data-action="remove" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" <?php if ($canedit == 0 || $product['quantity_in_stock'] <= 0) echo 'disabled'; ?>>
                                        <i class="fas fa-minus-circle"></i> Remove
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm stock-action" data-action="adjust" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" <?php if ($canedit == 0) echo 'disabled'; ?>>
                                        <i class="fas fa-sliders-h"></i> Adjust
                                    </button>
                                    
                                    <!-- Transaction History -->
                                    <button type="button" class="btn btn-secondary btn-sm mt-2 view-history" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <i class="fas fa-history"></i> History
                                    </button>
                                    
                                    <!-- Edit & Delete -->
                                    <div class="mt-2">
                                        <form method="POST" action="edit_product.php?id=<?php echo $product['product_id']; ?>&branch_id=<?php echo $selected_branch_id; ?>" style="display:inline-block;" onsubmit="return submitForm(this);">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                            <button type="submit" class="btn btn-warning btn-sm" <?php if ($canedit == 0) echo 'disabled'; ?>>
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger btn-sm delete-product" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" <?php if ($candelete == 0) echo 'disabled'; ?>>
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<div class="col-12"><div class="alert alert-info">No products found. Add some products to get started.</div></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Action Modal -->
<div class="modal fade" id="stockActionModal" tabindex="-1" aria-labelledby="stockActionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockActionModalLabel">Stock Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="stockActionText">Adjust stock for <span id="productName"></span></p>
                <div class="form-group">
                    <label for="quantityInput">Quantity:</label>
                    <div class="quantity-control">
                        <button type="button" class="btn btn-sm btn-primary quantity-btn" id="decreaseQuantity">-</button>
                        <input type="number" class="form-control mx-2" id="quantityInput" min="1" value="1">
                        <button type="button" class="btn btn-sm btn-primary quantity-btn" id="increaseQuantity">+</button>
                    </div>
                </div>
                <div class="form-group mt-3">
                    <label for="notesInput">Notes (optional):</label>
                    <textarea class="form-control" id="notesInput" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStockAction">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Transaction History Modal -->
<div class="modal fade" id="transactionHistoryModal" tabindex="-1" aria-labelledby="transactionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionHistoryModalLabel">Transaction History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="transactionHistoryContent">
                    <!-- Transaction history will be loaded here -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading transaction history...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="assets/js/app.js"></script>

<script>
    $(document).ready(function() {
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Product filters
        $("#searchProduct").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $(".product-item").filter(function() {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1);
            });
        });
        
        $("#filterStock").on("change", function() {
            var value = $(this).val();
            if (value === "") {
                $(".product-item").show();
            } else {
                $(".product-item").hide();
                $(".product-item[data-stock='" + value + "']").show();
            }
        });
        
        // Stock action buttons
        $(".stock-action").on("click", function() {
            var action = $(this).data("action");
            var productId = $(this).data("id");
            var productName = $(this).data("name");
            
            $("#productName").text(productName);
            $("#stockActionModal").data("product-id", productId);
            
            // Set modal title and text based on action
            if (action === "add") {
                $("#stockActionModalLabel").text("Add Stock");
                $("#stockActionText").text("Add stock for " + productName);
                $("#confirmStockAction").removeClass("btn-danger btn-warning").addClass("btn-success");
                $("#stockActionModal").data("action", "add");
            } else if (action === "remove") {
                $("#stockActionModalLabel").text("Remove Stock");
                $("#stockActionText").text("Remove stock for " + productName);
                $("#confirmStockAction").removeClass("btn-success btn-warning").addClass("btn-danger");
                $("#stockActionModal").data("action", "remove");
            } else if (action === "adjust") {
                $("#stockActionModalLabel").text("Adjust Stock");
                $("#stockActionText").text("Set exact stock level for " + productName);
                $("#confirmStockAction").removeClass("btn-success btn-danger").addClass("btn-warning");
                $("#stockActionModal").data("action", "adjust");
            }
            
            // Reset form
            $("#quantityInput").val(1);
            $("#notesInput").val("");
            
            // Show modal
            $("#stockActionModal").modal("show");
        });
        
        // Quantity input controls
        $("#decreaseQuantity").on("click", function() {
            var currentValue = parseInt($("#quantityInput").val());
            if (currentValue > 1) {
                $("#quantityInput").val(currentValue - 1);
            }
        });
        
        $("#increaseQuantity").on("click", function() {
            var currentValue = parseInt($("#quantityInput").val());
            $("#quantityInput").val(currentValue + 1);
        });
        
        // Confirm stock action
        $("#confirmStockAction").on("click", function() {
            var productId = $("#stockActionModal").data("product-id");
            var action = $("#stockActionModal").data("action");
            var quantity = parseInt($("#quantityInput").val());
            var notes = $("#notesInput").val();
            
            if (isNaN(quantity) || quantity < 1) {
                Swal.fire({
                    title: 'Invalid Quantity',
                    text: 'Please enter a valid quantity (minimum 1)',
                    icon: 'error'
                });
                return;
            }
            
            // Create the appropriate endpoint based on the action
            var endpoint = "";
            if (action === "add") {
                endpoint = "inventory_add_stock.php";
            } else if (action === "remove") {
                endpoint = "inventory_remove_stock.php";
            } else if (action === "adjust") {
                endpoint = "inventory_adjust_stock.php";
            }
            
            // Show loading
            Swal.fire({
                title: 'Processing...',
                html: 'Please wait while we update the inventory',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
        });

            // Call the appropriate endpoint
            $.ajax({
                url: endpoint,
                type: "GET",
                data: {
                    id: productId,
                    quantity: quantity,
                    notes: notes
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        Swal.fire({
                            title: 'Success',
                            text: response.message,
                            icon: 'success'
                        }).then(function() {
                            // Reload the page to update the UI
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while processing your request. Please try again.',
                        icon: 'error'
                    });
                    console.error(xhr.responseText);
                }
            });
            
            // Close the modal
            $("#stockActionModal").modal("hide");
        });
        
        // View transaction history
        $(".view-history").on("click", function() {
            var productId = $(this).data("id");
            var productName = $(this).data("name");
            
            $("#transactionHistoryModalLabel").text("Transaction History: " + productName);
            $("#transactionHistoryModal").modal("show");
            
            // Load transaction history
            $.ajax({
                url: "ajax/get_product_transactions.php",
                type: "GET",
                data: {
                    product_id: productId
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        var html = "";
                        
                        if (response.transactions.length === 0) {
                            html = '<div class="alert alert-info">No transaction history found for this product.</div>';
                        } else {
                            html = '<div class="list-group">';
                            
                            $.each(response.transactions, function(index, transaction) {
                                var transactionClass = "";
                                var icon = "";
                                
                                if (transaction.transaction_type === "purchase" || transaction.transaction_type === "adjustment_in") {
                                    transactionClass = "increase";
                                    icon = '<i class="fas fa-arrow-up text-success me-2"></i>';
                                } else if (transaction.transaction_type === "sale" || transaction.transaction_type === "adjustment_out") {
                                    transactionClass = "decrease";
                                    icon = '<i class="fas fa-arrow-down text-danger me-2"></i>';
                                }
                                
                                html += '<div class="transaction-item ' + transactionClass + '">';
                                html += '<div class="transaction-date">' + transaction.transaction_date + '</div>';
                                html += '<div class="transaction-details">';
                                html += icon;
                                html += '<strong>' + transaction.transaction_type.toUpperCase() + '</strong>: ';
                                
                                if (transaction.transaction_type === "purchase" || transaction.transaction_type === "adjustment_in") {
                                    html += 'Added ' + transaction.quantity + ' units';
                                } else if (transaction.transaction_type === "sale" || transaction.transaction_type === "adjustment_out") {
                                    html += 'Removed ' + Math.abs(transaction.quantity) + ' units';
                                }
                                
                                if (transaction.notes) {
                                    html += ' - Note: ' + transaction.notes;
                                }
                                
                                html += '</div>';
                                html += '</div>';
                            });
                            
                            html += '</div>';
                        }
                        
                        $("#transactionHistoryContent").html(html);
                    } else {
                        $("#transactionHistoryContent").html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $("#transactionHistoryContent").html('<div class="alert alert-danger">Error loading transaction history. Please try again.</div>');
                    console.error(xhr.responseText);
                }
            });
        });
        
        // Delete product
        $(".delete-product").on("click", function() {
            var productId = $(this).data("id");
            var productName = $(this).data("name");
            var branchId = <?php echo $selected_branch_id; ?>;
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to delete " + productName + ". This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete_product.php?id=' + productId + '&branch_id=' + branchId;
                }
            });
        });

        // Export inventory
        $("#btnExportInventory").on("click", function() {
            Swal.fire({
                title: 'Export Inventory',
                html: 'Choose export format:',
                showCancelButton: true,
                confirmButtonText: 'CSV',
                cancelButtonText: 'PDF',
                showCloseButton: true,
                showDenyButton: true,
                denyButtonText: 'Excel'
            }).then((result) => {
                var format = "";
                if (result.isConfirmed) {
                    format = "csv";
                } else if (result.isDenied) {
                    format = "excel";
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    format = "pdf";
                } else {
                    return;
                }
                
                window.location.href = 'export_inventory.php?format=' + format;
                    });
        });
    });
    
    function submitForm(form) {
        var productId = form.querySelector('input[name="product_id"]').value;
        if (!productId) {
            alert('Product ID is missing');
            return false;
        }
        return true;
    }
</script>
</body>
</html>