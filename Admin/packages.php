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

// Get user's assigned branches
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;
if ($user_id === null) {
    die("User ID is not set in the session.");
}

// Get user permissions
$permission_query = "SELECT canedit, candelete, canadd, isadmin FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch(PDO::FETCH_ASSOC);

// Check if $permissions is false (no user found)
if ($permissions === false) {
    die("No permissions found for the given user.");
}

// Get user's assigned branches
$branches_query = "SELECT b.* FROM branches b 
                   JOIN user_branches ub ON b.id = ub.branch_id 
                   WHERE ub.user_id = :user_id";
$branches_stmt = $pdo->prepare($branches_query);
$branches_stmt->execute(['user_id' => $user_id]);
$user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);

// If user is admin but has no branches assigned, get all branches
if ($permissions['isadmin'] == 1 && empty($user_branches)) {
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

if (isset($_SESSION['delete_message'])) {
    $alert_type = strpos($_SESSION['delete_message'], 'successfully') !== false ? 'success' : 'danger';
    echo "<div class='alert alert-$alert_type alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['delete_message']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    unset($_SESSION['delete_message']); // Unset after displaying the message
}

// Protect POST actions with permission checks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_name']) && $permissions['canadd'] == 1) {
    $package_name = $_POST['package_name'];
    $price = $_POST['price'];
    $number_of_days = (int)$_POST['number_of_days'];
    $branch_id = $selected_branch_id; // Use the selected branch
    
    $insert_query = "INSERT INTO packages (name, price, number_of_days, branch_id) 
                     VALUES (:package_name, :price, :number_of_days, :branch_id)";
    $insert_stmt = $pdo->prepare($insert_query);
    if ($insert_stmt->execute([
        'package_name' => $package_name, 
        'price' => $price, 
        'number_of_days' => $number_of_days,
        'branch_id' => $branch_id
    ])) {
        echo "<script>alert('New package added successfully');</script>";
    } else {
        echo "<script>alert('Error adding package: " . implode(", ", $insert_stmt->errorInfo()) . "');</script>";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_name'])) {
    echo "<script>alert('You do not have permission to add packages.');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Packages Table | Admin Template</title>
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
                                <li class="breadcrumb-item active" aria-current="page">Packages</li>
                            </ol>
                        </nav>
                        
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Packages Table</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="add_package.php" class="mb-4">
                                    <input type="hidden" name="branch_id" value="<?php echo $selected_branch_id; ?>">
                                    <button type="submit" class="btn btn-primary" <?php if ($permissions['canadd'] == 0) echo 'style="pointer-events: none; opacity: 0.6;"'; ?>>
                                        <i class="fas fa-plus me-2"></i> Add New Package
                                    </button>
                                </form>

                                <table id="datatable" class="table table-bordered dt-responsive nowrap w-100">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Number of Days</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Get packages for the selected branch
                                        $query = "SELECT * FROM packages WHERE branch_id = :branch_id";
                                        $stmt = $pdo->prepare($query);
                                        $stmt->execute(['branch_id' => $selected_branch_id]);
                                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if ($result) {
                                            foreach ($result as $row) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['number_of_days']) . "</td>";
                                                echo "<td class='text-center'>";

                                                // Edit Button
                                                echo "<form method='POST' action='edit_package.php?id={$row['id']}' style='display:inline-block;' onsubmit='return submitForm(this);'>";
                                                echo "<input type='hidden' name='package_id' value='" . htmlspecialchars($row['id']) . "'>";
                                                echo "<input type='hidden' name='branch_id' value='" . $selected_branch_id . "'>";
                                                echo "<button type='submit' class='btn btn-success btn-sm action-button' " . ($permissions['canedit'] == 0 ? 'style="pointer-events: none; opacity: 0.6;"' : '') . ">
                                                        <i class='mdi mdi-pencil d-block font-size-16'></i>
                                                      </button>";
                                                echo "</form>";

                                                // Delete Button with SweetAlert
                                                echo "<button type='button' class='btn btn-danger btn-sm action-button sa-warning' data-id='" . htmlspecialchars($row['id']) . "' " . ($permissions['candelete'] == 0 ? 'disabled' : '') . ">
                                                        <i class='mdi mdi-trash-can d-block font-size-16'></i>
                                                      </button>";

                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4'>No packages found for this branch</td></tr>";
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
<script src="assets/js/app.js"></script>
<script>
    function submitForm(form) {
        var packageId = form.querySelector('input[name="package_id"]').value;
        if (!packageId) {
            alert('Package ID is missing');
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
            var packageId = $(this).data('id');
            var branchId = <?php echo $selected_branch_id; ?>;
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
                    window.location.href = 'delete_package.php?id=' + packageId + '&branch_id=' + branchId;
                }
            })
        });
    });
</script>

</body>
</html>