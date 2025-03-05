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

// Fetch user permissions
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$permission_query = "SELECT canedit, candelete, canadd FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch(PDO::FETCH_ASSOC);

// Protect POST actions with permission checks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_name']) && $permissions['canadd'] == 1) {
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
            sendWhatsAppMessage($client['phone_number'], $message);
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
    <title>Clients Table | Admin Template</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <div class="row">
                    <div class="col-12">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-3">
                                <li class="breadcrumb-item">Login</li>
                                <li class="breadcrumb-item active" aria-current="page">Clients</li>
                            </ol>
                        </nav>
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
                                    <button type="submit" class="btn btn-primary" <?php if ($permissions['canadd'] == 0) echo 'style="pointer-events: none; opacity: 0.6;"'; ?>>
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
                                    if (isset($_GET['filter']) && $_GET['filter'] == 'ending_soon') {
                                        $query = "SELECT * FROM clients WHERE subscription_end_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
                                    } elseif (isset($_GET['filter']) && $_GET['filter'] == 'expired') {
                                        $query = "SELECT * FROM clients WHERE subscription_status = 'expired'";
                                    } elseif (isset($_GET['filter']) && $_GET['filter'] == 'pending_payments') { // New filter condition
                                        $query = "SELECT * FROM clients WHERE client_id IN (SELECT client_id FROM payments WHERE payment_status = 'pending')";
                                    } else {
                                        $query = "SELECT * FROM clients";
                                    }
                                        $stmt = $pdo->prepare($query);
                                        $stmt->execute();
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
                                                    echo " <span class='badge bg-warning'>Pending Payment</span>";
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
                                                echo "<button type='submit' class='btn btn-success btn-sm action-button' " . ($permissions['canedit'] == 0 ? 'style="pointer-events: none; opacity: 0.6;"' : '') . ">
                                                        <i class='mdi mdi-pencil d-block font-size-16'></i>
                                                      </button>";
                                                echo "</form>";

                                                // Delete Button with SweetAlert
                                                echo "<button type='button' class='btn btn-danger btn-sm action-button sa-warning' data-id='" . $client_id . "' " . ($permissions['candelete'] == 0 ? 'disabled' : '') . ">
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
<script src="assets/libs/apexcharts/apexcharts.min.js"></script>
<script src="assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js"></script>
<script src="assets/js/pages/dashboard.init.js"></script>
<script src="assets/js/app.js"></script>
<script>
    function submitForm(form) {
        var clientId = form.querySelector('input[name="client_id"]').value;
        if (!clientId) {
            alert('Client ID is missing');
            return false;
        }
        return true;
    }
</script>
<script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script>
    $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none'; // Disable DataTables warnings
        $('#datatable').DataTable({
            "searching": true,
            "paging": true,
            "info": true,
            "responsive": true
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
            })
        });

        // SweetAlert for renew subscription button
        $('.renew-subscription').on('click', function () {
            var clientId = $(this).data('id');
            Swal.fire({
                title: 'Renew Subscription',
                showCancelButton: true,
                confirmButtonText: 'Renew',
                showLoaderOnConfirm: true,
                preConfirm: (months) => {
                    return fetch(`renew_subscription.php?id=${clientId}`)
                        .then(response => response.text()) // Get response as text
                        .then(text => {
                            console.log(text); // Log the response text
                            return JSON.parse(text); // Parse the JSON
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error}`
                            )
                        })
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: `Subscription renewed and new payment generated`,
                        text: `Amount: ${result.value.amount}`,
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                }
            })
        });
        // SweetAlert for confirm payment button
        $('.confirm-payment').on('click', function () {
            var paymentId = $(this).data('id');
            Swal.fire({
                title: 'Confirm Payment',
                text: "Are you sure you want to confirm this payment?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, confirm it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'confirm_payment.php?id=' + paymentId;
                }
            })
        });
    });
</script>

</body>
</html>