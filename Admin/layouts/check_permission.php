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
 * Check if user has a specific permission
 * @param string $permission_name The name of the permission to check
 * @param object $pdo PDO database connection object
 * @return bool True if user has permission, false otherwise
 */
function has_permission($permission_name, $pdo) {
    if (!isset($_SESSION['id'])) {
        return false;
    }

    $user_id = $_SESSION['id'];
    
    // First check if user has a specific permission setting in user_permissions table
    $permission_query = "SELECT permission_value FROM user_permissions 
                        WHERE user_id = :user_id AND permission_name = :permission_name";
    $permission_stmt = $pdo->prepare($permission_query);
    $permission_stmt->execute([
        'user_id' => $user_id,
        'permission_name' => $permission_name
    ]);
    
    // If user has a specific permission setting, return that value (1 or 0)
    if ($permission_stmt->rowCount() > 0) {
        $permission = $permission_stmt->fetch(PDO::FETCH_ASSOC);
        return (bool)$permission['permission_value'];
    }
    
    // If no specific permission found, check if user is admin
    $user_query = "SELECT isadmin FROM users WHERE id = :id";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute(['id' => $user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If user is admin, they have permission by default
    return (bool)$user['isadmin'];
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
 * Check if user has access to a specific branch
 * @param int $branch_id The ID of the branch to check
 * @param object $pdo PDO database connection object
 * @return bool True if user has access to branch, false otherwise
 */
function has_branch_access($branch_id, $pdo) {
    if (!isset($_SESSION['id'])) {
        return false;
    }

    $user_id = $_SESSION['id'];
    
    // First check specific branch assignment in user_branches table
    $branch_query = "SELECT * FROM user_branches WHERE user_id = :user_id AND branch_id = :branch_id";
    $branch_stmt = $pdo->prepare($branch_query);
    $branch_stmt->execute([
        'user_id' => $user_id,
        'branch_id' => $branch_id
    ]);
    
    // If user is specifically assigned to this branch, grant access
    if ($branch_stmt->rowCount() > 0) {
        return true;
    }
    
    // If no specific branch assignment found, check if user is admin
    $user_query = "SELECT isadmin FROM users WHERE id = :id";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute(['id' => $user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If user is admin, they have access to all branches by default
    return (bool)$user['isadmin'];
}

/**
 * Get a list of branches accessible by the current user
 * @param object $pdo PDO database connection object
 * @return array List of accessible branches
 */
function get_accessible_branches($pdo) {
    if (!isset($_SESSION['id'])) {
        return [];
    }

    $user_id = $_SESSION['id'];
    
    // First check specific branch assignments in user_branches table
    $branch_query = "SELECT b.* FROM user_branches ub 
                     JOIN branches b ON ub.branch_id = b.id
                     WHERE ub.user_id = :user_id
                     ORDER BY b.name";
    $branch_stmt = $pdo->prepare($branch_query);
    $branch_stmt->execute(['user_id' => $user_id]);
    $branches = $branch_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If user has specific branch assignments, return those
    if (count($branches) > 0) {
        return $branches;
    }
    
    // If no specific branch assignments, check if user is admin
    $user_query = "SELECT isadmin FROM users WHERE id = :id";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute(['id' => $user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If user is admin, they have access to all branches
    if ((bool)$user['isadmin']) {
        $all_branches_query = "SELECT * FROM branches ORDER BY name";
        $all_branches_stmt = $pdo->prepare($all_branches_query);
        $all_branches_stmt->execute();
        return $all_branches_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Default to empty array if no branches accessible
    return [];
} 