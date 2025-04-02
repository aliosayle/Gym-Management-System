<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

// Ensure only admin can access this page
// This is redundant with our session check, but added for extra security
if (!isset($_SESSION['id'])) {
    header("location: auth-login.php");
    exit;
}

// Check if user is admin
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($user['isadmin']) || $user['isadmin'] != 1) {
    header("location: clients.php");
    exit;
}

// Create companies table if it doesn't exist
$create_companies_table = "CREATE TABLE IF NOT EXISTS companies (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    contact_person VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$pdo->exec($create_companies_table);

// Create branches table if it doesn't exist
$create_branches_table = "CREATE TABLE IF NOT EXISTS branches (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    company_id INT(11) NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    manager VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
)";
$pdo->exec($create_branches_table);

// Get all companies
$companies_query = "SELECT * FROM companies ORDER BY name";
$companies_stmt = $pdo->prepare($companies_query);
$companies_stmt->execute();
$companies = $companies_stmt->fetchAll(PDO::FETCH_ASSOC);

// Flash messages
$success_message = "";
$error_message = "";

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Companies & Branches | Gym Management</title>
    <?php include 'layouts/head.php'; ?>
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" type="text/css" />
    <style>
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        .spinner-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .fade-transition {
            transition: opacity 0.3s ease-in-out;
        }
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        .btn-loading .spinner-border {
            position: absolute;
            left: calc(50% - 8px);
            top: calc(50% - 8px);
        }
    </style>
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
                                <h4 class="mb-sm-0 font-size-18">Companies & Branches</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Companies & Branches</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Loading Overlay -->
                    <div id="loading-overlay">
                        <div class="spinner-container">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Processing...</div>
                        </div>
                    </div>

                    <!-- Companies Card -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Companies</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                                        <i class="mdi mdi-plus me-1"></i> Add Company
                                    </button>
                                </div>
                                <div class="card-body">
                                    <table id="companies-datatable" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Contact Person</th>
                                                <th>Phone</th>
                                                <th>Email</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($companies as $company): ?>
                                                <tr>
                                                    <td><?php echo $company['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($company['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($company['contact_person'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($company['phone'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($company['email'] ?? ''); ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-info btn-sm view-branches" data-id="<?php echo $company['id']; ?>" data-name="<?php echo htmlspecialchars($company['name']); ?>">
                                                                <i class="mdi mdi-office-building me-1"></i> Branches
                                                            </button>
                                                            <button type="button" class="btn btn-primary btn-sm edit-company" data-id="<?php echo $company['id']; ?>">
                                                                <i class="mdi mdi-pencil"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm delete-company" data-id="<?php echo $company['id']; ?>">
                                                                <i class="mdi mdi-trash-can"></i>
                                                            </button>
                                                        </div>
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
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            <?php include 'layouts/footer.php'; ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- Add Company Modal -->
    <div class="modal fade" id="addCompanyModal" tabindex="-1" aria-labelledby="addCompanyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCompanyModalLabel">Add New Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addCompanyForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person">
                        </div>
                        <div class="mb-3">
                            <label for="company_address" class="form-label">Address</label>
                            <textarea class="form-control" id="company_address" name="address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="company_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="company_phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="company_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="company_email" name="email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Company Modal -->
    <div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCompanyModalLabel">Edit Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCompanyForm">
                    <input type="hidden" id="edit_company_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="edit_company_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="edit_contact_person" name="contact_person">
                        </div>
                        <div class="mb-3">
                            <label for="edit_company_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_company_address" name="address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_company_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="edit_company_phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="edit_company_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_company_email" name="email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Branches Modal -->
    <div class="modal fade" id="branchesModal" tabindex="-1" aria-labelledby="branchesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="branchesModalLabel">Branches for <span id="companyName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-primary" id="addBranchBtn">
                            <i class="mdi mdi-plus me-1"></i> Add Branch
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="branches-datatable" class="table table-bordered dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Manager</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="branchesTableBody">
                                <!-- Branches will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Branch Modal -->
    <div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBranchModalLabel">Add New Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addBranchForm">
                    <input type="hidden" id="branch_company_id" name="company_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="branch_name" class="form-label">Branch Name</label>
                            <input type="text" class="form-control" id="branch_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="branch_manager" class="form-label">Manager</label>
                            <input type="text" class="form-control" id="branch_manager" name="manager">
                        </div>
                        <div class="mb-3">
                            <label for="branch_address" class="form-label">Address</label>
                            <textarea class="form-control" id="branch_address" name="address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="branch_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="branch_phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="branch_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="branch_email" name="email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Branch Modal -->
    <div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBranchModalLabel">Edit Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editBranchForm">
                    <input type="hidden" id="edit_branch_id" name="id">
                    <input type="hidden" id="edit_branch_company_id" name="company_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_branch_name" class="form-label">Branch Name</label>
                            <input type="text" class="form-control" id="edit_branch_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_branch_manager" class="form-label">Manager</label>
                            <input type="text" class="form-control" id="edit_branch_manager" name="manager">
                        </div>
                        <div class="mb-3">
                            <label for="edit_branch_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_branch_address" name="address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_branch_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="edit_branch_phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="edit_branch_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_branch_email" name="email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'layouts/vendor-scripts.php'; ?>

    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var companyTable = $('#companies-datatable').DataTable({
                responsive: true,
                columnDefs: [
                    { "width": "15%", "targets": 5 }
                ]
            });

            let branchesTable;
            let currentCompanyId = null;
            let branchesCache = {}; // Cache for branches data

            // Setup loading overlay
            $(document).ajaxStart(function() {
                $('#loading-overlay').fadeIn(200);
            }).ajaxStop(function() {
                $('#loading-overlay').fadeOut(200);
            });

            // Preload branches data for all companies
            function preloadAllBranchesData() {
                $.ajax({
                    url: 'ajax/branch_actions.php',
                    type: 'POST',
                    data: {
                        action: 'get_all_branches'
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                // Group branches by company_id
                                data.branches.forEach(branch => {
                                    if (!branchesCache[branch.company_id]) {
                                        branchesCache[branch.company_id] = [];
                                    }
                                    branchesCache[branch.company_id].push(branch);
                                });
                            }
                        } catch (e) {
                            console.error("Error parsing branches data:", e);
                        }
                    }
                });
            }

            // Call preload function when page loads
            preloadAllBranchesData();

            // Add Company Form Submit
            $('#addCompanyForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    name: $('#company_name').val(),
                    contact_person: $('#contact_person').val(),
                    address: $('#company_address').val(),
                    phone: $('#company_phone').val(),
                    email: $('#company_email').val()
                };
                
                $.ajax({
                    url: 'ajax/company_actions.php',
                    type: 'POST',
                    data: {
                        action: 'add_company',
                        ...formData
                    },
                    beforeSend: function() {
                        $('#addCompanyModal button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                // Auto-dismiss modal
                                $('#addCompanyModal').modal('hide');
                                
                                // Reset the form
                                $('#addCompanyForm')[0].reset();
                                
                                // Show success message and reload after delay
                                Swal.fire({
                                    title: 'Success!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    timer: 2000
                                });
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Invalid server response', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    },
                    complete: function() {
                        $('#addCompanyModal button[type="submit"]').prop('disabled', false).html('Save Company');
                    }
                });
            });

            // Edit Company Button Click
            $(document).on('click', '.edit-company', function() {
                const companyId = $(this).data('id');
                
                $.ajax({
                    url: 'ajax/company_actions.php',
                    type: 'POST',
                    data: {
                        action: 'get_company',
                        id: companyId
                    },
                    beforeSend: function() {
                        // Show loading for this button
                        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                        $(this).prop('disabled', true);
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                const company = data.company;
                                
                                $('#edit_company_id').val(company.id);
                                $('#edit_company_name').val(company.name);
                                $('#edit_contact_person').val(company.contact_person);
                                $('#edit_company_address').val(company.address);
                                $('#edit_company_phone').val(company.phone);
                                $('#edit_company_email').val(company.email);
                                
                                $('#editCompanyModal').modal('show');
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    timer: 2000
                                });
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Invalid server response', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            });

            // Edit Company Form Submit
            $('#editCompanyForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    id: $('#edit_company_id').val(),
                    name: $('#edit_company_name').val(),
                    contact_person: $('#edit_contact_person').val(),
                    address: $('#edit_company_address').val(),
                    phone: $('#edit_company_phone').val(),
                    email: $('#edit_company_email').val()
                };
                
                $.ajax({
                    url: 'ajax/company_actions.php',
                    type: 'POST',
                    data: {
                        action: 'update_company',
                        ...formData
                    },
                    beforeSend: function() {
                        $('#editCompanyModal button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                // Auto-dismiss modal
                                $('#editCompanyModal').modal('hide');
                                
                                // Reset the form
                                $('#editCompanyForm')[0].reset();
                                
                                // Show success message and reload after delay
                                Swal.fire({
                                    title: 'Success!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    timer: 2000
                                });
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Invalid server response', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    },
                    complete: function() {
                        $('#editCompanyModal button[type="submit"]').prop('disabled', false).html('Update Company');
                    }
                });
            });

            // Delete Company Button Click
            $(document).on('click', '.delete-company', function() {
                const companyId = $(this).data('id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the company and all its branches. This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const $btn = $(this);
                        const originalHtml = $btn.html();
                        
                        $.ajax({
                            url: 'ajax/company_actions.php',
                            type: 'POST',
                            data: {
                                action: 'delete_company',
                                id: companyId
                            },
                            beforeSend: function() {
                                $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                                $btn.prop('disabled', true);
                            },
                            success: function(response) {
                                try {
                                    const data = JSON.parse(response);
                                    if (data.status === 'success') {
                                        // Show success message and reload
                                        Swal.fire({
                                            title: 'Deleted!',
                                            text: data.message,
                                            icon: 'success',
                                            timer: 1500,
                                            showConfirmButton: false
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: data.message,
                                            icon: 'error',
                                            timer: 2000
                                        });
                                        $btn.html(originalHtml);
                                        $btn.prop('disabled', false);
                                    }
                                } catch (e) {
                                    Swal.fire('Error!', 'Invalid server response', 'error');
                                    $btn.html(originalHtml);
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                                $btn.html(originalHtml);
                                $btn.prop('disabled', false);
                            }
                        });
                    }
                });
            });

            // View Branches Button Click
            $(document).on('click', '.view-branches', function() {
                const companyId = $(this).data('id');
                const companyName = $(this).data('name');
                currentCompanyId = companyId;
                
                $('#companyName').text(companyName);
                $('#branch_company_id').val(companyId);
                
                // Show modal immediately
                $('#branchesModal').modal('show');
                
                // Show loading indicator in table body
                $('#branchesTableBody').html('<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
                
                // Check if we have cached data first
                if (branchesCache[companyId]) {
                    displayBranches(branchesCache[companyId]);
                } else {
                    // If not in cache, load from server
                    loadBranches(companyId);
                }
            });

            // Function to display branches in the table
            function displayBranches(branches) {
                let tableContent = '';
                
                if (branches.length === 0) {
                    tableContent = '<tr><td colspan="6" class="text-center">No branches found for this company</td></tr>';
                } else {
                    branches.forEach(branch => {
                        tableContent += `
                            <tr>
                                <td>${branch.id}</td>
                                <td>${branch.name}</td>
                                <td>${branch.manager || ''}</td>
                                <td>${branch.phone || ''}</td>
                                <td>${branch.email || ''}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm edit-branch" data-id="${branch.id}">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-branch" data-id="${branch.id}">
                                            <i class="mdi mdi-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
                
                $('#branchesTableBody').html(tableContent);
                
                // Initialize branches DataTable (destroy if already initialized)
                if ($.fn.DataTable.isDataTable('#branches-datatable')) {
                    branchesTable.destroy();
                }
                
                branchesTable = $('#branches-datatable').DataTable({
                    responsive: true,
                    columnDefs: [
                        { "width": "15%", "targets": 5 }
                    ]
                });
            }

            // Function to load branches for a company
            function loadBranches(companyId) {
                $.ajax({
                    url: 'ajax/branch_actions.php',
                    type: 'POST',
                    data: {
                        action: 'get_branches',
                        company_id: companyId
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                // Cache the branches data
                                branchesCache[companyId] = data.branches;
                                // Display the branches
                                displayBranches(data.branches);
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    timer: 2000
                                });
                            }
                        } catch (e) {
                            console.error("Error parsing branches data:", e);
                            Swal.fire('Error!', 'Invalid server response', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            }

            // Add Branch Button Click
            $('#addBranchBtn').on('click', function() {
                $('#branchesModal').modal('hide');
                setTimeout(() => {
                    $('#addBranchModal').modal('show');
                }, 500); // Small delay for smoother transition
            });

            // Add Branch Form Submit
            $('#addBranchForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    company_id: $('#branch_company_id').val(),
                    name: $('#branch_name').val(),
                    manager: $('#branch_manager').val(),
                    address: $('#branch_address').val(),
                    phone: $('#branch_phone').val(),
                    email: $('#branch_email').val()
                };
                
                $.ajax({
                    url: 'ajax/branch_actions.php',
                    type: 'POST',
                    data: {
                        action: 'add_branch',
                        ...formData
                    },
                    beforeSend: function() {
                        $('#addBranchModal button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                // Auto-dismiss modal
                                $('#addBranchModal').modal('hide');
                                
                                // Reset the form
                                $('#addBranchForm')[0].reset();
                                
                                // Clear cache for this company to ensure fresh data
                                delete branchesCache[currentCompanyId];
                                
                                // Show branches modal again and reload branches
                                setTimeout(() => {
                                    $('#branchesModal').modal('show');
                                    
                                    // Show toast notification instead of alert
                                    toastr.success(data.message);
                                    
                                    // Reload branches data
                                    loadBranches(currentCompanyId);
                                }, 500);
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    timer: 2000
                                });
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Invalid server response', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    },
                    complete: function() {
                        $('#addBranchModal button[type="submit"]').prop('disabled', false).html('Save Branch');
                    }
                });
            });

            // Edit Branch Button Click
            $(document).on('click', '.edit-branch', function() {
                const branchId = $(this).data('id');
                const $btn = $(this);
                const originalHtml = $btn.html();
                
                $.ajax({
                    url: 'ajax/branch_actions.php',
                    type: 'POST',
                    data: {
                        action: 'get_branch',
                        id: branchId
                    },
                    beforeSend: function() {
                        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                        $btn.prop('disabled', true);
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                const branch = data.branch;
                                
                                $('#edit_branch_id').val(branch.id);
                                $('#edit_branch_company_id').val(branch.company_id);
                                $('#edit_branch_name').val(branch.name);
                                $('#edit_branch_manager').val(branch.manager);
                                $('#edit_branch_address').val(branch.address);
                                $('#edit_branch_phone').val(branch.phone);
                                $('#edit_branch_email').val(branch.email);
                                
                                $('#branchesModal').modal('hide');
                                setTimeout(() => {
                                    $('#editBranchModal').modal('show');
                                }, 500);
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    timer: 2000
                                });
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Invalid server response', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    },
                    complete: function() {
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Edit Branch Form Submit
            $('#editBranchForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    id: $('#edit_branch_id').val(),
                    company_id: $('#edit_branch_company_id').val(),
                    name: $('#edit_branch_name').val(),
                    manager: $('#edit_branch_manager').val(),
                    address: $('#edit_branch_address').val(),
                    phone: $('#edit_branch_phone').val(),
                    email: $('#edit_branch_email').val()
                };
                
                $.ajax({
                    url: 'ajax/branch_actions.php',
                    type: 'POST',
                    data: {
                        action: 'update_branch',
                        ...formData
                    },
                    beforeSend: function() {
                        $('#editBranchModal button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                // Auto-dismiss modal
                                $('#editBranchModal').modal('hide');
                                
                                // Reset the form
                                $('#editBranchForm')[0].reset();
                                
                                // Clear cache for this company to ensure fresh data
                                delete branchesCache[currentCompanyId];
                                
                                // Show branches modal again and reload branches
                                setTimeout(() => {
                                    $('#branchesModal').modal('show');
                                    
                                    // Show toast notification instead of alert
                                    toastr.success(data.message);
                                    
                                    // Reload branches data
                                    loadBranches(currentCompanyId);
                                }, 500);
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    timer: 2000
                                });
                            }
                        } catch (e) {
                            Swal.fire('Error!', 'Invalid server response', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    },
                    complete: function() {
                        $('#editBranchModal button[type="submit"]').prop('disabled', false).html('Update Branch');
                    }
                });
            });

            // Delete Branch Button Click
            $(document).on('click', '.delete-branch', function() {
                const branchId = $(this).data('id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the branch. This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const $btn = $(this);
                        const originalHtml = $btn.html();
                        
                        $.ajax({
                            url: 'ajax/branch_actions.php',
                            type: 'POST',
                            data: {
                                action: 'delete_branch',
                                id: branchId
                            },
                            beforeSend: function() {
                                $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                                $btn.prop('disabled', true);
                            },
                            success: function(response) {
                                try {
                                    const data = JSON.parse(response);
                                    if (data.status === 'success') {
                                        // Clear cache for this company to ensure fresh data
                                        delete branchesCache[currentCompanyId];
                                        
                                        // Show toast notification
                                        toastr.success(data.message);
                                        
                                        // Reload branches
                                        loadBranches(currentCompanyId);
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: data.message,
                                            icon: 'error',
                                            timer: 2000
                                        });
                                        $btn.html(originalHtml);
                                        $btn.prop('disabled', false);
                                    }
                                } catch (e) {
                                    Swal.fire('Error!', 'Invalid server response', 'error');
                                    $btn.html(originalHtml);
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                                $btn.html(originalHtml);
                                $btn.prop('disabled', false);
                            }
                        });
                    }
                });
            });

            // Handle modal closing events
            $('#addBranchModal, #editBranchModal').on('hidden.bs.modal', function () {
                // When Add/Edit Branch modal is closed, show Branches modal again
                setTimeout(() => {
                    $('#branchesModal').modal('show');
                }, 400);
            });

            // Initialize toastr notifications
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 3000
            };
        });
    </script>
</body>
</html> 