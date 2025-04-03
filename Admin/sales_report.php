<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

// Set default date range (last 30 days)
$default_start_date = date('Y-m-d', strtotime('-30 days'));
$default_end_date = date('Y-m-d');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start_date;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end_date;
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

// Get the selected branch ID from session or query parameters
$selected_branch_id = null;

if (isset($_GET['branch_id'])) {
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

// Get payment methods for filter dropdown
$payment_methods = ['Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Mobile Payment', 'Other'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Sales Report | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
</head>

<body>
    <?php include 'layouts/body.php'; ?>

    <div id="layout-wrapper">
        <?php include 'layouts/menu.php'; ?>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Breadcrumb -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">
                                    Sales Report
                                    <?php if (!empty($branch_name)): ?>
                                        - <?php echo htmlspecialchars($branch_name); ?>
                                    <?php endif; ?>
                                </h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                        <li class="breadcrumb-item active">Sales Report</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" action="sales_report.php">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">End Date</label>
                                                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Payment Method</label>
                                                    <select class="form-select" name="payment_method">
                                                        <option value="">All Payment Methods</option>
                                                        <?php foreach ($payment_methods as $method): ?>
                                                            <?php $method_value = strtolower(str_replace(' ', '_', $method)); ?>
                                                            <option value="<?php echo $method_value; ?>" <?php echo $payment_method === $method_value ? 'selected' : ''; ?>>
                                                                <?php echo $method; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <?php if (isset($user_branches) && count($user_branches) > 1): ?>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Branch</label>
                                                    <select class="form-select" name="branch_id">
                                                        <?php foreach ($user_branches as $branch): ?>
                                                            <option value="<?php echo $branch['id']; ?>" 
                                                                <?php echo ($branch['id'] == $selected_branch_id) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($branch['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <input type="hidden" name="branch_id" value="<?php echo $selected_branch_id; ?>">
                                            <?php endif; ?>
                                            
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                                    <a href="sales_report.php" class="btn btn-secondary">Reset</a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sales Summary -->
                    <div class="row">
                        <?php
                        try {
                            // Build the WHERE clause for filtering
                            $where_clause = "WHERE sale_date BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)";
                            $params = [':start_date' => $start_date, ':end_date' => $end_date];
                            
                            // Add branch filter
                            $where_clause .= " AND sales.branch_id = :branch_id";
                            $params[':branch_id'] = $selected_branch_id;
                            
                            if (!empty($payment_method)) {
                                $where_clause .= " AND payment_method = :payment_method";
                                $params[':payment_method'] = $payment_method;
                            }
                            
                            // Get total sales
                            $query = "SELECT COUNT(*) as total_sales, SUM(total_amount) as total_revenue FROM sales $where_clause";
                            $stmt = $pdo->prepare($query);
                            foreach ($params as $key => $value) {
                                $stmt->bindValue($key, $value);
                            }
                            $stmt->execute();
                            $sales_summary = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Get total items sold
                            $query = "SELECT SUM(sale_items.quantity) as total_items FROM sale_items 
                                      JOIN sales ON sale_items.sale_id = sales.sale_id $where_clause";
                            $stmt = $pdo->prepare($query);
                            foreach ($params as $key => $value) {
                                $stmt->bindValue($key, $value);
                            }
                            $stmt->execute();
                            $items_summary = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Get average sale value
                            $avg_sale = $sales_summary['total_sales'] > 0 
                                ? $sales_summary['total_revenue'] / $sales_summary['total_sales'] 
                                : 0;
                        } catch (PDOException $e) {
                            echo "<div class='alert alert-danger'>Database error: " . $e->getMessage() . "</div>";
                            $sales_summary = ['total_sales' => 0, 'total_revenue' => 0];
                            $items_summary = ['total_items' => 0];
                            $avg_sale = 0;
                        }
                        ?>
                        
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Total Sales</p>
                                            <h4 class="mb-0"><?php echo number_format($sales_summary['total_sales']); ?></h4>
                                        </div>
                                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary align-self-center">
                                            <span class="avatar-title rounded-circle bg-primary">
                                                <i class="bx bx-shopping-bag font-size-24"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Total Revenue</p>
                                            <h4 class="mb-0">$<?php echo number_format($sales_summary['total_revenue'], 2); ?></h4>
                                        </div>
                                        <div class="mini-stat-icon avatar-sm rounded-circle bg-success align-self-center">
                                            <span class="avatar-title rounded-circle bg-success">
                                                <i class="bx bx-dollar font-size-24"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Items Sold</p>
                                            <h4 class="mb-0"><?php echo number_format($items_summary['total_items']); ?></h4>
                                        </div>
                                        <div class="mini-stat-icon avatar-sm rounded-circle bg-info align-self-center">
                                            <span class="avatar-title rounded-circle bg-info">
                                                <i class="bx bx-package font-size-24"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Average Sale</p>
                                            <h4 class="mb-0">$<?php echo number_format($avg_sale, 2); ?></h4>
                                        </div>
                                        <div class="mini-stat-icon avatar-sm rounded-circle bg-warning align-self-center">
                                            <span class="avatar-title rounded-circle bg-warning">
                                                <i class="bx bx-bar-chart-alt-2 font-size-24"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Sales List</h4>
                                    
                                    <table id="sales-datatable" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>Sale ID</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Items</th>
                                                <th>Total Amount</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            try {
                                                // Build the WHERE clause for filtering
                                                $where_clause = "WHERE s.sale_date BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)";
                                                $params = [':start_date' => $start_date, ':end_date' => $end_date];
                                                
                                                // Add branch filter
                                                $where_clause .= " AND s.branch_id = :branch_id";
                                                $params[':branch_id'] = $selected_branch_id;
                                                
                                                if (!empty($payment_method)) {
                                                    $where_clause .= " AND s.payment_method = :payment_method";
                                                    $params[':payment_method'] = $payment_method;
                                                }
                                                
                                                // Get sales list
                                                $query = "SELECT s.*, 
                                                         (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.sale_id) as item_count 
                                                         FROM sales s 
                                                         $where_clause 
                                                         ORDER BY s.sale_date DESC";
                                                         
                                                $stmt = $pdo->prepare($query);
                                                foreach ($params as $key => $value) {
                                                    $stmt->bindValue($key, $value);
                                                }
                                                $stmt->execute();
                                                $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                foreach ($sales as $sale) {
                                                    $sale_date = date('M d, Y h:i A', strtotime($sale['sale_date']));
                                                    $customer_name = !empty($sale['customer_name']) ? $sale['customer_name'] : 'Walk-in Customer';
                                                    
                                                    echo "<tr>
                                                        <td>{$sale['sale_id']}</td>
                                                        <td>{$sale_date}</td>
                                                        <td>{$customer_name}</td>
                                                        <td>{$sale['item_count']}</td>
                                                        <td>\${$sale['total_amount']}</td>
                                                        <td>
                                                            <button type='button' class='btn btn-sm btn-info view-sale' data-id='{$sale['sale_id']}'>
                                                                <i class='mdi mdi-eye'></i> View
                                                            </button>
                                                            <button type='button' class='btn btn-sm btn-primary print-receipt' data-id='{$sale['sale_id']}'>
                                                                <i class='mdi mdi-printer'></i> Receipt
                                                            </button>
                                                        </td>
                                                    </tr>";
                                                }
                                            } catch (PDOException $e) {
                                                echo "<tr><td colspan='7' class='text-center'>Database error: " . $e->getMessage() . "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'layouts/footer.php'; ?>
        </div>
    </div>

    <!-- Sale Details Modal -->
    <div class="modal fade" id="sale-details-modal" tabindex="-1" aria-labelledby="saleDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="saleDetailsModalLabel">Sale Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sale-info">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Sale ID:</strong> <span id="modal-sale-id"></span></p>
                                <p><strong>Date:</strong> <span id="modal-sale-date"></span></p>
                                <p><strong>Customer:</strong> <span id="modal-customer-name"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Payment Method:</strong> <span id="modal-payment-method"></span></p>
                                <p><strong>Total Amount:</strong> <span id="modal-total-amount"></span></p>
                                <p><strong>Created By:</strong> <span id="modal-created-by"></span></p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <p><strong>Notes:</strong> <span id="modal-notes"></span></p>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Items</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="modal-sale-items">
                                <!-- Sale items will be added here dynamically -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th id="modal-items-total"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="modal-print-receipt">Print Receipt</button>
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
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
    <script src="assets/libs/jszip/jszip.min.js"></script>
    <script src="assets/libs/pdfmake/build/pdfmake.min.js"></script>
    <script src="assets/libs/pdfmake/build/vfs_fonts.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="assets/libs/datatables.net-buttons/js/buttons.colVis.min.js"></script>
    <script src="assets/libs/flatpickr/flatpickr.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    
    <style>
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
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable with server-side processing
            var table = $('#sales-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'ajax/get_sales.php',
                    type: 'POST',
                    data: function(d) {
                        d.start_date = '<?php echo $start_date; ?>';
                        d.end_date = '<?php echo $end_date; ?>';
                        d.payment_method = '<?php echo $payment_method; ?>';
                        d.branch_id = '<?php echo $selected_branch_id; ?>';
                    }
                },
                columns: [
                    { data: 'sale_id' },
                    { data: 'date' },
                    { data: 'customer' },
                    { data: 'items' },
                    { data: 'total_amount' },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-info btn-sm view-sale" data-id="${row.sale_id}">
                                    <i class="mdi mdi-eye"></i>
                                </button>
                                <button class="btn btn-primary btn-sm print-receipt" data-id="${row.sale_id}">
                                    <i class="mdi mdi-printer"></i>
                                </button>
                            `;
                        }
                    }
                ],
                order: [[1, 'desc']],
                pageLength: 25,
                responsive: true
            });

            // Handle view sale button click
            $('#sales-datatable').on('click', '.view-sale', function() {
                var saleId = $(this).data('id');
                $.ajax({
                    url: 'ajax/get_sale_details.php',
                    type: 'GET',
                    data: { 
                        sale_id: saleId,
                        branch_id: '<?php echo $selected_branch_id; ?>' 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            Swal.fire('Error', response.error, 'error');
                            return;
                        }
                        
                        // Show sale details in a modal
                        Swal.fire({
                            title: 'Sale Details',
                            html: `
                                <div class="text-start">
                                    <p><strong>Sale ID:</strong> ${response.sale_id}</p>
                                    <p><strong>Date:</strong> ${response.date}</p>
                                    <p><strong>Items:</strong> ${response.items}</p>
                                    <p><strong>Total Amount:</strong> $${response.total_amount}</p>
                                    <p><strong>Status:</strong> ${response.status}</p>
                                </div>
                            `,
                            icon: 'info'
                        });
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error:", textStatus, errorThrown);
                        Swal.fire('Error', 'Failed to fetch sale details: ' + errorThrown, 'error');
                    }
                });
            });

            // Handle print receipt button click
            $('#sales-datatable').on('click', '.print-receipt', function() {
                var saleId = $(this).data('id');
                window.open('ajax/print_receipt.php?sale_id=' + saleId + '&branch_id=<?php echo $selected_branch_id; ?>', '_blank');
            });
        });
    </script>
</body>

</html> 