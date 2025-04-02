<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: auth-login.php");
    exit;
}

// Permission check - restrict non-admins to only POS and clients pages
if(isset($_SESSION["id"])) {
    // Get the current page
    $current_page = basename($_SERVER['PHP_SELF']);
    $allowed_pages = ['pos.php', 'clients.php', 'index.php'];
    
    // Check if user is admin
    require_once 'config.php';
    $user_id = $_SESSION["id"];
    
    // Check admin status
    try {
        $query = "SELECT isadmin FROM users WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If not admin and trying to access a restricted page, redirect to clients page
        if (isset($user['isadmin']) && $user['isadmin'] != 1) {
            if (!in_array($current_page, $allowed_pages)) {
                // Get directory path to construct absolute URL
                $redirect_path = dirname($_SERVER['PHP_SELF']);
                $redirect_path = rtrim($redirect_path, '/') . '/clients.php';
                header("location: $redirect_path");
                exit;
            }
        }
    } catch (PDOException $e) {
        // Log error but don't disrupt user experience
        error_log("Permission check error: " . $e->getMessage());
    }
}

// Function to get user's assigned branches
function getUserBranches($pdo, $user_id) {
    $query = "SELECT b.*, c.name as company_name 
              FROM user_branches ub 
              JOIN branches b ON ub.branch_id = b.id 
              JOIN companies c ON b.company_id = c.id 
              WHERE ub.user_id = :user_id 
              ORDER BY c.name, b.name";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to check if user has multiple branches
function hasMultipleBranches($pdo, $user_id) {
    $query = "SELECT COUNT(*) as count FROM user_branches WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 1;
}

// Check if user is admin
if(isset($_SESSION["id"])) {
    $user_id = $_SESSION["id"];
    
    // Check admin status
    try {
        $query = "SELECT isadmin FROM users WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If user is not admin and has multiple branches, show branch selection
        if (!isset($user['isadmin']) || $user['isadmin'] != 1) {
            if (!isset($_SESSION['selected_branch_id']) && hasMultipleBranches($pdo, $user_id)) {
                $_SESSION['needs_branch_selection'] = true;
            } else if (!isset($_SESSION['selected_branch_id'])) {
                // If user has only one branch, set it automatically
                $branches = getUserBranches($pdo, $user_id);
                if (!empty($branches)) {
                    $_SESSION['selected_branch_id'] = $branches[0]['id'];
                    $_SESSION['selected_branch_name'] = $branches[0]['name'];
                    $_SESSION['selected_company_name'] = $branches[0]['company_name'];
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
    }
}
?>