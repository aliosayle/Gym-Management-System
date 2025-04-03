<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: auth-login.php");
    exit;
}

// Ensure database connection is available
require_once 'config.php';

// CRITICAL: Ensure branch_id is always set to something valid
if(!isset($_SESSION['selected_branch_id'])) {
    error_log("No branch_id in session, attempting to set default branch for user " . $_SESSION["id"]);
    
    try {
        // First try to get a branch from user_branches
        $query = "SELECT branch_id FROM user_branches WHERE user_id = :user_id LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $_SESSION["id"]]);
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($branch) {
            $_SESSION['selected_branch_id'] = $branch['branch_id'];
            error_log("Set default branch_id to " . $branch['branch_id'] . " from user_branches");
            
            // Also get the branch name
            $name_query = "SELECT b.name, c.name as company_name FROM branches b 
                          JOIN companies c ON b.company_id = c.id 
                          WHERE b.id = :branch_id";
            $name_stmt = $pdo->prepare($name_query);
            $name_stmt->execute(['branch_id' => $branch['branch_id']]);
            $branch_info = $name_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($branch_info) {
                $_SESSION['selected_branch_name'] = $branch_info['name'];
                $_SESSION['selected_company_name'] = $branch_info['company_name'];
            }
        } else {
            // If no user_branches record, try to insert one for branch 1
            $insert = "INSERT INTO user_branches (user_id, branch_id) VALUES (:user_id, 1)";
            $insert_stmt = $pdo->prepare($insert);
            $insert_stmt->execute(['user_id' => $_SESSION["id"]]);
            $_SESSION['selected_branch_id'] = 1;
            error_log("No branches found, assigned user to branch_id 1");
            
            // Get branch info for the newly assigned branch
            $name_query = "SELECT b.name, c.name as company_name FROM branches b 
                          JOIN companies c ON b.company_id = c.id 
                          WHERE b.id = 1";
            $name_stmt = $pdo->prepare($name_query);
            $name_stmt->execute();
            $branch_info = $name_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($branch_info) {
                $_SESSION['selected_branch_name'] = $branch_info['name'];
                $_SESSION['selected_company_name'] = $branch_info['company_name'];
            }
        }
    } catch (PDOException $e) {
        // Last resort fallback
        error_log("Failed to set branch_id: " . $e->getMessage());
        $_SESSION['selected_branch_id'] = 1; // Default to branch 1 as absolute last resort
        $_SESSION['selected_branch_name'] = "Default Branch";
    }
}

// Permission check - restrict non-admins to only POS and clients pages
if(isset($_SESSION["id"])) {
    // Get the current page
    $current_page = basename($_SERVER['PHP_SELF']);
    $allowed_pages = ['pos.php', 'clients.php', 'index.php'];
    
    // Check if user is admin
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