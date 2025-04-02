<?php
/**
 * Helper functions for the gym management system
 */

/**
 * Check if the user has access to multiple branches
 *
 * @param PDO $pdo The database connection
 * @param int $user_id The user ID to check
 * @return bool True if the user has access to multiple branches
 */
if (!function_exists('hasMultipleBranches')) {
    function hasMultipleBranches($pdo, $user_id) {
        try {
            $query = "SELECT COUNT(*) FROM user_branches WHERE user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            $count = $stmt->fetchColumn();
            
            return $count > 1;
        } catch (Exception $e) {
            // If there's an error, assume no branches
            return false;
        }
    }
}

/**
 * Get all branches a user has access to
 *
 * @param PDO $pdo The database connection
 * @param int $user_id The user ID
 * @return array List of branches the user has access to
 */
if (!function_exists('getUserBranches')) {
    function getUserBranches($pdo, $user_id) {
        try {
            $query = "SELECT b.* FROM branches b
                      JOIN user_branches ub ON b.id = ub.branch_id
                      WHERE ub.user_id = :user_id
                      ORDER BY b.id ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If there's an error, return empty array
            return [];
        }
    }
}

/**
 * Check if the current user has a specific permission
 *
 * @param PDO $pdo The database connection
 * @param int $user_id The user ID
 * @param string $permission The permission name to check
 * @return bool True if the user has the permission
 */
function hasPermission($pdo, $user_id, $permission) {
    try {
        // First check if user is admin
        $admin_query = "SELECT isadmin FROM users WHERE id = :user_id";
        $admin_stmt = $pdo->prepare($admin_query);
        $admin_stmt->execute(['user_id' => $user_id]);
        $is_admin = $admin_stmt->fetchColumn();
        
        // Admins have all permissions
        if ($is_admin == 1) {
            return true;
        }
        
        // Add "can_" prefix if not already there
        if (strpos($permission, 'can_') !== 0) {
            $permission = 'can_' . $permission;
        }
        
        // Check specific permission
        $query = "SELECT $permission FROM user_permissions WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $has_permission = $stmt->fetchColumn();
        
        return $has_permission == 1;
    } catch (Exception $e) {
        // If there's an error, assume no permission
        return false;
    }
}

/**
 * Get translation for a key
 * 
 * @param string $key The translation key
 * @param string $default Default text if translation is not found
 * @return string The translated text
 */
function getTranslation($key, $default = '') {
    global $language;
    
    if (isset($language[$key])) {
        return $language[$key];
    }
    
    return $default ?: $key;
} 