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

// Set default values for dates
$default_start_date = date('Y-m-d', strtotime('-30 days'));
$default_end_date = date('Y-m-d');

// Get filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start_date;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end_date;

// Validate dates
if (!validateDate($start_date) || !validateDate($end_date)) {
    $_SESSION['error'] = "Invalid date format.";
    header("Location: sales.php");
    exit();
}

// Create date objects for comparison
$start_date_obj = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);
$end_date_obj->setTime(23, 59, 59); // Set end date to end of day

// Add one day to end date for SQL BETWEEN to include the end date
$end_date_sql = date('Y-m-d', strtotime($end_date . ' +1 day'));

// Function to validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Initialize variables
$sales = [];
$total_revenue = 0;
$sales_count = 0;

try {
// Fetch sales data
    $query = "SELECT s.sale_id, s.sale_date, s.total_amount, s.notes, 
             GROUP_CONCAT(si.product_id, ':', si.quantity, ':', si.price SEPARATOR '|') as items 
             FROM sales s 
             LEFT JOIN sale_items si ON s.sale_id = si.sale_id 
             WHERE s.sale_date BETWEEN ? AND ? 
             GROUP BY s.sale_id 
             ORDER BY s.sale_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date_sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
        $total_revenue += $row['total_amount'];
        $sales_count++;
    }
    
    // Calculate statistics
    $average_sale = $sales_count > 0 ? $total_revenue / $sales_count : 0;
    
} catch (Exception $e) {
    error_log("Error fetching sales data: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching sales data.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sales Report | Admin Template</title>
    <?php include 'layouts/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
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
                        <h4 class="card-title">Sales Report</h4>
                        <form method="GET" action="sales.php" class="row g-3 mb-4">
                            <div class="col-auto">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="col-auto">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="col-auto align-self-end">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </form>
                        <table id="salesTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Sale ID</th>
                                    <th>Date & Time</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['sale_id']); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($sale['sale_date'])); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($sale['items'])) {
                                            $items_array = explode('|', $sale['items']);
                                            echo count($items_array) . ' item(s)';
                                        } else {
                                            echo '0 items';
                                        }
                                        ?>
                                    </td>
                                    <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info view-receipt" data-id="<?php echo $sale['sale_id']; ?>">
                                            <i class="fas fa-receipt"></i> View Receipt
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Inventory Statistics Cards -->
                <div class="row">
                    <!-- Total Sales Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Sales</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $sales_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Revenue</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_revenue, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Average Sale Card -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Average Sale Value</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($average_sale, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#salesTable').DataTable({
        ordering: true,
        order: [[1, 'desc']], // Sort by date descending
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });
    
    // View Receipt
    $('.view-receipt').on('click', function() {
        const saleId = $(this).data('id');
        
        // Show loading
        $('#receipt-content').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading receipt...</div>');
        $('#receipt-modal').modal('show');
        
        // Fetch receipt data
        $.ajax({
            url: 'ajax/get_receipt.php',
            type: 'POST',
            data: {
                sale_id: saleId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Build receipt HTML
                    let receiptHtml = `
                        <div class="receipt-header text-center mb-3">
                            <h4>Gym Management System</h4>
                            <p>Sale Receipt</p>
                            <p>Date: ${new Date(response.data.sale_date).toLocaleDateString()} ${new Date(response.data.sale_date).toLocaleTimeString()}</p>
                            <p>Receipt #: ${response.data.sale_id}</p>
                        </div>
                        <div class="receipt-items mb-3">
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
                    
                    // Add items
                    response.data.items.forEach(item => {
                        const itemTotal = item.price * item.quantity;
                        receiptHtml += `
                            <tr>
                                <td style="text-align:left">${item.name}</td>
                                <td style="text-align:right">${item.quantity}</td>
                                <td style="text-align:right">$${parseFloat(item.price).toFixed(2)}</td>
                                <td style="text-align:right">$${itemTotal.toFixed(2)}</td>
                            </tr>`;
                    });
                    
                    receiptHtml += `
                                </tbody>
                            </table>
                        </div>
                        <div class="receipt-total mb-3">
                            <table style="width:100%">
                                <tr>
                                    <td style="text-align:left">Total:</td>
                                    <td style="text-align:right">$${parseFloat(response.data.total_amount).toFixed(2)}</td>
                                </tr>
                            </table>
                        </div>`;
                    
                    // Add notes if available
                    if (response.data.notes) {
                        receiptHtml += `
                            <div class="receipt-notes mb-3">
                                <p><strong>Notes:</strong> ${response.data.notes}</p>
                            </div>`;
                    }
                    
                    receiptHtml += `
                        <div class="receipt-footer text-center">
                            <p>Thank you for your purchase!</p>
                            <p>Please come again.</p>
                        </div>`;
                    
                    // Update receipt content
                    $('#receipt-content').html(receiptHtml);
                    
                } else {
                    $('#receipt-content').html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function() {
                $('#receipt-content').html('<div class="alert alert-danger">An error occurred while fetching the receipt</div>');
            }
        });
    });
    
    // Print receipt
    $('#print-receipt').on('click', function() {
        const content = document.getElementById('receipt-content').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Sales Receipt</title>
                <style>
                    body { font-family: 'Courier New', monospace; }
                    .text-center { text-align: center; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 5px; }
                </style>
            </head>
            <body>
                ${content}
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
    
    // Export to CSV
    $('#export-csv').on('click', function(e) {
        e.preventDefault();
        
        // Show loading
        Swal.fire({
            title: 'Exporting...',
            text: 'Please wait while we prepare your CSV file',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: 'ajax/export_sales.php',
            type: 'POST',
            data: {
                format: 'csv',
                start_date: '<?php echo $start_date; ?>',
                end_date: '<?php echo $end_date; ?>'
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(blob) {
                Swal.close();
                
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'sales_report_<?php echo date("Y-m-d"); ?>.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while exporting the CSV file',
                    icon: 'error'
                });
            }
        });
    });
    
    // Export to PDF
    $('#export-pdf').on('click', function(e) {
        e.preventDefault();
        
        // Show loading
        Swal.fire({
            title: 'Exporting...',
            text: 'Please wait while we prepare your PDF file',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: 'ajax/export_sales.php',
            type: 'POST',
            data: {
                format: 'pdf',
                start_date: '<?php echo $start_date; ?>',
                end_date: '<?php echo $end_date; ?>'
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(blob) {
                Swal.close();
                
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'sales_report_<?php echo date("Y-m-d"); ?>.pdf';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while exporting the PDF file',
                    icon: 'error'
                });
            }
        });
    });
});
</script>

<!-- Receipt Modal -->
<div class="modal fade" id="receipt-modal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalLabel">Sales Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="receipt-content" class="p-3" style="font-family: 'Courier New', monospace;">
                    <!-- Receipt content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="print-receipt">Print Receipt</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>