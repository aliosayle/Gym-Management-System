<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Check if client ID is provided
if (!isset($_GET['id'])) {
    echo "Error: Client ID is required";
    exit;
}

$client_id = $_GET['id'];

// Fetch client information
$client_query = "SELECT * FROM clients WHERE client_id = :client_id";
$client_stmt = $pdo->prepare($client_query);
$client_stmt->execute(['client_id' => $client_id]);
$client = $client_stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo "Error: Client not found";
    exit;
}

// Fetch packages
$packages_query = "SELECT * FROM packages ORDER BY price ASC";
$packages_stmt = $pdo->prepare($packages_query);
$packages_stmt->execute();
$packages = $packages_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_id = $_POST['package_id'];
    
    if (empty($package_id)) {
        $error = "Please select a package";
    } else {
        // Redirect to the renewal script with parameters
        header("Location: renew_subscription.php?id=$client_id&package=$package_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Simple Renewal | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
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
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Simple Renewal</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>
                                    <li class="breadcrumb-item active">Simple Renewal</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-8 col-md-10 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Renew Subscription for <?php echo htmlspecialchars($client['name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error; ?>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="package_id" class="form-label">Select Package</label>
                                        <select id="package_id" name="package_id" class="form-select" required>
                                            <option value="">-- Select a package --</option>
                                            <?php foreach ($packages as $package): ?>
                                            <option value="<?php echo htmlspecialchars($package['id']); ?>" 
                                                    data-price="<?php echo htmlspecialchars($package['price']); ?>" 
                                                    data-days="<?php echo htmlspecialchars($package['number_of_days']); ?>">
                                                <?php echo htmlspecialchars($package['name']); ?> - 
                                                $<?php echo htmlspecialchars($package['price']); ?> / 
                                                <?php echo htmlspecialchars($package['number_of_days']); ?> days
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h5 class="card-title">Subscription Summary</h5>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Package:</span>
                                                    <span id="summary_package">-</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Duration:</span>
                                                    <span id="summary_duration">-</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Total Price:</span>
                                                    <span id="summary_price">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="clients.php" class="btn btn-secondary me-md-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary">Renew Subscription</button>
                                    </div>
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

<script>
    $(document).ready(function() {
        function updateSummary() {
            var selectedOption = $('#package_id option:selected');
            var packageName = selectedOption.text() || '-';
            var price = parseFloat(selectedOption.data('price')) || 0;
            var days = parseInt(selectedOption.data('days')) || 0;
            
            $('#summary_package').text(packageName);
            $('#summary_duration').text(days + ' days');
            $('#summary_price').text('$' + price.toFixed(2));
        }
        
        // Update summary when package changes
        $('#package_id').on('change', updateSummary);
        
        // Initialize summary
        updateSummary();
    });
</script>

</body>
</html> 