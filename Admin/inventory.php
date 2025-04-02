<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/layout-pre-content.php';

// Check if user has permission to manage inventory
$can_manage = isset($_SESSION['permissions']) && in_array('manage_inventory', $_SESSION['permissions']);
if (!$can_manage) {
    $_SESSION['error'] = "You don't have permission to access inventory management.";
    header("Location: index.php");
    exit();
}

// Create inventory_items table if it doesn't exist
try {
    $check_table = "SHOW TABLES LIKE 'inventory_items'";
    $table_exists = $con->query($check_table);
    
    if ($table_exists->num_rows == 0) {
        // Create inventory_items table
        $create_table = "CREATE TABLE `inventory_items` (
            `item_id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `sku` varchar(50) DEFAULT NULL,
            `barcode` varchar(100) DEFAULT NULL,
            `category` varchar(100) DEFAULT NULL,
            `purchase_price` decimal(10,2) DEFAULT '0.00',
            `selling_price` decimal(10,2) DEFAULT '0.00',
            `stock_quantity` int(11) DEFAULT '0',
            `image_src` varchar(255) DEFAULT NULL,
            `low_stock_threshold` int(11) DEFAULT '5',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`item_id`),
            UNIQUE KEY `sku` (`sku`),
            UNIQUE KEY `barcode` (`barcode`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $con->query($create_table);
        
        // Create inventory_transactions table
        $create_transactions_table = "CREATE TABLE `inventory_transactions` (
            `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
            `item_id` int(11) NOT NULL,
            `quantity` int(11) NOT NULL,
            `transaction_type` enum('purchase','sale','adjustment','return','transfer') NOT NULL,
            `user_id` int(11) NOT NULL,
            `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `notes` text,
            PRIMARY KEY (`transaction_id`),
            KEY `item_id` (`item_id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $con->query($create_transactions_table);
    }
} catch (Exception $e) {
    error_log("Error creating inventory tables: " . $e->getMessage());
}

// Get inventory statistics
try {
    // Total items
    $total_items_query = "SELECT COUNT(*) as total FROM inventory_items";
    $total_items_result = $con->query($total_items_query);
    $total_items = $total_items_result->fetch_assoc()['total'] ?? 0;
    
    // Total value
    $total_value_query = "SELECT SUM(purchase_price * stock_quantity) as total_value FROM inventory_items";
    $total_value_result = $con->query($total_value_query);
    $total_value = $total_value_result->fetch_assoc()['total_value'] ?? 0;
    
    // Low stock items
    $low_stock_query = "SELECT COUNT(*) as low_stock FROM inventory_items 
                        WHERE stock_quantity <= low_stock_threshold AND stock_quantity > 0";
    $low_stock_result = $con->query($low_stock_query);
    $low_stock = $low_stock_result->fetch_assoc()['low_stock'] ?? 0;
    
    // Out of stock items
    $out_of_stock_query = "SELECT COUNT(*) as out_of_stock FROM inventory_items WHERE stock_quantity = 0";
    $out_of_stock_result = $con->query($out_of_stock_query);
    $out_of_stock = $out_of_stock_result->fetch_assoc()['out_of_stock'] ?? 0;
    
    // Get all inventory items
    $items_query = "SELECT * FROM inventory_items ORDER BY name";
    $items_result = $con->query($items_query);
    $inventory_items = [];
    
    if ($items_result) {
        while ($row = $items_result->fetch_assoc()) {
            $inventory_items[] = $row;
        }
    }
    
} catch (Exception $e) {
    error_log("Error fetching inventory data: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching inventory data.";
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Inventory Management</h1>
        <a href="add_inventory_item.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Item
        </a>
    </div>

    <?php include 'includes/alerts.php'; ?>

    <!-- Inventory Statistics Cards -->
    <div class="row">
        <!-- Total Items Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_items; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Value Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_value, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Items Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Low Stock Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $low_stock; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Out of Stock Items Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Out of Stock</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $out_of_stock; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Items Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Inventory Items</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Export Options:</div>
                    <a class="dropdown-item" href="#">Export CSV</a>
                    <a class="dropdown-item" href="#">Export PDF</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">Print Inventory</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="inventoryTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($inventory_items)): ?>
                            <?php foreach ($inventory_items as $item): ?>
                                <?php 
                                    $stock_class = '';
                                    if ($item['stock_quantity'] <= 0) {
                                        $stock_class = 'text-danger font-weight-bold';
                                    } else if ($item['stock_quantity'] <= $item['low_stock_threshold']) {
                                        $stock_class = 'text-warning font-weight-bold';
                                    }
                                    
                                    $image_src = !empty($item['image_src']) ? $item['image_src'] : 'assets/images/no-image.png';
                                ?>
                                <tr>
                                    <td style="width: 60px;">
                                        <img src="<?php echo htmlspecialchars($image_src); ?>" class="img-fluid" style="max-height: 50px;" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td>$<?php echo number_format($item['purchase_price'], 2); ?></td>
                                    <td>$<?php echo number_format($item['selling_price'], 2); ?></td>
                                    <td class="<?php echo $stock_class; ?>"><?php echo $item['stock_quantity']; ?></td>
                                    <td style="width: 180px;">
                                        <div class="btn-group">
                                            <a href="inventory_add_stock.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-success" title="Add Stock">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                            <a href="inventory_remove_stock.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-warning" title="Remove Stock">
                                                <i class="fas fa-minus"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-info view-history" data-id="<?php echo $item['item_id']; ?>" title="View History">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            <a href="edit_inventory_item.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary" title="Edit Item">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-item" data-id="<?php echo $item['item_id']; ?>" title="Delete Item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No inventory items found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Transaction History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">Transaction History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="history-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading transaction history...</p>
                </div>
                <div id="history-content" style="display: none;">
                    <div class="item-details mb-4">
                        <h6 class="item-name font-weight-bold"></h6>
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>SKU:</strong> <span class="item-sku"></span></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Current Stock:</strong> <span class="item-stock"></span></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Value:</strong> $<span class="item-value"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="historyTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>User</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body">
                                <!-- Transaction history will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="history-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="print-history">Print History</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#inventoryTable').DataTable({
        "order": [[1, "asc"]], // Sort by name
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [0, 8] } // Disable sorting on image and actions columns
        ]
    });
    
    // View Transaction History
    $('.view-history').on('click', function() {
        const itemId = $(this).data('id');
        
        // Show loading
        $('#history-content').hide();
        $('#history-error').hide();
        $('#history-loading').show();
        
        // Show modal
        $('#historyModal').modal('show');
        
        // Get transaction history
        $.ajax({
            url: 'ajax/get_transaction_history.php',
            type: 'POST',
            data: {
                item_id: itemId
            },
            dataType: 'json',
            success: function(response) {
                $('#history-loading').hide();
                
                if (response.status === 'success') {
                    // Populate item details
                    $('.item-name').text(response.item.name);
                    $('.item-sku').text(response.item.sku || 'N/A');
                    $('.item-stock').text(response.item.stock_quantity);
                    $('.item-value').text((response.item.purchase_price * response.item.stock_quantity).toFixed(2));
                    
                    // Populate transaction history
                    $('#history-table-body').empty();
                    
                    if (response.transactions.length > 0) {
                        response.transactions.forEach(function(transaction) {
                            const date = new Date(transaction.transaction_date);
                            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                            
                            let typeClass = '';
                            if (transaction.transaction_type === 'purchase' || transaction.transaction_type === 'adjustment' && transaction.quantity > 0) {
                                typeClass = 'text-success';
                            } else if (transaction.transaction_type === 'sale' || transaction.transaction_type === 'adjustment' && transaction.quantity < 0) {
                                typeClass = 'text-danger';
                            }
                            
                            const row = `
                                <tr>
                                    <td>${formattedDate}</td>
                                    <td class="${typeClass}">${transaction.transaction_type.toUpperCase()}</td>
                                    <td class="${typeClass}">${transaction.quantity}</td>
                                    <td>${transaction.username || 'N/A'}</td>
                                    <td>${transaction.notes || 'N/A'}</td>
                                </tr>
                            `;
                            
                            $('#history-table-body').append(row);
                        });
                    } else {
                        $('#history-table-body').html('<tr><td colspan="5" class="text-center">No transactions found</td></tr>');
                    }
                    
                    $('#history-content').show();
                } else {
                    $('#history-error').text(response.message).show();
                }
            },
            error: function() {
                $('#history-loading').hide();
                $('#history-error').text('An error occurred while fetching transaction history').show();
            }
        });
    });
    
    // Delete Item
    $('.delete-item').on('click', function() {
        const itemId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete this inventory item and all its transaction history. You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading overlay
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the item',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Delete item
                $.ajax({
                    url: 'ajax/delete_inventory_item.php',
                    type: 'POST',
                    data: {
                        item_id: itemId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while deleting the item',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }
        });
    });
    
    // Print History
    $('#print-history').on('click', function() {
        const itemName = $('.item-name').text();
        const printWindow = window.open('', '_blank');
        
        const historyContent = $('#history-content').html();
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Transaction History - ${itemName}</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .text-success { color: green; }
                    .text-danger { color: red; }
                </style>
            </head>
            <body>
                <h2>Transaction History - ${itemName}</h2>
                ${historyContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        
        // Print after a slight delay to ensure content is loaded
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 250);
    });
});
</script>

<?php
include 'includes/layout-post-content.php';
?> 