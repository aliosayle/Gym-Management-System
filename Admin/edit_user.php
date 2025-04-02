<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();
require 'layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: auth-login.php");
    exit;
}

// Check if user is admin
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($user['isadmin']) || $user['isadmin'] != 1) {
    header("Location: clients.php");
    exit;
}

// Process form submission for updating user
$message = '';
$messageType = '';

// Get user ID from URL
if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$editUserId = $_GET['id'];

// Get user data
$userQuery = "SELECT * FROM users WHERE id = :id";
$userStmt = $pdo->prepare($userQuery);
$userStmt->execute(['id' => $editUserId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    header("Location: users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    try {
        $username = trim($_POST['username']);
        $usermail = trim($_POST['usermail']);
        $password = trim($_POST['password']);
        $isAdmin = isset($_POST['isadmin']) ? 1 : 0;
        
        // Validate input
        if (empty($username) || empty($usermail)) {
            throw new Exception("Username and email are required");
        }
        
        if (!filter_var($usermail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if username or email already exists (excluding current user)
        $checkQuery = "SELECT * FROM users WHERE (username = :username OR useremail = :useremail) AND id != :id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute(['username' => $username, 'useremail' => $usermail, 'id' => $editUserId]);
        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            if ($existingUser['username'] === $username) {
                throw new Exception("Username already exists");
            } else {
                throw new Exception("Email already exists");
            }
        }
        
        // Update user
        if (!empty($password)) {
            // Update with new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $updateQuery = "UPDATE users SET username = :username, useremail = :useremail, password = :password, isadmin = :isadmin 
                           WHERE id = :id";
            $updateStmt = $pdo->prepare($updateQuery);
            
            $result = $updateStmt->execute([
                'username' => $username,
                'useremail' => $usermail,
                'password' => $hashedPassword,
                'isadmin' => $isAdmin,
                'id' => $editUserId
            ]);
        } else {
            // Update without changing password
            $updateQuery = "UPDATE users SET username = :username, useremail = :useremail, isadmin = :isadmin 
                           WHERE id = :id";
            $updateStmt = $pdo->prepare($updateQuery);
            
            $result = $updateStmt->execute([
                'username' => $username,
                'useremail' => $usermail,
                'isadmin' => $isAdmin,
                'id' => $editUserId
            ]);
        }
        
        if ($result) {
            $message = "User updated successfully";
            $messageType = 'success';
            
            // Update the userData variable to reflect the changes
            $userData['username'] = $username;
            $userData['useremail'] = $usermail;
            $userData['isadmin'] = $isAdmin;
        } else {
            throw new Exception("Error updating user");
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Edit User | Gym Management System</title>
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
                                <h4 class="mb-sm-0 font-size-18">Edit User</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                                        <li class="breadcrumb-item active">Edit User</li>
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
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">Edit User: <?php echo htmlspecialchars($userData['username']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <form action="edit_user.php?id=<?php echo $editUserId; ?>" method="post">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="usermail" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="usermail" name="usermail" value="<?php echo htmlspecialchars($userData['useremail']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="password" name="password">
                                            <small class="text-muted">Leave blank to keep current password</small>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="isadmin" name="isadmin" <?php echo ($userData['isadmin'] == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="isadmin">Admin Access</label>
                                        </div>
                                        <div class="mb-3">
                                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                                            <a href="users.php" class="btn btn-secondary ms-2">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">User Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <div class="avatar-lg mx-auto mb-3">
                                            <div class="avatar-title bg-light text-primary display-4 rounded-circle">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <h5 class="font-size-16"><?php echo htmlspecialchars($userData['username']); ?></h5>
                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($userData['useremail']); ?></p>
                                        <p class="mb-0">
                                            <?php if ($userData['isadmin'] == 1): ?>
                                            <span class="badge bg-primary">Administrator</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Regular User</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <div class="d-grid gap-2">
                                            <a href="permission_manager.php?user_id=<?php echo $editUserId; ?>" class="btn btn-warning">
                                                <i class="fas fa-key me-1"></i> Manage Permissions
                                            </a>
                                            <a href="branch_manager.php?user_id=<?php echo $editUserId; ?>" class="btn btn-success">
                                                <i class="fas fa-building me-1"></i> Manage Branches
                                            </a>
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
    </div>
    
    <?php include 'layouts/vendor-scripts.php'; ?>
</body>
</html> 