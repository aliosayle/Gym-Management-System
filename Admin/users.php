<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include necessary files
session_start();
require 'layouts/config.php';
require 'layouts/check_permission.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: auth-login.php");
    exit;
}

// Check if user has permission to manage users
$can_manage_users = has_permission('can_manage_users', $pdo);
$is_admin = false;

// Also check admin status for certain operations
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$is_admin = isset($user['isadmin']) && $user['isadmin'] == 1;

if (!$can_manage_users) {
    $_SESSION['error_message'] = "You don't have permission to access the user management.";
    header("Location: index.php");
    exit;
}

// Process form submission for adding new user
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    try {
        $username = trim($_POST['username']);
        $usermail = trim($_POST['usermail']);
        $password = trim($_POST['password']);
        $isAdmin = isset($_POST['isadmin']) ? 1 : 0;
        
        // Validate input
        if (empty($username) || empty($usermail) || empty($password)) {
            throw new Exception("All fields are required");
        }
        
        if (!filter_var($usermail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if username or email already exists
        $checkQuery = "SELECT * FROM users WHERE username = :username OR useremail = :useremail";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute(['username' => $username, 'useremail' => $usermail]);
        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            if ($existingUser['username'] === $username) {
                throw new Exception("Username already exists");
            } else {
                throw new Exception("Email already exists");
            }
        }
        
        // Create new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertQuery = "INSERT INTO users (username, useremail, password, isadmin, created_at) 
                       VALUES (:username, :useremail, :password, :isadmin, NOW())";
        $insertStmt = $pdo->prepare($insertQuery);
        
        $result = $insertStmt->execute([
            'username' => $username,
            'useremail' => $usermail,
            'password' => $hashedPassword,
            'isadmin' => $isAdmin
        ]);
        
        if ($result) {
            $message = "User added successfully";
            $messageType = 'success';
            
            // Get the new user's ID
            $newUserId = $pdo->lastInsertId();
            
            // Create default permissions for the new user
            $insertPermQuery = "INSERT INTO user_permissions (user_id, can_view_dashboard, can_manage_clients) 
                              VALUES (:user_id, 1, 1)";
            $insertPermStmt = $pdo->prepare($insertPermQuery);
            $insertPermStmt->execute(['user_id' => $newUserId]);
            
            // Assign to the default branch
            $insertBranchQuery = "INSERT INTO user_branches (user_id, branch_id) 
                                VALUES (:user_id, 1)";
            $insertBranchStmt = $pdo->prepare($insertBranchQuery);
            $insertBranchStmt->execute(['user_id' => $newUserId]);
        } else {
            throw new Exception("Error adding user");
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Delete user
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $userId = $_GET['id'];
        
        // Check if user exists
        $checkQuery = "SELECT username FROM users WHERE id = :id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute(['id' => $userId]);
        $userToDelete = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userToDelete) {
            throw new Exception("User not found");
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete user permissions (if exists)
        $deletePermQuery = "DELETE FROM user_permissions WHERE user_id = :user_id";
        $deletePermStmt = $pdo->prepare($deletePermQuery);
        $deletePermStmt->execute(['user_id' => $userId]);
        
        // Delete user branches (if exists)
        $deleteBranchQuery = "DELETE FROM user_branches WHERE user_id = :user_id";
        $deleteBranchStmt = $pdo->prepare($deleteBranchQuery);
        $deleteBranchStmt->execute(['user_id' => $userId]);
        
        // Delete user
        $deleteUserQuery = "DELETE FROM users WHERE id = :id";
        $deleteUserStmt = $pdo->prepare($deleteUserQuery);
        $deleteUserStmt->execute(['id' => $userId]);
        
        // Commit transaction
        $pdo->commit();
        
        $message = "User " . htmlspecialchars($userToDelete['username']) . " deleted successfully";
        $messageType = 'success';
    } catch (Exception $e) {
        // Roll back transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get all users
$usersQuery = "SELECT * FROM users ORDER BY username ASC";
$usersStmt = $pdo->prepare($usersQuery);
$usersStmt->execute();
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>User Management | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <?php include 'layouts/head-style.php'; ?>
    
    <!-- Required DataTables CSS -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    
    <style>
        .user-action-btn {
            margin-right: 5px;
        }
        
        .action-cell {
            white-space: nowrap;
        }
        
        .admin-badge {
            background-color: #6259ca;
            color: #fff;
        }
        
        .user-badge {
            background-color: #4ac6ec;
            color: #fff;
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
                    <!-- Start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">User Management</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">User Management</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End page title -->
                    
                    <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Manage Users</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                        <i class="fas fa-user-plus me-2"></i>Add New User
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="usersTable" class="table table-striped table-bordered dt-responsive nowrap">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Role</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($allUsers as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['useremail']); ?></td>
                                                    <td>
                                                        <?php if ($user['isadmin'] == 1): ?>
                                                        <span class="badge admin-badge">Admin</span>
                                                        <?php else: ?>
                                                        <span class="badge user-badge">User</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                    <td class="action-cell">
                                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info user-action-btn">
                                                            <i class="fas fa-pencil-alt"></i> Edit
                                                        </a>
                                                        <a href="permission_manager.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning user-action-btn">
                                                            <i class="fas fa-key"></i> Permissions
                                                        </a>
                                                        <a href="branch_manager.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success user-action-btn">
                                                            <i class="fas fa-building"></i> Branches
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-danger user-action-btn" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </a>
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
            </div>
            
            <?php include 'layouts/footer.php'; ?>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="users.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="usermail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="usermail" name="usermail" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isadmin" name="isadmin">
                            <label class="form-check-label" for="isadmin">Admin Access</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user <span id="deleteUserName" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone and will remove all user data including permissions and branch assignments.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete User</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'layouts/vendor-scripts.php'; ?>
    
    <!-- jQuery -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <!-- DataTables -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Using vanilla JS to initialize DataTables to avoid jQuery dependency issues
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
            jQuery('#usersTable').DataTable({
                responsive: true,
                lengthChange: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']]
            });
        } else {
            console.error('jQuery or DataTables not loaded properly');
        }
    });
    
    // Function to show delete confirmation modal
    function confirmDelete(userId, username) {
        document.getElementById('deleteUserName').textContent = username;
        document.getElementById('confirmDeleteBtn').href = 'users.php?action=delete&id=' + userId;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
    </script>
</body>
</html> 