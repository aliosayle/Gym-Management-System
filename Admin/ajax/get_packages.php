<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file for database connection
include '../layouts/session.php';
include '../layouts/config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if database connection exists
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Get branch ID from request, default to 1 if not provided
    $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 1;
    
    // If user is logged in, check what branches they have access to
    $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;
    $can_access_branch = false;
    
    if ($user_id) {
        // Check if user has access to this branch or is admin
        $access_check = "SELECT COUNT(*) FROM user_branches WHERE user_id = :user_id AND branch_id = :branch_id
                         UNION
                         SELECT COUNT(*) FROM users WHERE id = :user_id AND isadmin = 1";
        $access_stmt = $pdo->prepare($access_check);
        $access_stmt->execute([
            'user_id' => $user_id,
            'branch_id' => $branch_id
        ]);
        
        if ($access_stmt->fetchColumn() > 0) {
            $can_access_branch = true;
        }
    }
    
    // If user can't access this branch, use their default branch instead
    if (!$can_access_branch && $user_id) {
        $default_branch_query = "SELECT branch_id FROM user_branches WHERE user_id = :user_id LIMIT 1";
        $default_branch_stmt = $pdo->prepare($default_branch_query);
        $default_branch_stmt->execute(['user_id' => $user_id]);
        $default_branch = $default_branch_stmt->fetchColumn();
        
        if ($default_branch) {
            $branch_id = $default_branch;
        }
    }
    
    // Fetch packages for the selected branch
    $query = "SELECT id, name, price, number_of_days FROM packages 
              WHERE branch_id = :branch_id 
              ORDER BY price ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['branch_id' => $branch_id]);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return packages as JSON
    echo json_encode($packages);
} catch (PDOException $e) {
    // Return error
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching packages: ' . $e->getMessage()
    ]);
}
?> 