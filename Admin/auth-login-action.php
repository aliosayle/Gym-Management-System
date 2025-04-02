<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();
include 'layouts/config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header("location: auth-login.php");
    exit;
}

// Validate inputs
if (!isset($_POST['username']) || !isset($_POST['password']) || empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error'] = 'Username and password are required';
    header("location: auth-login.php");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

try {
    // Check if user exists
    $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Invalid username or password';
        header("location: auth-login.php");
        exit;
    }

    // Set session variables
    $_SESSION['id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['useremail'] = $user['useremail'];
    $_SESSION['isadmin'] = $user['isadmin'];
    
    // Check if user is admin
    if ($user['isadmin'] == 1) {
        // Admin users go directly to dashboard
        header("location: index.php");
        exit;
    }
    
    // Check if user has multiple branches
    $branches_query = "SELECT COUNT(*) as branch_count 
                      FROM user_branches 
                      WHERE user_id = :user_id";
    $branches_stmt = $pdo->prepare($branches_query);
    $branches_stmt->execute(['user_id' => $user['id']]);
    $branch_count = $branches_stmt->fetch(PDO::FETCH_ASSOC)['branch_count'];
    
    if ($branch_count > 1) {
        // User has multiple branches, redirect to selection page
        header("location: select_branch.php");
        exit;
    } else if ($branch_count == 1) {
        // User has only one branch, set it automatically
        $branch_query = "SELECT b.id, b.name 
                        FROM user_branches ub 
                        JOIN branches b ON ub.branch_id = b.id 
                        WHERE ub.user_id = :user_id 
                        LIMIT 1";
        $branch_stmt = $pdo->prepare($branch_query);
        $branch_stmt->execute(['user_id' => $user['id']]);
        $branch = $branch_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($branch) {
            $_SESSION['selected_branch_id'] = $branch['id'];
            $_SESSION['selected_branch_name'] = $branch['name'];
        }
        
        header("location: index.php");
        exit;
    } else {
        // User has no branches
        $_SESSION['error'] = 'Your account does not have access to any branch. Please contact the administrator.';
        session_destroy();
        header("location: auth-login.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header("location: auth-login.php");
    exit;
}
?> 