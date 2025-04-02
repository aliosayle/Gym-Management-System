<body>
<?php
// ... existing code ...

// Add this before the closing body tag
if (isset($_SESSION['needs_branch_selection'])) {
    // Check if current user is admin
    $current_user_query = "SELECT isadmin FROM users WHERE id = :id";
    $current_user_stmt = $pdo->prepare($current_user_query);
    $current_user_stmt->execute(['id' => $_SESSION['id']]);
    $current_user = $current_user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only show branch selection for non-admin users
    if (!isset($current_user['isadmin']) || $current_user['isadmin'] != 1) {
        // We should first redirect to the select_branch.php page which provides a better UI
        echo "<script>window.location.href = 'select_branch.php';</script>";
        exit;
    }
}
?>
</body>