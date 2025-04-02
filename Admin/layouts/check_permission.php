<?php
/**
 * Permission checking helper functions for the Gym Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration if not already included
if (!isset($pdo)) {
    include_once 'config.php';
}

/**
 * Check if user has the specified permission
 * 
 * @param string $permission The permission to check
 * @param PDO $pdo The database connection
 * @return bool True if user has the permission, false otherwise
 */
function has_permission($permission, $pdo) {
    // If not logged in, no permissions
    if (!isset($_SESSION['id'])) {
        return false;
    }
    
    $user_id = $_SESSION['id'];
    
    // Admin users have all permissions
    $admin_query = "SELECT isadmin FROM users WHERE id = :id";
    $admin_stmt = $pdo->prepare($admin_query);
    $admin_stmt->execute(['id' => $user_id]);
    $user = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($user['isadmin']) && $user['isadmin'] == 1) {
        return true;
    }
    
    // Check specific permission for non-admin users
    $query = "SELECT $permission FROM user_permissions WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return isset($result[$permission]) && $result[$permission] == 1;
}

/**
 * Redirect user if they don't have the required permission
 * 
 * @param string $permission The permission to check
 * @param PDO $pdo The database connection
 * @param string $redirect_url The URL to redirect to if permission check fails
 * @return void
 */
function require_permission($permission, $pdo, $redirect_url = 'index.php') {
    if (!has_permission($permission, $pdo)) {
        $_SESSION['error_message'] = "You don't have permission to access this page.";
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Check if user has access to the specified branch
 * 
 * @param int $branch_id The branch ID to check
 * @param PDO $pdo The database connection
 * @return bool True if user has access to the branch, false otherwise
 */
function has_branch_access($branch_id, $pdo) {
    // If not logged in, no access
    if (!isset($_SESSION['id'])) {
        return false;
    }
    
    $user_id = $_SESSION['id'];
    
    // Admin users have access to all branches
    $admin_query = "SELECT isadmin FROM users WHERE id = :id";
    $admin_stmt = $pdo->prepare($admin_query);
    $admin_stmt->execute(['id' => $user_id]);
    $user = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($user['isadmin']) && $user['isadmin'] == 1) {
        return true;
    }
    
    // Check branch access for non-admin users
    $query = "SELECT COUNT(*) FROM user_branches WHERE user_id = :user_id AND branch_id = :branch_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id, 'branch_id' => $branch_id]);
    $count = $stmt->fetchColumn();
    
    return $count > 0;
}

/**
 * Get all branches that the user has access to
 * 
 * @param PDO $pdo The database connection
 * @return array Array of branch IDs the user has access to
 */
function get_accessible_branches($pdo) {
    // If not logged in, no branches
    if (!isset($_SESSION['id'])) {
        return [];
    }
    
    $user_id = $_SESSION['id'];
    
    // Admin users have access to all branches
    $admin_query = "SELECT isadmin FROM users WHERE id = :id";
    $admin_stmt = $pdo->prepare($admin_query);
    $admin_stmt->execute(['id' => $user_id]);
    $user = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($user['isadmin']) && $user['isadmin'] == 1) {
        $all_branches_query = "SELECT id FROM branches";
        $all_branches_stmt = $pdo->prepare($all_branches_query);
        $all_branches_stmt->execute();
        return $all_branches_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Get assigned branches for non-admin users
    $query = "SELECT branch_id FROM user_branches WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
} 