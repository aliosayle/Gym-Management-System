<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();
include '../layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if branch_id is provided
if (!isset($_POST['branch_id']) || empty($_POST['branch_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Branch ID is required']);
    exit;
}

$branch_id = $_POST['branch_id'];
$user_id = $_SESSION['id'];

try {
    // Verify the branch exists and user has access to it
    $query = "SELECT b.id, b.name, c.name as company_name
              FROM branches b
              JOIN companies c ON b.company_id = c.id
              JOIN user_branches ub ON b.id = ub.branch_id
              WHERE b.id = :branch_id AND ub.user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'branch_id' => $branch_id,
        'user_id' => $user_id
    ]);
    
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($branch) {
        // Set branch information in session
        $_SESSION['selected_branch_id'] = $branch['id'];
        $_SESSION['selected_branch_name'] = $branch['name'];
        $_SESSION['selected_company_name'] = $branch['company_name'];
        
        echo json_encode(['status' => 'success', 'message' => 'Branch switched successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid branch selection']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 