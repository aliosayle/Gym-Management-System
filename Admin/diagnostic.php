<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>System Diagnostics | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <?php include 'layouts/head-style.php'; ?>
</head>

<body>
    <div id="layout-wrapper">
        <?php include 'layouts/menu.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">System Diagnostics</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                        <li class="breadcrumb-item active">Diagnostics</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">User Information</h4>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>User ID</th>
                                                    <td><?php echo $_SESSION['id'] ?? 'Not set'; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Username</th>
                                                    <td><?php echo $_SESSION['username'] ?? 'Not set'; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Selected Branch ID</th>
                                                    <td><?php echo $_SESSION['selected_branch_id'] ?? 'Not set'; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Selected Branch Name</th>
                                                    <td><?php echo $_SESSION['selected_branch_name'] ?? 'Not set'; ?></td>
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
                                <div class="card-body">
                                    <h4 class="card-title">User Branch Assignments</h4>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Branch ID</th>
                                                    <th>Branch Name</th>
                                                    <th>Company Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                try {
                                                    $query = "SELECT b.id, b.name, c.name as company_name
                                                              FROM branches b
                                                              JOIN companies c ON b.company_id = c.id
                                                              JOIN user_branches ub ON b.id = ub.branch_id
                                                              WHERE ub.user_id = :user_id";
                                                    $stmt = $pdo->prepare($query);
                                                    $stmt->execute(['user_id' => $_SESSION['id']]);
                                                    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    if (count($branches) > 0) {
                                                        foreach ($branches as $branch) {
                                                            echo "<tr>
                                                                <td>{$branch['id']}</td>
                                                                <td>{$branch['name']}</td>
                                                                <td>{$branch['company_name']}</td>
                                                            </tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='3'>No branch assignments found</td></tr>";
                                                    }
                                                } catch (PDOException $e) {
                                                    echo "<tr><td colspan='3'>Error: " . $e->getMessage() . "</td></tr>";
                                                }
                                                ?>
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
                                <div class="card-body">
                                    <h4 class="card-title">Available Branches</h4>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Branch ID</th>
                                                    <th>Branch Name</th>
                                                    <th>Company Name</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                try {
                                                    $query = "SELECT b.id, b.name, c.name as company_name
                                                              FROM branches b
                                                              JOIN companies c ON b.company_id = c.id";
                                                    $stmt = $pdo->prepare($query);
                                                    $stmt->execute();
                                                    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    if (count($branches) > 0) {
                                                        foreach ($branches as $branch) {
                                                            echo "<tr>
                                                                <td>{$branch['id']}</td>
                                                                <td>{$branch['name']}</td>
                                                                <td>{$branch['company_name']}</td>
                                                                <td>
                                                                    <form method='post'>
                                                                        <input type='hidden' name='assign_branch_id' value='{$branch['id']}'>
                                                                        <button type='submit' class='btn btn-sm btn-primary'>Assign to User</button>
                                                                    </form>
                                                                </td>
                                                            </tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='4'>No branches found</td></tr>";
                                                    }
                                                } catch (PDOException $e) {
                                                    echo "<tr><td colspan='4'>Error: " . $e->getMessage() . "</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Handle branch assignment
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_branch_id'])) {
                        $branch_id = $_POST['assign_branch_id'];
                        
                        try {
                            // Check if assignment already exists
                            $check_query = "SELECT COUNT(*) FROM user_branches 
                                           WHERE user_id = :user_id AND branch_id = :branch_id";
                            $check_stmt = $pdo->prepare($check_query);
                            $check_stmt->execute([
                                'user_id' => $_SESSION['id'],
                                'branch_id' => $branch_id
                            ]);
                            $exists = $check_stmt->fetchColumn() > 0;
                            
                            if (!$exists) {
                                // Insert new assignment
                                $insert_query = "INSERT INTO user_branches (user_id, branch_id) 
                                                VALUES (:user_id, :branch_id)";
                                $insert_stmt = $pdo->prepare($insert_query);
                                $insert_stmt->execute([
                                    'user_id' => $_SESSION['id'],
                                    'branch_id' => $branch_id
                                ]);
                                
                                echo "<div class='alert alert-success'>Successfully assigned branch ID {$branch_id} to user</div>";
                                
                                // Update session variables
                                $_SESSION['selected_branch_id'] = $branch_id;
                                
                                // Get branch name
                                $name_query = "SELECT b.name, c.name as company_name 
                                              FROM branches b
                                              JOIN companies c ON b.company_id = c.id
                                              WHERE b.id = :branch_id";
                                $name_stmt = $pdo->prepare($name_query);
                                $name_stmt->execute(['branch_id' => $branch_id]);
                                $branch_info = $name_stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($branch_info) {
                                    $_SESSION['selected_branch_name'] = $branch_info['name'];
                                    $_SESSION['selected_company_name'] = $branch_info['company_name'];
                                }
                                
                                // Redirect to refresh page
                                echo "<script>window.location.href = 'diagnostic.php';</script>";
                            } else {
                                echo "<div class='alert alert-info'>User is already assigned to branch ID {$branch_id}</div>";
                            }
                        } catch (PDOException $e) {
                            echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                        }
                    }
                    ?>
                    
                </div>
            </div>
            <?php include 'layouts/footer.php'; ?>
        </div>
    </div>

    <?php include 'layouts/vendor-scripts.php'; ?>
</body>
</html> 