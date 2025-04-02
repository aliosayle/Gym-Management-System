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

if (isset($_SESSION['delete_message'])) {
    $alert_type = strpos($_SESSION['delete_message'], 'successfully') !== false ? 'success' : 'danger';
    echo "<div class='alert alert-$alert_type alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['delete_message']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    unset($_SESSION['delete_message']); // Unset after displaying the message
}

// Flash message handling
if (isset($_SESSION['message'])) {
    $alert_type = strpos($_SESSION['message'], 'success') !== false ? 'success' : 'danger';
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Unset after storing
}

// Fetch user permissions
$user_id = $_SESSION['id']; // Assuming user_id is stored in session

// Include permission checks
include 'layouts/check_permission.php';

// Check permissions for this page
$can_manage_clients = has_permission('can_manage_clients', $pdo);
$can_add_client = has_permission('can_add_client', $pdo);
$can_edit_client = has_permission('can_edit_client', $pdo);
$can_delete_client = has_permission('can_delete_client', $pdo);

// If user doesn't have permission to manage clients, redirect them
if (!$can_manage_clients) {
    $_SESSION['error_message'] = "You don't have permission to manage clients.";
    header("Location: index.php");
    exit;
}

// Get user's assigned branches
$branches_query = "SELECT b.* FROM branches b 
                   JOIN user_branches ub ON b.id = ub.branch_id 
                   WHERE ub.user_id = :user_id";
$branches_stmt = $pdo->prepare($branches_query);
$branches_stmt->execute(['user_id' => $user_id]);
$user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);

// If user is admin but has no branches assigned, get all branches
if (has_permission('isadmin', $pdo) && empty($user_branches)) {
    $branches_query = "SELECT * FROM branches";
    $branches_stmt = $pdo->prepare($branches_query);
    $branches_stmt->execute();
    $user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If there are no branches at all, create a default one
if (empty($user_branches)) {
    try {
        $pdo->beginTransaction();
        
        // Check if any branches exist
        $check_branches = "SELECT COUNT(*) FROM branches";
        $check_stmt = $pdo->prepare($check_branches);
        $check_stmt->execute();
        $branch_count = $check_stmt->fetchColumn();
        
        if ($branch_count == 0) {
            // Create default branch
            $create_branch = "INSERT INTO branches (id, company_id, manager) VALUES (1, 1, 'Default Manager')";
            $pdo->exec($create_branch);
            
            // Assign user to this branch
            $assign_branch = "INSERT INTO user_branches (user_id, branch_id, assigned_by) VALUES (:user_id, 1, :user_id)";
            $assign_stmt = $pdo->prepare($assign_branch);
            $assign_stmt->execute(['user_id' => $user_id]);
            
            $pdo->commit();
            
            // Refresh the branches list
            $branches_query = "SELECT * FROM branches WHERE id = 1";
            $branches_stmt = $pdo->prepare($branches_query);
            $branches_stmt->execute();
            $user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error creating default branch: " . $e->getMessage());
    }
}

// Get the selected branch (from POST, GET, or default to the first branch)
$selected_branch_id = null;

if (isset($_POST['branch_id'])) {
    $selected_branch_id = $_POST['branch_id'];
} elseif (isset($_GET['branch_id'])) {
    $selected_branch_id = $_GET['branch_id'];
} elseif (!empty($user_branches)) {
    $selected_branch_id = $user_branches[0]['id'];
}

// Get client statistics
$total_clients_query = "SELECT COUNT(*) as total FROM clients WHERE branch_id = :branch_id";
$total_clients_stmt = $pdo->prepare($total_clients_query);
$total_clients_stmt->execute(['branch_id' => $selected_branch_id]);
$total_clients = $total_clients_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$active_clients_query = "SELECT COUNT(*) as active FROM clients WHERE subscription_status = 'active' AND branch_id = :branch_id";
$active_clients_stmt = $pdo->prepare($active_clients_query);
$active_clients_stmt->execute(['branch_id' => $selected_branch_id]);
$active_clients = $active_clients_stmt->fetch(PDO::FETCH_ASSOC)['active'];

$expiring_clients_query = "SELECT COUNT(*) as expiring FROM clients 
                          WHERE subscription_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                          AND subscription_status = 'active'
                          AND branch_id = :branch_id";
$expiring_clients_stmt = $pdo->prepare($expiring_clients_query);
$expiring_clients_stmt->execute(['branch_id' => $selected_branch_id]);
$expiring_clients = $expiring_clients_stmt->fetch(PDO::FETCH_ASSOC)['expiring'];

$expired_clients_query = "SELECT COUNT(*) as expired FROM clients WHERE subscription_status = 'expired' AND branch_id = :branch_id";
$expired_clients_stmt = $pdo->prepare($expired_clients_query);
$expired_clients_stmt->execute(['branch_id' => $selected_branch_id]);
$expired_clients = $expired_clients_stmt->fetch(PDO::FETCH_ASSOC)['expired'];

$pending_payments_query = "SELECT COUNT(*) as pending FROM payments WHERE payment_status = 'Pending'";
$pending_payments_stmt = $pdo->prepare($pending_payments_query);
$pending_payments_stmt->execute();
$pending_payments = $pending_payments_stmt->fetch(PDO::FETCH_ASSOC)['pending'];

// Protect POST actions with permission checks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_name']) && $can_add_client) {
    $client_name = $_POST['client_name'];
    $insert_query = "INSERT INTO clients (name) VALUES (:client_name)";
    $insert_stmt = $pdo->prepare($insert_query);
    if ($insert_stmt->execute(['client_name' => $client_name])) {
        echo "<script>alert('New client added successfully');</script>";
    } else {
        echo "<script>alert('Error adding client: " . $insert_stmt->errorInfo()[2] . "');</script>";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<script>alert('You do not have permission to add clients.');</script>";
}


include 'layouts/api_keys.php';

// Function to send WhatsApp message
function sendWhatsAppMessage($phoneNumber, $messageBody) {

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode([
            "recipient_type" => "individual",
            "to" => $phoneNumber,
            "type" => "text",
            "text" => ["body" => $messageBody]
        ]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $authToken",
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [
        'success' => $httpCode === 200,
        'response' => $response,
        'http_code' => $httpCode
    ];
}

// Check if the alert has already been sent today
$today = date('Y-m-d');
$alert_check_query = "SELECT last_alert_date FROM alert_log WHERE alert_date = :today";
$alert_check_stmt = $pdo->prepare($alert_check_query);
$alert_check_stmt->execute(['today' => $today]);

if ($alert_check_stmt->rowCount() == 0) {
    // Fetch clients with subscriptions ending soon
    $alert_query = "SELECT name, phone_number, subscription_end_date FROM clients WHERE subscription_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    $alert_stmt = $pdo->prepare($alert_query);
    $alert_stmt->execute();

    if ($alert_stmt->rowCount() > 0) {
        while ($client = $alert_stmt->fetch(PDO::FETCH_ASSOC)) {
            $days_left = (new DateTime($client['subscription_end_date']))->diff(new DateTime())->days;
            $message = "Dear " . htmlspecialchars($client['name']) . ", your Kinshasa Mall Gym subscription is ending in " . $days_left . " days on " . htmlspecialchars($client['subscription_end_date']) . ". Please renew it soon.";
            // sendWhatsAppMessage($client['phone_number'], $message); - Commented out to preserve existing function
        }
    }

    // Log the alert date
    $log_alert_query = "INSERT INTO alert_log (alert_date) VALUES (:today)";
    $log_alert_stmt = $pdo->prepare($log_alert_query);
    $log_alert_stmt->execute(['today' => $today]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Client Management | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <!-- Add SweetAlert2 from CDN to ensure it's available -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <?php include 'layouts/head-style.php'; ?>
    <style>
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .stats-icon {
            height: 60px;
            width: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .card-action-btn {
            width: 38px;
        }
        .action-button {
            margin: 0 3px;
        }
        .client-card {
            border-radius: 10px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .client-card:hover {
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        .badge-subscription {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 10px;
        }
        .filter-btn {
            border-radius: 8px;
            box-shadow: none;
            font-weight: 500;
            padding: 8px 15px;
        }
        .filter-btn.active {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .search-box {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .export-btn {
            border-radius: 8px;
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
                <!-- Breadcrumb and Flash Messages -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Client Management</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Client Management</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Remove the branch selector dropdown card -->
                
                <!-- Dashboard Statistics -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-white text-primary rounded-circle me-3">
                                        <i class="fas fa-users fa-lg"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title text-white mb-0"><?php echo $total_clients; ?></h5>
                                        <p class="text-white-50 mb-0">Total Clients</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-white text-success rounded-circle me-3">
                                        <i class="fas fa-check-circle fa-lg"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title text-white mb-0"><?php echo $active_clients; ?></h5>
                                        <p class="text-white-50 mb-0">Active Memberships</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-white text-warning rounded-circle me-3">
                                        <i class="fas fa-clock fa-lg"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title text-white mb-0"><?php echo $expiring_clients; ?></h5>
                                        <p class="text-white-50 mb-0">Expiring Soon</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-white text-danger rounded-circle me-3">
                                        <i class="fas fa-exclamation-circle fa-lg"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title text-white mb-0"><?php echo $expired_clients; ?></h5>
                                        <p class="text-white-50 mb-0">Expired Memberships</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Clients Table</h4>
                                <div>
                                    <a href="clients.php" class="btn btn-secondary">Show All Clients</a>
                                    <a href="clients.php?filter=ending_soon" class="btn btn-warning">Show Subscriptions Ending Soon</a>
                                    <a href="clients.php?filter=expired" class="btn btn-danger">Show Expired Clients</a>
                                    <a href="clients.php?filter=pending_payments" class="btn btn-info">Show Pending Payments</a> <!-- New button added -->
                                    <?php
                                    if (isset($_GET['filter']) && $_GET['filter'] == 'ending_soon') {
                                        $query = "SELECT * FROM clients WHERE subscription_end_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
                                    } elseif (isset($_GET['filter']) && $_GET['filter'] == 'expired') {
                                        $query = "SELECT * FROM clients WHERE subscription_status = 'expired'";
                                    } elseif (isset($_GET['filter']) && $_GET['filter'] == 'pending_payments') { // New filter condition
                                        $query = "SELECT * FROM clients WHERE client_id IN (SELECT client_id FROM payments WHERE payment_status = 'pending')";
                                    } else {
                                        $query = "SELECT * FROM clients";
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="add_client.php" class="mb-4">
                                    <input type="hidden" name="branch_id" value="<?php echo $selected_branch_id; ?>">
                                    <button type="submit" class="btn btn-primary" <?php if ($can_add_client == 0) echo 'style="pointer-events: none; opacity: 0.6;"'; ?>>
                                        <i class="fas fa-plus me-2"></i> Add New Client
                                    </button>
                                </form>

                                <table id="datatable" class="table table-bordered dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Phone Number</th>
                                            <th>Subscription Status</th>
                                            <th>Subscription End Date</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Apply filters with branch_id
                                        if (isset($_GET['filter']) && $_GET['filter'] == 'ending_soon') {
                                            $query = "SELECT * FROM clients 
                                                     WHERE subscription_end_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                                                     AND branch_id = :branch_id";
                                        } elseif (isset($_GET['filter']) && $_GET['filter'] == 'expired') {
                                            $query = "SELECT * FROM clients 
                                                     WHERE subscription_status = 'expired'
                                                     AND branch_id = :branch_id";
                                        } else {
                                            $query = "SELECT * FROM clients WHERE branch_id = :branch_id";
                                        }
                                        
                                        $stmt = $pdo->prepare($query);
                                        $stmt->execute(['branch_id' => $selected_branch_id]);
                                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        if ($result) {
                                            foreach ($result as $row) {
                                                $client_id = htmlspecialchars($row['client_id']);
                                                $pending_payment_query = "SELECT * FROM payments WHERE client_id = :client_id AND payment_status = 'pending'";
                                                $pending_payment_stmt = $pdo->prepare($pending_payment_query);
                                                $pending_payment_stmt->execute(['client_id' => $client_id]);
                                                $has_pending_payment = $pending_payment_stmt->rowCount() > 0;
                                                $pending_payment = $has_pending_payment ? $pending_payment_stmt->fetch(PDO::FETCH_ASSOC) : null;

                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['name']);
                                                if ($has_pending_payment) {
                                                    echo " <span class='badge bg-warning'>Pending Payment: $" . htmlspecialchars($pending_payment['amount']) . "</span>";
                                                }
                                                echo "</td>";
                                                echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['subscription_status']) . "</td>";
                                                echo "<td>" . (!empty($row['subscription_end_date']) ? htmlspecialchars($row['subscription_end_date']) : 'Not set') . "</td>";
                                                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                                echo "<td class='text-center'>";

                                                // Edit Button
                                                echo "<form method='POST' action='edit_client.php' style='display:inline-block;' onsubmit='return submitForm(this);'>";
                                                echo "<input type='hidden' name='client_id' value='" . $client_id . "'>";
                                                echo "<button type='submit' class='btn btn-success btn-sm action-button' " . ($can_edit_client == 0 ? 'style="pointer-events: none; opacity: 0.6;"' : '') . ">
                                                        <i class='mdi mdi-pencil d-block font-size-16'></i>
                                                      </button>";
                                                echo "</form>";

                                                // Delete Button with SweetAlert
                                                echo "<button type='button' class='btn btn-danger btn-sm action-button sa-warning' data-id='" . $client_id . "' " . ($can_delete_client == 0 ? 'disabled' : '') . ">
                                                        <i class='mdi mdi-trash-can d-block font-size-16'></i>
                                                      </button>";

                                                // Conditionally display either the Renew Subscription Button or Confirm Payment Button
                                                if ($has_pending_payment) {
                                                    echo "<button type='button' class='btn btn-warning btn-sm action-button confirm-payment' data-id='" . htmlspecialchars($pending_payment['payment_id']) . "'>
                                                            <i class='mdi mdi-cash d-block font-size-16'></i>
                                                          </button>";
                                                } else {
                                                    echo "<button type='button' class='btn btn-info btn-sm action-button renew-subscription' data-id='" . $client_id . "'>
                                                            <i class='mdi mdi-refresh d-block font-size-16'></i>
                                                          </button>";
                                                }

                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6'>No data found</td></tr>";
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
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
</div>

<?php include 'layouts/vendor-scripts.php'; ?>

<!-- Required datatable js -->
<script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
<script src="assets/libs/jszip/jszip.min.js"></script>
<script src="assets/libs/pdfmake/build/pdfmake.min.js"></script>
<script src="assets/libs/pdfmake/build/vfs_fonts.js"></script>
<script src="assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>

<!-- Load SweetAlert2 BEFORE other scripts that use it -->
<script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

<!-- Other scripts -->
<script src="assets/js/app.js"></script>

<script>
    function submitForm(form) {
        var clientId = form.querySelector('input[name="client_id"]').value;
        if (!clientId) {
            Swal.fire({
                title: 'Error',
                text: 'Client ID is missing',
                icon: 'error'
            });
            return false;
        }
        return true;
    }
    
    $(document).ready(function() {
        // Initialize DataTable
        var clientsTable = $('#datatable').DataTable({
            responsive: true,
            "language": {
                "paginate": {
                    "previous": "<i class='fas fa-chevron-left'>",
                    "next": "<i class='fas fa-chevron-right'>"
                },
                "emptyTable": "No clients found matching your criteria",
                "zeroRecords": "No matching clients found"
            },
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                   '<"row"<"col-sm-12"tr>>' +
                   '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            "pageLength": 10,
            "order": [[0, "asc"]]
        });
        
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Search functionality across both views
        $('#searchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            
            // List view search (DataTable)
            clientsTable.search(value).draw();
            
            // Card view search
            $('.client-item').filter(function() {
                var shouldShow = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).closest('.col-xl-3').toggle(shouldShow);
                return shouldShow;
            });
        });
        
        // View toggling
        $('#cardViewBtn').on('click', function() {
            $(this).addClass('active');
            $('#listViewBtn').removeClass('active');
            $('#cardView').show();
            $('#listView').hide();
        });
        
        $('#listViewBtn').on('click', function() {
            $(this).addClass('active');
            $('#cardViewBtn').removeClass('active');
            $('#listView').show();
            $('#cardView').hide();
            
            // Adjust columns when showing DataTable
            clientsTable.columns.adjust().responsive.recalc();
        });
        
        // SweetAlert for delete button
        $('.sa-warning').on('click', function () {
            var clientId = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete_client.php?id=' + clientId;
                }
            });
        });
        
        // Enhanced renew subscription functionality
        $('.renew-subscription').on('click', function () {
            var clientId = $(this).data('id');
            console.log('Renew subscription clicked for client ID:', clientId);
            
            // First check if SweetAlert is available
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert is not defined. Using browser confirm instead.');
                if (!confirm('SweetAlert is not available. Continue with basic confirmation?\nClient ID: ' + clientId)) {
                    return;
                }
                
                // Redirect to a simplified renewal page
                window.location.href = 'simple_renewal.php?id=' + clientId;
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
                        <div id="packageDetails" class="alert alert-info mt-3" style="display: none;">
                            <div class="d-flex justify-content-between">
                                <span>Price:</span>
                                <span id="packagePrice">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Duration:</span>
                                <span id="packageDuration">0 days</span>
                            </div>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Renew',
                showLoaderOnConfirm: true,
                didOpen: () => {
                    // Fetch packages
                    console.log('Fetching packages...');
                    
                    $.ajax({
                        url: 'ajax/get_packages.php',
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            console.log('Packages loaded successfully:', data);
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
                            console.error('Error loading packages:', status, error);
                            console.error('Response:', xhr.responseText);
                            $('#package').html('<option value="">Error loading packages</option>');
                            Swal.showValidationMessage('Failed to load packages: ' + error);
                        }
                    });
                    
                    // Handle package change
                    $(document).on('change', '#package', updatePackageDetails);
                    
                    function updatePackageDetails() {
                        var selectedOption = $('#package option:selected');
                        var price = selectedOption.data('price') || 0;
                        var days = selectedOption.data('days') || 0;
                        
                        $('#packagePrice').text('$' + price.toFixed(2));
                        $('#packageDuration').text(days + ' days');
                        $('#packageDetails').show();
                    }
                },
                preConfirm: () => {
                    var packageId = $('#package').val();
                    
                    console.log('Submitting renewal with package:', packageId);
                    
                    if (!packageId) {
                        Swal.showValidationMessage('Please select a package');
                        return false;
                    }
                    
                    return fetch(`renew_subscription.php?id=${clientId}&package=${packageId}`)
                        .then(response => {
                            console.log('Renewal response status:', response.status);
                            if (!response.ok) {
                                throw new Error(response.statusText || 'Server error');
                            }
                            return response.text().then(text => {
                                console.log('Renewal response text:', text);
                                try {
                                    return JSON.parse(text);
                                } catch (e) {
                                    console.error('Error parsing JSON:', e);
                                    throw new Error('Invalid server response');
                                }
                            });
                        })
                        .catch(error => {
                            console.error('Renewal error:', error);
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                }
            }).then((result) => {
                console.log('Renewal result:', result);
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        title: 'Subscription Renewed',
                        text: `New subscription created and payment of $${result.value.amount} recorded.`,
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        });
        
        // Confirm payment functionality
        $('.confirm-payment').on('click', function () {
            var paymentId = $(this).data('id');
            Swal.fire({
                title: 'Confirm Payment',
                text: "Are you sure you want to confirm this payment?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, confirm it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'confirm_payment.php?id=' + paymentId;
                }
            });
        });
    });
</script>

</body>
</html>