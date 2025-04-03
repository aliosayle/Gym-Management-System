<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include necessary files
include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';
include 'layouts/check_permission.php';

$user_id = $_SESSION['id'];

// Get user information
$user_query = "SELECT * FROM users WHERE id = :id";
$user_stmt = $pdo->prepare($user_query);
$user_stmt->execute(['id' => $user_id]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get user permissions
$permissions_query = "SELECT * FROM user_permissions WHERE user_id = :user_id";
$permissions_stmt = $pdo->prepare($permissions_query);
$permissions_stmt->execute(['user_id' => $user_id]);
$permissions = $permissions_stmt->fetch(PDO::FETCH_ASSOC);

// Get user branches
$branches_query = "SELECT b.*, c.name as company_name 
                  FROM branches b
                  JOIN user_branches ub ON b.id = ub.branch_id
                  JOIN companies c ON b.company_id = c.id
                  WHERE ub.user_id = :user_id";
$branches_stmt = $pdo->prepare($branches_query);
$branches_stmt->execute(['user_id' => $user_id]);
$branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check specific permissions
$can_view_dashboard = has_permission('can_view_dashboard', $pdo);
$can_manage_clients = has_permission('can_manage_clients', $pdo);
$can_add_client = has_permission('can_add_client', $pdo);
$can_edit_client = has_permission('can_edit_client', $pdo);
$can_delete_client = has_permission('can_delete_client', $pdo);
$can_manage_inventory = has_permission('can_manage_inventory', $pdo);
$can_manage_invoices = has_permission('can_manage_invoices', $pdo);
$can_use_pos = has_permission('can_use_pos', $pdo);
$can_view_reports = has_permission('can_view_reports', $pdo);
$can_manage_packages = has_permission('can_manage_packages', $pdo);
$can_manage_companies = has_permission('can_manage_companies', $pdo);
$can_manage_branches = has_permission('can_manage_branches', $pdo);
$can_manage_users = has_permission('can_manage_users', $pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Permissions | GMS</title>
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
                    <!-- Start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">My Permissions</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">My Permissions</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">User Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0">
                                            <tbody>
                                                <tr>
                                                    <th width="200">User ID</th>
                                                    <td><?php echo htmlspecialchars($user_id); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Username</th>
                                                    <td><?php echo htmlspecialchars($user_info['username'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Email</th>
                                                    <td><?php echo htmlspecialchars($user_info['useremail'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Admin Status</th>
                                                    <td>
                                                        <?php if (isset($user_info['isadmin']) && $user_info['isadmin'] == 1): ?>
                                                            <span class="badge bg-success">Admin</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Regular User</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Assigned Permissions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Permission</th>
                                                    <th>Status</th>
                                                    <th>Function Result</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>View Dashboard</td>
                                                    <td><?php echo isset($permissions['can_view_dashboard']) && $permissions['can_view_dashboard'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_view_dashboard ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Manage Clients</td>
                                                    <td><?php echo isset($permissions['can_manage_clients']) && $permissions['can_manage_clients'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_manage_clients ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Add Client</td>
                                                    <td><?php echo isset($permissions['can_add_client']) && $permissions['can_add_client'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_add_client ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Edit Client</td>
                                                    <td><?php echo isset($permissions['can_edit_client']) && $permissions['can_edit_client'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_edit_client ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Delete Client</td>
                                                    <td><?php echo isset($permissions['can_delete_client']) && $permissions['can_delete_client'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_delete_client ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Manage Inventory</td>
                                                    <td><?php echo isset($permissions['can_manage_inventory']) && $permissions['can_manage_inventory'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_manage_inventory ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Manage Invoices</td>
                                                    <td><?php echo isset($permissions['can_manage_invoices']) && $permissions['can_manage_invoices'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_manage_invoices ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Use POS</td>
                                                    <td><?php echo isset($permissions['can_use_pos']) && $permissions['can_use_pos'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_use_pos ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>View Reports</td>
                                                    <td><?php echo isset($permissions['can_view_reports']) && $permissions['can_view_reports'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_view_reports ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Manage Packages</td>
                                                    <td><?php echo isset($permissions['can_manage_packages']) && $permissions['can_manage_packages'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_manage_packages ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Manage Companies</td>
                                                    <td><?php echo isset($permissions['can_manage_companies']) && $permissions['can_manage_companies'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_manage_companies ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Manage Branches</td>
                                                    <td><?php echo isset($permissions['can_manage_branches']) && $permissions['can_manage_branches'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_manage_branches ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Manage Users</td>
                                                    <td><?php echo isset($permissions['can_manage_users']) && $permissions['can_manage_users'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                                                    <td><?php echo $can_manage_users ? '<span class="badge bg-success">Granted</span>' : '<span class="badge bg-danger">Denied</span>'; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Assigned Branches</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Branch ID</th>
                                                    <th>Branch Name</th>
                                                    <th>Company</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($branches)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No branches assigned</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($branches as $branch): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($branch['id']); ?></td>
                                                        <td><?php echo htmlspecialchars($branch['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($branch['company_name']); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'layouts/footer.php'; ?>
        </div>
    </div>
    <?php include 'layouts/vendor-scripts.php'; ?>
    <script src="assets/js/app.js"></script>
</body>
</html> 