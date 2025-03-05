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
                        <img src="assets/images/logo-sm.svg" alt="" height="24"> <span class="logo-txt">CMS</span>
                    </span>
                </a>

                <a href="index.php" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="assets/images/logo-sm.svg" alt="" height="24">
                    </span>
                    <span class="logo-lg">
                        <img src="assets/images/logo-sm.svg" alt="" height="24"> <span class="logo-txt">CMS</span>
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

            <div class="dropdown d-inline-block d-lg-none ms-2">
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

            <div class="dropdown d-none d-sm-inline-block">
            <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <?php if ($lang == 'en') { ?>
                        <img class="me-2" src="assets/images/flags/us.jpg" alt="Header Language" height="16"> 
                    <?php } ?>
                    <?php if ($lang == 'fr') { ?>
                        <img class="me-2" src="assets/images/flags/fr.jpg" alt="Header Language" height="16"> 
                    <?php } ?>

                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    

                    <!-- item-->
                    <a href="?lang=en" class="dropdown-item notify-item language">
                        <img src="assets/images/flags/us.jpg" alt="user-image" class="me-1" height="12"> <span class="align-middle"> English </span>
                    </a>
                    
                    <!-- item-->
                    <a href="?lang=fr" class="dropdown-item notify-item language">
                        <img src="assets/images/flags/fr.jpg" alt="user-image" class="me-1" height="12"> <span class="align-middle"> French </span>
                    </a>
                </div>
                <div class="dropdown d-none d-lg-inline-block ms-1">
    <button type="button" class="btn header-item" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i data-feather="grid" class="icon-lg"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <div class="p-2">
            <div class="row g-0">
                <div class="col">
                    <a class="dropdown-icon-item" href="chat.php">
                        <i class="fas fa-comments"></i>  <!-- Chat Icon -->
                        <span>Chat</span>
                    </a>
                </div>
                <div class="col">
                    <a class="dropdown-icon-item" href="report.php">
                        <i class="fas fa-file-alt"></i>  <!-- Report Icon -->
                        <span>Report</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


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
                <li class="menu-title" data-key="t-menu"><?php echo $language["Menu"]; ?></li>

                <li>
                    <a href="index.php">
                        <i data-feather="home"></i>
                        <span data-key="t-dashboard"><?php echo $language["Dashboard"]; ?></span>
                    </a>
                    <a href="clients.php">
                        <i data-feather="file-text"></i>
                        <span data-key="t-dashboard">Clients</span>
                    </a>
                    <a href="#">
                        <i data-feather="navigation"></i>
                        <span data-key="t-dashboard">Title 2</span>
                    </a>
                </li>

                

                

                <li class="menu-title mt-2" data-key="t-components"><?php echo $language["Elements"]; ?></li>

                <li>
                <a href="packages.php">
    <i data-feather="box"></i>
    <span data-key="t-components">Package</span>
</a>


                    

                
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
 <!-- Feather Icons CDN -->
<script src="https://unpkg.com/feather-icons"></script>
<script>
    feather.replace();
</script>