<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

// Check database connection
if (!$pdo) {
    die("Database connection failed");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check user permissions
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;
if ($user_id === null) {
    die("User not authenticated");
}

$permission_query = "SELECT canadd FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permission = $permission_stmt->fetchColumn();

if ($permission === false || $permission != 1) {
    $_SESSION['inventory_message'] = "You don't have permission to add inventory items";
    header("Location: inventory.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $sku = $_POST['sku'] ?? '';
    $barcode = $_POST['barcode'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $cost = (float)($_POST['cost'] ?? 0);
    $category = $_POST['category'] ?? '';
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($sku)) {
        $errors[] = "SKU is required";
    } else {
        // Check if SKU already exists
        $check_sku_query = "SELECT COUNT(*) FROM inventory_items WHERE sku = :sku";
        $check_sku_stmt = $pdo->prepare($check_sku_query);
        $check_sku_stmt->execute(['sku' => $sku]);
        if ($check_sku_stmt->fetchColumn() > 0) {
            $errors[] = "SKU already exists";
        }
    }
    
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero";
    }
    
    if ($cost < 0) {
        $errors[] = "Cost cannot be negative";
    }
    
    // Handle image upload
    $image_src = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Invalid image format. Allowed formats: JPG, PNG, GIF";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size exceeds 2MB limit";
        } else {
            $upload_dir = 'assets/images/inventory/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'inv_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_src = $target_file;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // If no errors, insert the item
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert into inventory_items
            $insert_query = "INSERT INTO inventory_items (name, description, sku, barcode, quantity, price, cost, category, image_src) 
                            VALUES (:name, :description, :sku, :barcode, :quantity, :price, :cost, :category, :image_src)";
            
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([
                'name' => $name,
                'description' => $description,
                'sku' => $sku,
                'barcode' => $barcode,
                'quantity' => $quantity,
                'price' => $price,
                'cost' => $cost,
                'category' => $category,
                'image_src' => $image_src
            ]);
            
            $item_id = $pdo->lastInsertId();
            
            // If initial quantity > 0, add an initial inventory transaction
            if ($quantity > 0) {
                $transaction_query = "INSERT INTO inventory_transactions (item_id, type, quantity, price, total_amount, notes, created_by) 
                                    VALUES (:item_id, 'beginning', :quantity, :cost, :total_amount, 'Initial inventory', :created_by)";
                
                $transaction_stmt = $pdo->prepare($transaction_query);
                $transaction_stmt->execute([
                    'item_id' => $item_id,
                    'quantity' => $quantity,
                    'cost' => $cost,
                    'total_amount' => $quantity * $cost,
                    'created_by' => $user_id
                ]);
            }
            
            $pdo->commit();
            
            $_SESSION['inventory_message'] = "Inventory item added successfully";
            header("Location: inventory.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch categories for dropdown
try {
    $categories_query = "SELECT DISTINCT category FROM inventory_items WHERE category IS NOT NULL AND category != '' ORDER BY category";
    $categories_stmt = $pdo->query($categories_query);
    $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Inventory Item | GMS</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
    <style>
        .form-label {
            font-weight: 500;
        }
        
        .invalid-feedback {
            display: block;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
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
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">Add Inventory Item</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                        <li class="breadcrumb-item"><a href="inventory.php">Inventory</a></li>
                                        <li class="breadcrumb-item active">Add Item</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Add New Inventory Item</h4>
                                    <p class="card-title-desc">Fill in all required fields</p>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                                    <?php endif; ?>
                                    
                                    <form action="add_inventory_item.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                                    <div class="invalid-feedback">Please enter item name</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="sku">SKU <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($sku ?? ''); ?>" required>
                                                    <div class="invalid-feedback">Please enter SKU</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label class="form-label" for="description">Description</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="barcode">Barcode</label>
                                                    <input type="text" class="form-control" id="barcode" name="barcode" value="<?php echo htmlspecialchars($barcode ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="category">Category</label>
                                                    <select class="form-control select2" id="category" name="category">
                                                        <option value="">Select Category</option>
                                                        <?php foreach ($categories as $cat): ?>
                                                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category ?? '') === $cat ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($cat); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                        <option value="new_category">+ Add New Category</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3" id="new-category-field" style="display: none;">
                                                    <label class="form-label" for="new_category">New Category</label>
                                                    <input type="text" class="form-control" id="new_category" name="new_category">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label" for="quantity">Initial Quantity</label>
                                                    <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($quantity ?? 0); ?>" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label" for="cost">Cost <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">$</div>
                                                        <input type="number" class="form-control" id="cost" name="cost" value="<?php echo htmlspecialchars($cost ?? 0); ?>" min="0" step="0.01" required>
                                                    </div>
                                                    <div class="invalid-feedback">Please enter cost</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label" for="price">Selling Price <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <div class="input-group-text">$</div>
                                                        <input type="number" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($price ?? 0); ?>" min="0.01" step="0.01" required>
                                                    </div>
                                                    <div class="invalid-feedback">Please enter selling price</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label class="form-label" for="image">Product Image</label>
                                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                                    <small class="text-muted">Max file size: 2MB. Supported formats: JPG, PNG, GIF</small>
                                                    <div id="image-preview-container"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-4">
                                            <div class="col-md-12 text-end">
                                                <a href="inventory.php" class="btn btn-secondary me-2">Cancel</a>
                                                <button type="submit" class="btn btn-primary">Add Inventory Item</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'layouts/footer.php'; ?>
        </div>
    </div>
    
    <?php include 'layouts/vendor-scripts.php'; ?>
    
    <!-- Required js -->
    <script src="assets/libs/select2/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();
            
            // Handle category selection
            $('#category').on('change', function() {
                if ($(this).val() === 'new_category') {
                    $('#new-category-field').show();
                } else {
                    $('#new-category-field').hide();
                }
            });
            
            // Image preview
            $('#image').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#image-preview-container').html('<img src="' + e.target.result + '" class="image-preview" />');
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Form validation
            (function () {
                'use strict'
                
                // Fetch all forms we want to apply validation styles to
                var forms = document.querySelectorAll('.needs-validation')
                
                // Loop over them and prevent submission
                Array.prototype.slice.call(forms)
                    .forEach(function (form) {
                        form.addEventListener('submit', function (event) {
                            if (!form.checkValidity()) {
                                event.preventDefault()
                                event.stopPropagation()
                            }
                            
                            form.classList.add('was-validated')
                        }, false)
                    })
            })();
            
            // Auto generate SKU based on name
            $('#name').on('blur', function() {
                if ($('#sku').val() === '') {
                    const name = $(this).val().trim();
                    if (name) {
                        // Create SKU: first letters of words + timestamp
                        const words = name.split(' ');
                        let sku = '';
                        words.forEach(word => {
                            if (word) sku += word.charAt(0).toUpperCase();
                        });
                        sku += Math.floor(Date.now() / 1000).toString().substr(-6);
                        $('#sku').val(sku);
                    }
                }
            });
        });
    </script>
    
    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>
</html> 