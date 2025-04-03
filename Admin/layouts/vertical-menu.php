<?php
//include 'layouts/config.php'; // Include the config file where $pdo is defined

// Include permission checking functions if not already included
if (!function_exists('has_permission')) {
    if (file_exists('layouts/check_permission.php')) {
        include_once 'layouts/check_permission.php';
    } elseif (file_exists('Admin/layouts/check_permission.php')) {
        include_once 'Admin/layouts/check_permission.php';
    } else {
        include_once __DIR__ . '/check_permission.php';
    }
}

// Fetch user permissions
$user_id = $_SESSION['id']; // Assuming user_id is stored in session
$permission_query = "SELECT isadmin FROM users WHERE id = :id";
$permission_stmt = $pdo->prepare($permission_query);
$permission_stmt->execute(['id' => $user_id]);
$permissions = $permission_stmt->fetch(PDO::FETCH_ASSOC);
$is_admin = $permissions['isadmin'];

// Get specific permissions for menu display
$can_view_dashboard = has_permission('can_view_dashboard', $pdo);
$can_manage_clients = has_permission('can_manage_clients', $pdo);
$can_manage_inventory = has_permission('can_manage_inventory', $pdo);
$can_manage_packages = has_permission('can_manage_packages', $pdo);
$can_manage_companies = has_permission('can_manage_companies', $pdo);
$can_manage_branches = has_permission('can_manage_branches', $pdo);
$can_manage_users = has_permission('can_manage_users', $pdo);
$can_use_pos = has_permission('can_use_pos', $pdo);
$can_view_reports = has_permission('can_view_reports', $pdo);
?>

<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="index.php" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="assets/images/logo-sm.svg" alt="" height="24">
                    </span>
                    <span class="logo-lg">
                        <img src="assets/images/logo-sm.svg" alt="" height="24"> <span class="logo-txt">GMS</span>
                    </span>
                </a>

                <a href="index.php" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="assets/images/logo-sm.svg" alt="" height="24">
                    </span>
                    <span class="logo-lg">
                        <img src="assets/images/logo-sm.svg" alt="" height="24"> <span class="logo-txt">GMS</span>
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <!-- App Search-->
            <!-- <form class="app-search d-none d-lg-block">
                <div class="position-relative">
                    <input type="text" class="form-control" placeholder="<?php echo $language["Search"]; ?>">
                    <button class="btn btn-primary" type="button"><i class="bx bx-search-alt align-middle"></i></button>
                </div>
            </form> -->
        </div>

        <div class="d-flex">

            <div class="dropdown d-none d-lg-none ms-2">
                <button type="button" class="btn header-item" id="page-header-search-dropdown"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i data-feather="search" class="icon-lg"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-search-dropdown">
        
                    <form class="p-3">
                        <div class="form-group m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="<?php echo $language["Search"]; ?>" aria-label="Search Result">

                                <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Branch Selector -->
            <?php
            // Check if user is logged in and has a branch selected
            if (isset($_SESSION['id'])) {
                // Get user's assigned branches
                $branches_query = "SELECT b.id, b.name, c.name as company_name 
                                  FROM user_branches ub
                                  JOIN branches b ON ub.branch_id = b.id
                                  JOIN companies c ON b.company_id = c.id
                                  WHERE ub.user_id = :user_id
                                  ORDER BY c.name, b.name";
                $branches_stmt = $pdo->prepare($branches_query);
                $branches_stmt->execute(['user_id' => $_SESSION['id']]);
                $user_branches = $branches_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Only show branch selector if user has branches
                if (count($user_branches) > 0) {
                    // Get current branch name
                    $current_branch_name = isset($_SESSION['selected_branch_name']) ? $_SESSION['selected_branch_name'] : 'Select Branch';
                    ?>
                    <div class="dropdown d-none d-sm-inline-block">
                        <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                            <i class="mdi mdi-office-building me-1"></i> <?php echo htmlspecialchars($current_branch_name); ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($user_branches as $branch): ?>
                                <a href="javascript:void(0);" 
                                   class="dropdown-item branch-item <?php echo (isset($_SESSION['selected_branch_id']) && $_SESSION['selected_branch_id'] == $branch['id']) ? 'active' : ''; ?>"
                                   data-branch-id="<?php echo $branch['id']; ?>"
                                   data-branch-name="<?php echo htmlspecialchars($branch['name']); ?>">
                                    <span class="align-middle"><?php echo htmlspecialchars($branch['company_name'] . ' - ' . $branch['name']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>

            <?php if ($can_use_pos): ?>
            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i data-feather="grid" class="icon-lg"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <div class="p-2">
                        <div class="row g-0">
                            <div class="col">
                                <a class="dropdown-icon-item" href="pos.php">
                                    <i class="fas fa-cash-register"></i>  <!-- POS Icon -->
                                    <span>POS</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            </div>



            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item bg-light-subtle border-start border-end" id="page-header-user-dropdown"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="assets/images/users/avatar-1.jpg"
                        alt="Header Avatar">
                    <span class="d-none d-xl-inline-block ms-1 fw-medium">                        <?php
                        // Check if the user is logged in and display their username
                        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
                            echo htmlspecialchars($_SESSION["username"]); // Display logged-in username
                        } else {
                            echo "Guest"; // Default name if no user is logged in
                        }
                        ?></span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="apps-contacts-profile.php"><i class="mdi mdi mdi-face-man font-size-16 align-middle me-1"></i> <?php echo $language["Profile"]; ?></a>
                    <a class="dropdown-item" href="auth-lock-screen.php"><i class="mdi mdi-lock font-size-16 align-middle me-1"></i> <?php echo $language["Lock_screen"]; ?> </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php"><i class="mdi mdi-logout font-size-16 align-middle me-1"></i> <?php echo $language["Logout"]; ?></a>
                </div>
            </div>

        </div>
    </div>
</header>


<!-- ========== Left Sidebar Start ========== -->
<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <?php
                // Define fallback language array if not defined
                if (!isset($language)) {
                    $language = [
                        "Menu" => "Menu",
                        "Dashboard" => "Dashboard",
                        "Elements" => "Elements"
                    ];
                }
                ?>
                
                <li class="menu-title" data-key="t-menu"><?php echo $language["Menu"]; ?></li>

                <!-- Clients menu - available to everyone with appropriate permissions -->
                <?php if ($can_manage_clients): ?>
                <li>
                    <a href="clients.php">
                        <i data-feather="file-text"></i>
                        <span data-key="t-dashboard">Clients</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Dashboard menu - available to users with dashboard permission -->
                <?php if ($can_view_dashboard): ?>
                <li>
                    <a href="index.php">
                        <i data-feather="home"></i>
                        <span data-key="t-dashboard"><?php echo $language["Dashboard"]; ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Products menu - available to users with inventory management permission -->
                <?php if ($can_manage_inventory): ?>
                <li>
                    <a href="products.php">
                        <i data-feather="navigation"></i>
                        <span data-key="t-dashboard">Products</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($can_use_pos): ?>
                <li>
                    <a href="pos.php">
                        <i data-feather="shopping-cart"></i>
                        <span data-key="t-dashboard">POS</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($can_view_reports): ?>
                <li>
                    <a href="sales_report.php">
                        <i data-feather="bar-chart-2"></i>
                        <span data-key="t-dashboard">Sales Reports</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="menu-title mt-2" data-key="t-components"><?php echo $language["Elements"]; ?></li>

                <!-- Packages menu - available to users with package management permission -->
                <?php if ($can_manage_packages): ?>
                <li>
                    <a href="packages.php">
                        <i data-feather="box"></i>
                        <span data-key="t-components">Packages</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Companies & Branches - available to users with company/branch management permission -->
                <?php if ($can_manage_companies || $can_manage_branches): ?>
                <li>
                    <a href="companies.php">
                        <i data-feather="briefcase"></i>
                        <span data-key="t-components">Companies & Branches</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- User Management - available to users with user management permission -->
                <?php if ($can_manage_users): ?>
                <li>
                    <a href="users.php">
                        <i data-feather="users"></i>
                        <span data-key="t-components">User Management</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Admin-only menus -->
                <?php if ($is_admin): ?>
                <li>
                    <a href="setup_subscription_cron.php">
                        <i data-feather="clock"></i>
                        <span data-key="t-components">Subscription Automation</span>
                    </a>
                </li>
                <li>
                    <a href="setup_permissions.php">
                        <i data-feather="shield"></i>
                        <span data-key="t-components">Setup Permissions</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
 <!-- Feather Icons CDN -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    feather.replace();
</script>

<!-- Branch Selector Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle branch selection
    var branchItems = document.querySelectorAll('.branch-item');
    branchItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            var branchId = this.getAttribute('data-branch-id');
            var branchName = this.getAttribute('data-branch-name');
            
            // Show loading indication
            var originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Switching...';
            
            // Use AJAX to switch branch without page reload
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/select_branch.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.status === 'success') {
                                // Refresh the page to apply the new branch
                                window.location.reload();
                            } else {
                                alert('Error: ' + response.message);
                                // Reset the button text
                                item.innerHTML = originalText;
                            }
                        } catch (e) {
                            alert('Error parsing response: ' + e.message);
                            // Reset the button text
                            item.innerHTML = originalText;
                        }
                    } else {
                        alert('Error: Server returned status ' + xhr.status);
                        // Reset the button text
                        item.innerHTML = originalText;
                    }
                }
            };
            xhr.send('branch_id=' + encodeURIComponent(branchId));
        });
    });
});
</script>