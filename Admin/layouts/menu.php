<!-- if you select vertical Menu then comment Horizontal Menu and uncomment this-->
<?php
// Include helper functions
if (file_exists('layouts/helpers.php')) {
    include_once 'layouts/helpers.php';
} elseif (file_exists('Admin/layouts/helpers.php')) {
    include_once 'Admin/layouts/helpers.php';
}

include 'layouts/vertical-menu.php';

?>

<!-- if you select Horizontal Menu then comment vertical Menu and uncomment this-->
<?php
    
// include 'layouts/horizontal-menu.php';

?>

<?php
// Add this after the user dropdown menu
if (isset($_SESSION['id'])) {
    // Fallback function definition if include failed
    if (!function_exists('hasMultipleBranches')) {
        function hasMultipleBranches($pdo, $user_id) {
            try {
                $query = "SELECT COUNT(*) FROM user_branches WHERE user_id = :user_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['user_id' => $user_id]);
                $count = $stmt->fetchColumn();
                return $count > 1;
            } catch (Exception $e) {
                return false;
            }
        }
    }
    
    // Fallback function definition if include failed
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
                return [];
            }
        }
    }
    
    // Check if user has multiple branches
    $has_multiple_branches = false;
    try {
        $has_multiple_branches = hasMultipleBranches($pdo, $_SESSION['id']);
    } catch (Exception $e) {
        // Silently fail
    }
    
    if ($has_multiple_branches) {
        $branches = getUserBranches($pdo, $_SESSION['id']);
        ?>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="branchDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="mdi mdi-office-building me-1"></i>
                <span><?php echo isset($_SESSION['selected_branch_name']) ? htmlspecialchars($_SESSION['selected_branch_name']) : 'Select Branch'; ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="branchDropdown">
                <?php foreach ($branches as $branch): ?>
                    <li>
                        <a class="dropdown-item <?php echo (isset($_SESSION['selected_branch_id']) && $branch['id'] == $_SESSION['selected_branch_id']) ? 'active' : ''; ?>" 
                           href="#" 
                           data-branch-id="<?php echo $branch['id']; ?>">
                            <?php echo htmlspecialchars($branch['name'] ?? 'Branch ' . $branch['id']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php
    }
}
?>

<script>
$(document).ready(function() {
    // Handle branch switching
    $('.dropdown-item[data-branch-id]').click(function(e) {
        e.preventDefault();
        const branchId = $(this).data('branch-id');
        
        $.ajax({
            url: 'ajax/select_branch.php',
            type: 'POST',
            data: { branch_id: branchId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert('Error switching branch. Please try again.');
                }
            },
            error: function() {
                alert('Error switching branch. Please try again.');
            }
        });
    });
});
</script>