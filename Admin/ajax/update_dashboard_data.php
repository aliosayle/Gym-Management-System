<?php
// Update dashboard data
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../layouts/session.php';
include '../layouts/config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    // Update dashboard data
    
    // 1. Update any outdated subscription statuses
    $update_expired = "UPDATE clients 
                       SET subscription_status = 'expired' 
                       WHERE subscription_end_date < CURDATE() 
                       AND subscription_status = 'active'";
    $stmt = $pdo->prepare($update_expired);
    $stmt->execute();
    
    // 2. Calculate current stats for cache tables if they exist
    // This is a placeholder - you might want to create a dashboard_stats table 
    // to cache frequently accessed statistics
    
    // 3. Return success
    echo json_encode([
        'success' => true,
        'message' => 'Dashboard data updated',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating dashboard data: ' . $e->getMessage()
    ]);
}
?> 