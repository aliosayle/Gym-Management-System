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

// Fetch sales data
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

$sales_query = "SELECT s.user_id, s.total_amount, s.sale_date, 
                GROUP_CONCAT(CONCAT(p.description, ':', si.quantity, ':', si.price) SEPARATOR ',') AS items
                FROM sales s
                JOIN sale_items si ON s.sale_id = si.sale_id
                JOIN products p ON si.product_id = p.product_id
                WHERE DATE(s.sale_date) BETWEEN :from_date AND :to_date
                GROUP BY s.sale_id";
$sales_stmt = $pdo->prepare($sales_query);
$sales_stmt->execute(['from_date' => $from_date, 'to_date' => $to_date]);
$sales = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                <label for="from_date" class="form-label">From</label>
                                <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                            </div>
                            <div class="col-auto">
                                <label for="to_date" class="form-label">To</label>
                                <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                            </div>
                            <div class="col-auto align-self-end">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </form>
                        <table id="salesTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Sale Date</th>
                                    <th>User ID</th>
                                    <th>Total Amount</th>
                                    <th>Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['user_id']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></td>
                                    <td>
                                        <?php
                                        $items = explode(',', $sale['items']);
                                        foreach ($items as $item) {
                                            list($description, $quantity, $price) = explode(':', $item);
                                            echo "Description: $description, Quantity: $quantity, Price: $" . number_format($price, 2) . "<br>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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

<?php include 'layouts/vendor-scripts.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#salesTable').DataTable({
        "order": [[0, "desc"]]
    });
});
</script>
</body>
</html>