<?php
/**
 * Helper script to guide setting up the subscription cron job
 * This file provides instructions on how to set up the cron job
 * and also allows running the subscription update process manually
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include session and configuration files
include 'layouts/session.php';
include 'layouts/head-main.php';
include 'layouts/config.php';

// This feature has been disabled
$_SESSION['message'] = 'The subscription automation feature has been disabled.';
header('Location: index.php');
exit;

if (!$pdo) {
    die("Database connection error");
}

// Check if this is an admin user
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;
if (!$user_id) {
    header("Location: auth-login.php");
    exit;
}

$is_admin_query = "SELECT isadmin FROM users WHERE id = :user_id";
$admin_stmt = $pdo->prepare($is_admin_query);
$admin_stmt->execute(['user_id' => $user_id]);
$user_data = $admin_stmt->fetch(PDO::FETCH_ASSOC);
$is_admin = isset($user_data['isadmin']) && $user_data['isadmin'] == 1;

if (!$is_admin) {
    die("You do not have permission to access this page");
}

// Handle manual run
$result_message = "";
if (isset($_POST['run_now']) && $_POST['run_now'] == '1') {
    // Execute the cron script
    ob_start();
    include 'cron_update_subscriptions.php';
    $result = ob_get_clean();
    $result_message = "Subscription update process executed manually.<br><pre>" . htmlspecialchars($result) . "</pre>";
}

// Get cron command
$script_path = __DIR__ . '/cron_update_subscriptions.php';
$php_path = PHP_BINARY;
$cron_command = $php_path . ' ' . $script_path;

// Get server OS
$is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$cron_instructions = "";

if ($is_windows) {
    $cron_instructions = "
    <h4>Setting Up Scheduled Task on Windows:</h4>
    <ol>
        <li>Open Command Prompt as Administrator</li>
        <li>Run this command to create a scheduled task that runs daily at 1:00 AM:<br>
        <code>schtasks /create /tn \"Gym Subscription Update\" /tr \"$cron_command\" /sc DAILY /st 01:00</code></li>
        <li>To verify the task was created, run:<br>
        <code>schtasks /query /tn \"Gym Subscription Update\"</code></li>
    </ol>";
} else {
    $cron_instructions = "
    <h4>Setting Up Cron Job on Linux/Unix:</h4>
    <ol>
        <li>Open terminal and edit your crontab:<br>
        <code>crontab -e</code></li>
        <li>Add the following line to run the script daily at 1:00 AM:<br>
        <code>0 1 * * * $cron_command</code></li>
        <li>Save and exit the editor</li>
        <li>Verify your crontab with:<br>
        <code>crontab -l</code></li>
    </ol>";
}
?>

<head>
    <title>Subscription Cron Job Setup | Admin Dashboard</title>
    <?php include 'layouts/head.php'; ?>
    <?php include 'layouts/head-style.php'; ?>
</head>

<?php include 'layouts/body.php'; ?>

<!-- Begin page -->
<div id="layout-wrapper">
    <?php include 'layouts/menu.php'; ?>

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Subscription Cron Job Setup</h4>

                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Subscription Cron</li>
                                </ol>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Subscription Automatic Updates</h4>
                                <p class="card-title-desc">
                                    This system needs to run a daily check to expire outdated subscriptions and send renewal notifications.
                                    Follow the instructions below to set up automatic updates.
                                </p>

                                <?php if ($result_message): ?>
                                <div class="alert alert-info">
                                    <?php echo $result_message; ?>
                                </div>
                                <?php endif; ?>

                                <div class="mt-4">
                                    <h5>Cron Job Command</h5>
                                    <div class="bg-light p-3 mb-3">
                                        <code><?php echo htmlspecialchars($cron_command); ?></code>
                                    </div>

                                    <?php echo $cron_instructions; ?>

                                    <div class="mt-4">
                                        <h5>Manual Run</h5>
                                        <p>You can run the subscription update process manually by clicking the button below:</p>
                                        <form method="post">
                                            <input type="hidden" name="run_now" value="1">
                                            <button type="submit" class="btn btn-primary">Run Subscription Update Now</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        <?php include 'layouts/footer.php'; ?>
    </div>
    <!-- end main content-->
</div>
<!-- END layout-wrapper -->

<!-- Right Sidebar -->
<?php include 'layouts/right-sidebar.php'; ?>
<!-- /Right-bar -->

<!-- JAVASCRIPT -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script src="assets/js/app.js"></script>

</body>
</html> 