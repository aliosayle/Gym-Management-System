<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

if (!$link) {
    die("Connection not established: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch user permissions
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$permission_query = "SELECT canedit FROM users WHERE id = '$user_id'";
$permission_result = mysqli_query($link, $permission_query);
$permissions = mysqli_fetch_assoc($permission_result);

if ($permissions['canedit'] == 0) {
    echo "<script>alert('You do not have permission to edit clients.'); window.location.href = 'clients.php';</script>";
    exit;
}

$client_id = $_POST['client_id'] ?? null;
if (!$client_id) {
    echo "<script>alert('No client ID provided.'); window.location.href = 'clients.php';</script>";
    exit;
}

$query = "SELECT * FROM clients WHERE client_id = '$client_id'";
$result = mysqli_query($link, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    echo "<script>alert('Client not found.'); window.location.href = 'clients.php';</script>";
    exit;
}

$client = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
    $name = mysqli_real_escape_string($link, $_POST['name']);
    $phone_number = mysqli_real_escape_string($link, $_POST['phone_number']);
    $subscription_status = mysqli_real_escape_string($link, $_POST['subscription_status']);
    $subscription_end_date = mysqli_real_escape_string($link, $_POST['subscription_end_date']);

    $update_query = "UPDATE clients SET name = '$name', phone_number = '$phone_number', subscription_status = '$subscription_status', subscription_end_date = '$subscription_end_date' WHERE client_id = '$client_id'";
    if (mysqli_query($link, $update_query)) {
        echo "<script>alert('Client updated successfully'); window.location.href = 'clients.php';</script>";
    } else {
        echo "<script>alert('Error updating client: " . mysqli_error($link) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Client | Admin Template</title>
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
                                <li class="breadcrumb-item"><a href="clients.php">Clients</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Client</li>
                            </ol>
                        </nav>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Edit Client</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                <input type="hidden" name="form_submitted" value="1">

                                    <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['client_id']); ?>">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input class="form-control" type="text" name="name" id="name" value="<?php echo htmlspecialchars($client['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input class="form-control" type="text" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($client['phone_number']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subscription_status" class="form-label">Subscription Status</label>
                                        <select class="form-control" name="subscription_status" id="subscription_status" required>
                                            <option value="active" <?php if ($client['subscription_status'] == 'active') echo 'selected'; ?>>Active</option>
                                            <option value="inactive" <?php if ($client['subscription_status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
                                            <option value="expired" <?php if ($client['subscription_status'] == 'expired') echo 'selected'; ?>>Expired</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subscription_end_date" class="form-label">Subscription End Date</label>
                                        <input class="form-control" type="date" name="subscription_end_date" id="subscription_end_date" value="<?php echo htmlspecialchars($client['subscription_end_date']); ?>" required>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Update Client</button>
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
<script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

<link rel="stylesheet" href="styles.css">

</body>
</html>