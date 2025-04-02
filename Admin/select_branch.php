<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header("location: auth-login.php");
    exit;
}

// Include database connection
include 'layouts/config.php';

// Check if branch is already selected
if (isset($_SESSION['selected_branch_id'])) {
    // User already has a branch selected, redirect to dashboard
    header("location: index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Get user's assigned branches
$branches_query = "SELECT b.id, b.name, c.name as company_name 
                  FROM user_branches ub
                  JOIN branches b ON ub.branch_id = b.id
                  JOIN companies c ON b.company_id = c.id
                  WHERE ub.user_id = :user_id
                  ORDER BY c.name, b.name";
$stmt = $pdo->prepare($branches_query);
$stmt->execute(['user_id' => $user_id]);
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If user has only one branch, select it automatically
if (count($branches) == 1) {
    $_SESSION['selected_branch_id'] = $branches[0]['id'];
    $_SESSION['selected_branch_name'] = $branches[0]['name'];
    header("location: index.php");
    exit;
}

// If user has no branches assigned
if (count($branches) == 0) {
    // Check if user is admin
    $admin_query = "SELECT isadmin FROM users WHERE id = :id";
    $admin_stmt = $pdo->prepare($admin_query);
    $admin_stmt->execute(['id' => $user_id]);
    $user = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($user['isadmin']) && $user['isadmin'] == 1) {
        // Admin users can proceed without branch selection
        header("location: index.php");
        exit;
    } else {
        // Regular users with no branches - show error
        $_SESSION['error'] = 'You have no branches assigned to your account. Please contact the administrator.';
        header("location: auth-logout.php");
        exit;
    }
}

// Process branch selection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['branch_id'])) {
    $branch_id = $_POST['branch_id'];
    
    // Validate that the branch exists and is assigned to this user
    $valid_branch = false;
    foreach ($branches as $branch) {
        if ($branch['id'] == $branch_id) {
            $valid_branch = true;
            $_SESSION['selected_branch_id'] = $branch_id;
            $_SESSION['selected_branch_name'] = $branch['name'];
            $_SESSION['selected_company_name'] = $branch['company_name'];
            break;
        }
    }
    
    if ($valid_branch) {
        header("location: index.php");
        exit;
    } else {
        $error_message = "Invalid branch selection.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Select Branch | Gym Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons CSS -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App CSS -->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style"/>
    <style>
        .branch-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .branch-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .branch-card.selected {
            border-color: #556ee6;
            background-color: rgba(85, 110, 230, 0.1);
        }
        .branch-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="authentication-bg">
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-8">
                    <div class="text-center mb-4">
                        <a href="index.php" class="auth-logo mb-5 d-block">
                            <img src="assets/images/logo-dark.png" alt="" height="30" class="logo logo-dark">
                            <img src="assets/images/logo-light.png" alt="" height="30" class="logo logo-light">
                        </a>
                        <h4>Select Your Branch</h4>
                        <p class="text-muted mb-4">Please select the branch you want to work with</p>
                    </div>

                    <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger text-center mb-4" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body p-4">
                            <form action="select_branch.php" method="post" id="branchSelectForm">
                                <input type="hidden" name="branch_id" id="selected_branch_id">
                                
                                <div class="row">
                                    <?php foreach($branches as $branch): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card branch-card" data-branch-id="<?php echo $branch['id']; ?>">
                                            <div class="card-body text-center py-4">
                                                <div class="branch-icon">
                                                    <i class="mdi mdi-office-building text-primary"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($branch['name']); ?></h5>
                                                <p class="card-text text-muted small"><?php echo htmlspecialchars($branch['company_name']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="d-grid mt-3">
                                    <button class="btn btn-primary waves-effect waves-light" type="submit" id="continueBtn" disabled>Continue</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <p>Not the right account? <a href="auth-logout.php" class="fw-medium text-primary"> Logout </a> </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Branch card selection
            $('.branch-card').click(function() {
                $('.branch-card').removeClass('selected');
                $(this).addClass('selected');
                
                const branchId = $(this).data('branch-id');
                $('#selected_branch_id').val(branchId);
                $('#continueBtn').prop('disabled', false);
            });
            
            // Submit form when a branch card is double-clicked
            $('.branch-card').dblclick(function() {
                const branchId = $(this).data('branch-id');
                $('#selected_branch_id').val(branchId);
                $('#branchSelectForm').submit();
            });
        });
    </script>
</body>
</html> 