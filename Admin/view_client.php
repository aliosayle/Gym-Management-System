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

// Include permission checks
include 'layouts/check_permission.php';

// Get client ID from GET parameter
$client_id = isset($_GET['id']) ? $_GET['id'] : null;
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : (isset($_SESSION['selected_branch_id']) ? $_SESSION['selected_branch_id'] : 1);

if (!$client_id) {
    echo "<script>alert('No client ID provided.'); window.location.href = 'clients.php';</script>";
    exit;
}

// Fetch client details
$query = "SELECT * FROM clients WHERE client_id = :client_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['client_id' => $client_id]);

if ($stmt->rowCount() == 0) {
    echo "<script>alert('Client not found.'); window.location.href = 'clients.php';</script>";
    exit;
}

$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch client's package
$package_query = "SELECT p.* FROM packages p WHERE p.id = :package_id";
$package_stmt = $pdo->prepare($package_query);
$package_stmt->execute(['package_id' => $client['package_id']]);
$package = $package_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch payment history
$payment_query = "SELECT p.*, pk.name as package_name 
                 FROM payments p 
                 LEFT JOIN packages pk ON p.package_id = pk.id
                 WHERE p.client_id = :client_id 
                 ORDER BY p.payment_date DESC";
$payment_stmt = $pdo->prepare($payment_query);
$payment_stmt->execute(['client_id' => $client_id]);
$payments = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subscription statistics
$total_spent = 0;
$membership_days = 0;
$membership_count = 0;

foreach ($payments as $payment) {
    if ($payment['payment_status'] == 'Completed') {
        $total_spent += $payment['amount'];
        $membership_count++;
        
        // Try to get days from package
        $pkg_query = "SELECT number_of_days FROM packages WHERE id = :id";
        $pkg_stmt = $pdo->prepare($pkg_query);
        $pkg_stmt->execute(['id' => $payment['package_id']]);
        $pkg_result = $pkg_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pkg_result) {
            $membership_days += $pkg_result['number_of_days'];
        }
    }
}

// Get pending payment if exists
$pending_payment_query = "SELECT * FROM payments WHERE client_id = :client_id AND payment_status = 'Pending'";
$pending_payment_stmt = $pdo->prepare($pending_payment_query);
$pending_payment_stmt->execute(['client_id' => $client_id]);
$pending_payment = $pending_payment_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Client Details | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
    <style>
        .client-profile-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .client-cover {
            height: 120px;
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
        }
        .client-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 3px solid #fff;
            margin-top: -45px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #6c757d;
        }
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .timeline {
            list-style-type: none;
            position: relative;
            padding-left: 30px;
        }
        .timeline:before {
            content: ' ';
            background: #dee2e6;
            display: inline-block;
            position: absolute;
            left: 10px;
            width: 2px;
            height: 100%;
            z-index: 1;
        }
        .timeline-item {
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-item:before {
            content: ' ';
            background: #fff;
            display: inline-block;
            position: absolute;
            border-radius: 50%;
            border: 3px solid #dee2e6;
            left: -37px;
            width: 20px;
            height: 20px;
            z-index: 2;
            top: 5px;
        }
        .timeline-item.payment-completed:before {
            border-color: #28a745;
            background-color: #d4edda;
        }
        .timeline-item.payment-pending:before {
            border-color: #ffc107;
            background-color: #fff3cd;
        }
        .timeline-item.payment-refunded:before {
            border-color: #dc3545;
            background-color: #f8d7da;
        }
        .timeline-date {
            font-size: 0.8rem;
            color: #6c757d;
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
                <!-- Page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Client Details</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>
                                    <li class="breadcrumb-item active">Client Details</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Client Profile -->
                <div class="row">
                    <div class="col-xl-4">
                        <div class="card client-profile-card">
                            <div class="card-body p-0">
                                <div class="client-cover"></div>
                                <div class="p-4 text-center">
                                    <div class="client-avatar mx-auto">
                                        <i class="mdi mdi-account"></i>
                                    </div>
                                    <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($client['name']); ?></h4>
                                    <p class="text-muted mb-1">
                                        <i class="mdi mdi-phone me-1"></i> <?php echo htmlspecialchars($client['phone_number']); ?>
                                    </p>
                                    <p class="badge bg-<?php 
                                        echo $client['subscription_status'] === 'active' ? 'success' : 
                                            ($client['subscription_status'] === 'expired' ? 'danger' : 'warning'); 
                                    ?> mb-3">
                                        <?php echo ucfirst(htmlspecialchars($client['subscription_status'])); ?>
                                    </p>
                                    
                                    <?php if ($pending_payment): ?>
                                    <div class="alert alert-warning" role="alert">
                                        <i class="mdi mdi-alert-circle me-2"></i>
                                        Pending Payment: $<?php echo htmlspecialchars($pending_payment['amount']); ?>
                                        <button type="button" class="btn btn-sm btn-warning confirm-payment float-end" 
                                                data-id="<?php echo htmlspecialchars($pending_payment['payment_id']); ?>">
                                            <i class="mdi mdi-cash"></i> Confirm
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2 mt-4">
                                        <a href="edit_client.php?id=<?php echo htmlspecialchars($client_id); ?>&branch_id=<?php echo htmlspecialchars($branch_id); ?>" 
                                           class="btn btn-primary">
                                            <i class="mdi mdi-pencil me-1"></i> Edit Client
                                        </a>
                                        <button type="button" class="btn btn-success renew-subscription" 
                                                data-id="<?php echo htmlspecialchars($client_id); ?>">
                                            <i class="mdi mdi-refresh me-1"></i> Renew Subscription
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Client Details Card -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Client Information</h5>
                                
                                <div class="table-responsive">
                                    <table class="table table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Client ID:</th>
                                                <td><?php echo htmlspecialchars($client['client_id']); ?></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Created On:</th>
                                                <td><?php echo date('F j, Y', strtotime($client['created_at'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Subscription Ends:</th>
                                                <td>
                                                    <?php if (!empty($client['subscription_end_date'])): ?>
                                                        <?php 
                                                            $end_date = new DateTime($client['subscription_end_date']);
                                                            $now = new DateTime();
                                                            $interval = $now->diff($end_date);
                                                            $days_left = $interval->days;
                                                            $is_past = $end_date < $now;
                                                        ?>
                                                        <span class="<?php echo $is_past ? 'text-danger' : ($days_left <= 7 ? 'text-warning' : 'text-success'); ?>">
                                                            <?php echo date('F j, Y', strtotime($client['subscription_end_date'])); ?>
                                                            <?php if (!$is_past && $days_left <= 30): ?>
                                                                <small>(<?php echo $days_left; ?> days left)</small>
                                                            <?php elseif ($is_past): ?>
                                                                <small>(Expired)</small>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not set</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Current Package:</th>
                                                <td>
                                                    <?php if ($package): ?>
                                                        <?php echo htmlspecialchars($package['name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">None</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-8">
                        <!-- Stats Cards -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card stats-card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title text-white mb-1">Total Spent</h5>
                                                <h4 class="mb-0">$<?php echo number_format($total_spent, 2); ?></h4>
                                            </div>
                                            <div class="avatar-sm rounded-circle bg-white text-primary">
                                                <span class="avatar-title">
                                                    <i class="mdi mdi-cash-multiple font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stats-card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title text-white mb-1">Memberships</h5>
                                                <h4 class="mb-0"><?php echo $membership_count; ?></h4>
                                            </div>
                                            <div class="avatar-sm rounded-circle bg-white text-success">
                                                <span class="avatar-title">
                                                    <i class="mdi mdi-calendar-check font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stats-card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title text-white mb-1">Member For</h5>
                                                <h4 class="mb-0"><?php echo $membership_days; ?> days</h4>
                                            </div>
                                            <div class="avatar-sm rounded-circle bg-white text-info">
                                                <span class="avatar-title">
                                                    <i class="mdi mdi-clock-outline font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment History -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Payment History</h5>
                                
                                <?php if (count($payments) > 0): ?>
                                <ul class="verti-timeline list-unstyled">
                                    <?php foreach ($payments as $payment): ?>
                                    <li class="event-list">
                                        <div class="event-timeline-dot">
                                            <i class="mdi mdi-cash-multiple font-size-18 <?php echo ($payment['payment_status'] === 'Pending') ? 'text-warning' : 'text-success'; ?>"></i>
                                        </div>
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <div>
                                                    <h5 class="font-size-15 mb-1">
                                                        <?php echo ($payment['payment_status'] === 'Pending') ? 'Payment Pending' : 'Payment Confirmed'; ?>
                                                    </h5>
                                                    <p class="text-muted"><?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></p>
                                                    <p>Package: <span class="font-weight-bold"><?php echo $payment['package_name']; ?></span></p>
                                                    <p>Amount: <span class="font-weight-bold"><?php echo $payment['amount']; ?> <?php echo $company_info['currency']; ?></span></p>
                                                    
                                                    <?php if ($payment['payment_status'] === 'Pending'): ?>
                                                    <div class="d-flex align-items-center">
                                                        <button type="button" class="btn btn-sm btn-success confirm-payment" data-id="<?php echo $payment['payment_id']; ?>">
                                                            <i class="mdi mdi-check me-1"></i>Confirm Payment
                                                        </button>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else: ?>
                                <div class="alert alert-info mb-0">
                                    <i class="mdi mdi-information-outline me-2"></i>
                                    No payment history found for this client.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Back Button -->
                <div class="row mt-4">
                    <div class="col-12">
                        <a href="clients.php?branch_id=<?php echo htmlspecialchars($branch_id); ?>" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left me-1"></i> Back to Clients
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/vendor-scripts.php'; ?>
<script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="assets/js/app.js"></script>

<script>
/* Fallback for jQuery if it didn't load properly */
if (typeof jQuery === 'undefined') {
    document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>');
}

function initializeWhenJQueryIsReady() {
    if (typeof jQuery === 'undefined') {
        setTimeout(initializeWhenJQueryIsReady, 100);
        return;
    }
    
    jQuery(document).ready(function($) {
        // Confirm payment functionality
        $('.confirm-payment').on('click', function () {
            var paymentId = $(this).data('id');
            
            // Simple confirmation dialog that works in all browsers
            if (confirm('Are you sure you want to confirm this payment?')) {
                window.location.href = 'confirm_payment.php?id=' + paymentId + '&redirect=view_client.php%3Fid%3D<?php echo urlencode($client_id); ?>%26branch_id%3D<?php echo urlencode($branch_id); ?>';
            }
        });
        
        // Renew subscription functionality
        $('.renew-subscription').on('click', function () {
            var clientId = $(this).data('id');
            
            // First check if SweetAlert is available
            if (typeof Swal === 'undefined') {
                if (!confirm('SweetAlert is not available. Continue with basic confirmation?\nClient ID: ' + clientId)) {
                    return;
                }
                
                window.location.href = 'simple_renewal.php?id=' + clientId + '&redirect=view_client.php%3Fid%3D<?php echo urlencode($client_id); ?>%26branch_id%3D<?php echo urlencode($branch_id); ?>';
                return;
            }
            
            Swal.fire({
                title: 'Renew Subscription',
                html: `
                    <form id="renewForm" class="mt-3">
                        <div class="mb-3">
                            <label for="package" class="form-label">Select Package</label>
                            <select id="package" class="form-select">
                                <option value="">Loading packages...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" id="startDate" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                            <small class="text-muted">Default is today's date</small>
                        </div>
                        <div id="packageDetails" class="alert alert-info mt-3" style="display: none;">
                            <div class="d-flex justify-content-between">
                                <span>Price:</span>
                                <span id="packagePrice">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Duration:</span>
                                <span id="packageDuration">0 days</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>End Date:</span>
                                <span id="calculatedEndDate">-</span>
                            </div>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Renew',
                showLoaderOnConfirm: true,
                didOpen: () => {
                    // Fetch packages
                    $.ajax({
                        url: 'ajax/get_packages.php',
                        type: 'GET',
                        data: { branch_id: <?php echo $branch_id; ?> },
                        dataType: 'json',
                        success: function(data) {
                            var options = '';
                            $.each(data, function(index, package) {
                                options += `<option value="${package.id}" 
                                            data-price="${package.price}" 
                                            data-days="${package.number_of_days}">
                                            ${package.name} - $${package.price} (${package.number_of_days} days)
                                            </option>`;
                            });
                            $('#package').html(options);
                            
                            // Show package details for first option
                            updatePackageDetails();
                        },
                        error: function(xhr, status, error) {
                            $('#package').html('<option value="">Error loading packages</option>');
                            Swal.showValidationMessage('Failed to load packages: ' + error);
                        }
                    });
                    
                    // Handle package change
                    $(document).on('change', '#package', updatePackageDetails);
                    $(document).on('change', '#startDate', updatePackageDetails);
                    
                    function updatePackageDetails() {
                        var selectedOption = $('#package option:selected');
                        var price = selectedOption.data('price') || 0;
                        var days = selectedOption.data('days') || 0;
                        var startDate = new Date($('#startDate').val());
                        
                        // Calculate end date
                        var endDate = new Date(startDate);
                        endDate.setDate(endDate.getDate() + days);
                        var formattedEndDate = endDate.toISOString().split('T')[0];
                        
                        $('#packagePrice').text('$' + price.toFixed(2));
                        $('#packageDuration').text(days + ' days');
                        $('#calculatedEndDate').text(formattedEndDate);
                        $('#packageDetails').show();
                    }
                },
                preConfirm: () => {
                    var packageId = $('#package').val();
                    var startDate = $('#startDate').val();
                    
                    if (!packageId) {
                        Swal.showValidationMessage('Please select a package');
                        return false;
                    }
                    
                    return fetch(`renew_subscription.php?id=${clientId}&package=${packageId}&start_date=${startDate}&branch_id=<?php echo $branch_id; ?>&redirect=view_client.php%3Fid%3D<?php echo urlencode($client_id); ?>%26branch_id%3D<?php echo urlencode($branch_id); ?>`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText || 'Server error');
                            }
                            return response.text().then(text => {
                                try {
                                    return JSON.parse(text);
                                } catch (e) {
                                    throw new Error('Invalid server response');
                                }
                            });
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        title: 'Subscription Renewed',
                        text: `New subscription created with start date ${result.value.start_date} and payment of $${result.value.amount} recorded.`,
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        });
    });
}

// Start initialization
initializeWhenJQueryIsReady();
</script>

</body>
</html> 