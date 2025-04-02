<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();
require 'layouts/config.php';

// Debug POST data if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST Request received: ' . print_r($_POST, true));
}

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

// Check if user_branches table exists, create if not
try {
    $checkTableQuery = "SHOW TABLES LIKE 'user_branches'";
    $checkTableStmt = $pdo->prepare($checkTableQuery);
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() == 0) {
        // Table doesn't exist, create it
        $createTableQuery = "CREATE TABLE `user_branches` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `assigned_by` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_branch_unique` (`user_id`,`branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($createTableQuery);
    }
} catch (PDOException $e) {
    $tableError = "Error checking/creating branches table: " . $e->getMessage();
}

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_branches'])) {
    try {
        $userId = $_POST['user_id'];
        $branchIds = isset($_POST['branches']) ? $_POST['branches'] : [];
        
        // First check if the user exists
        $checkUserQuery = "SELECT username FROM users WHERE id = :id";
        $checkUserStmt = $pdo->prepare($checkUserQuery);
        $checkUserStmt->execute(['id' => $userId]);
        $userData = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            throw new Exception("User not found");
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete current branch assignments
        $deleteQuery = "DELETE FROM user_branches WHERE user_id = :user_id";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute(['user_id' => $userId]);
        
        // Insert new branch assignments
        if (!empty($branchIds)) {
            // Use a simplified query for validation
            $validBranches = [];
            $invalidBranches = [];
            
            // Check each branch ID individually instead of using IN clause
            $checkBranchQuery = "SELECT id FROM branches WHERE id = ?";
            $checkBranchStmt = $pdo->prepare($checkBranchQuery);
            
            foreach ($branchIds as $branchId) {
                $checkBranchStmt->execute([$branchId]);
                if ($checkBranchStmt->rowCount() > 0) {
                    $validBranches[] = $branchId;
                } else {
                    $invalidBranches[] = $branchId;
                }
            }
            
            if (!empty($invalidBranches)) {
                throw new Exception("Invalid branch selection. Branch IDs not found: " . implode(', ', $invalidBranches));
            }
            
            $insertQuery = "INSERT INTO user_branches (user_id, branch_id, assigned_by) VALUES (:user_id, :branch_id, :assigned_by)";
            $insertStmt = $pdo->prepare($insertQuery);
            
            foreach ($validBranches as $branchId) {
                $insertParams = [
                    'user_id' => $userId,
                    'branch_id' => $branchId,
                    'assigned_by' => $user_id
                ];
                
                // For debugging
                error_log("Inserting branch: " . print_r($insertParams, true));
                
                $insertStmt->execute($insertParams);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        $message = "Branch assignments updated successfully for " . $userData['username'];
        $messageType = 'success';
    } catch (Exception $e) {
        // Roll back transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
        error_log("Branch assignment error: " . $e->getMessage());
    }
}

// Get user ID from URL
if (isset($_GET['user_id'])) {
    $selectedUserId = $_GET['user_id'];
    
    // Get user data
    $userQuery = "SELECT id, username FROM users WHERE id = :id";
    $userStmt = $pdo->prepare($userQuery);
    $userStmt->execute(['id' => $selectedUserId]);
    $selectedUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get assigned branches
    if ($selectedUser) {
        $branchesQuery = "SELECT branch_id FROM user_branches WHERE user_id = :user_id";
        $branchesStmt = $pdo->prepare($branchesQuery);
        $branchesStmt->execute(['user_id' => $selectedUserId]);
        $assignedBranches = $branchesStmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $assignedBranches = [];
    }
} else {
    $selectedUserId = null;
    $selectedUser = null;
    $assignedBranches = [];
}

// Get all users
$usersQuery = "SELECT id, username FROM users ORDER BY username ASC";
$usersStmt = $pdo->prepare($usersQuery);
$usersStmt->execute();
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all branches
$branchesQuery = "SELECT * FROM branches ORDER BY id ASC";
$branchesStmt = $pdo->prepare($branchesQuery);
$branchesStmt->execute();
$allBranches = $branchesStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if branches table is empty
if (empty($allBranches)) {
    error_log("No branches found in the database. This could cause assignment issues.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Branch Manager | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <?php include 'layouts/head-style.php'; ?>
    
    <style>
        .branch-item {
            padding: 10px;
            margin-bottom: 8px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        
        .branch-item:hover {
            background-color: #f8f9fa;
        }
        
        .branch-item.selected {
            background-color: #e6f2ff;
            border-color: #99c2ff;
        }
        
        .debug-section {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
        }

        /* Make the form more apparent */
        .branch-selection-form {
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 20px;
            background-color: #fcfcfc;
        }

        /* Style the save button to make it stand out */
        .save-branches-btn {
            font-weight: bold;
            padding: 10px 20px;
        }

        /* Instructions box */
        .instructions-box {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
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
                                <h4 class="mb-sm-0 font-size-18">Branch Manager</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                                        <li class="breadcrumb-item active">Branch Manager</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End page title -->
                    
                    <!-- Debug information -->
                    <?php if (isset($tableError) || $messageType === 'danger'): ?>
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="card-title mb-0">Debug Information</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($tableError)): ?>
                                    <div class="alert alert-danger mb-3">
                                        <?php echo $tableError; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($messageType === 'danger'): ?>
                                    <div class="alert alert-danger mb-3">
                                        <?php echo $message; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <p><strong>Available Branches:</strong> <?php echo count($allBranches); ?></p>
                                    <p><strong>User Branches Table Exists:</strong> <?php echo $checkTableStmt->rowCount() > 0 ? 'Yes' : 'No'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($message) && $messageType !== 'danger'): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Select User</h5>
                                </div>
                                <div class="card-body">
                                    <form action="branch_manager.php" method="get">
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <select name="user_id" class="form-select">
                                                    <option value="">Select a user...</option>
                                                    <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>" <?php echo ($selectedUserId == $user['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['username']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-primary">Load User</button>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <div class="mt-3">
                                        <a href="users.php" class="btn btn-secondary">Back to User List</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($selectedUser): ?>
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Assign Branches for <?php echo htmlspecialchars($selectedUser['username']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($allBranches)): ?>
                                    <div class="alert alert-warning">
                                        <h5><i class="fas fa-exclamation-triangle me-2"></i>No Branches Available</h5>
                                        <p>There are no branches in the system. Please create branches first before assigning them to users.</p>
                                        <a href="branches.php" class="btn btn-sm btn-primary mt-2">Manage Branches</a>
                                    </div>
                                    <?php else: ?>
                                    <div class="instructions-box">
                                        <h6>How to use:</h6>
                                        <ol>
                                            <li>Check the boxes next to branches you want to assign to this user</li>
                                            <li>When finished, click the "Save Branch Assignments" button at the bottom</li>
                                            <li>Nothing will be saved until you click the save button</li>
                                        </ol>
                                    </div>
                                    
                                    <!-- Two-step process to avoid accidental submission -->
                                    <form action="branch_manager.php" method="post" class="branch-selection-form">
                                        <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>">
                                        <input type="hidden" name="save_branches" value="1">
                                        
                                        <div class="mb-3">
                                            <p><strong>Select the branches this user can access:</strong></p>
                                            
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th width="50">Select</th>
                                                        <th>Branch Name</th>
                                                        <th width="80">ID</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($allBranches as $branch): ?>
                                                    <?php 
                                                        $branchName = isset($branch['name']) ? $branch['name'] : 
                                                            (isset($branch['branch_name']) ? $branch['branch_name'] : 'Branch ' . $branch['id']);
                                                        $isAssigned = in_array($branch['id'], $assignedBranches);
                                                    ?>
                                                    <tr class="<?php echo $isAssigned ? 'table-primary' : ''; ?>">
                                                        <td class="text-center">
                                                            <input type="checkbox" class="form-check-input"
                                                                   name="branches[]" 
                                                                   value="<?php echo $branch['id']; ?>" 
                                                                   <?php echo $isAssigned ? 'checked' : ''; ?>>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($branchName); ?></td>
                                                        <td class="text-center"><?php echo $branch['id']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-success save-branches-btn">
                                                <i class="fas fa-save me-1"></i> Save Branch Assignments
                                            </button>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php include 'layouts/footer.php'; ?>
        </div>
    </div>
    
    <?php include 'layouts/vendor-scripts.php'; ?>
</body>
</html> 