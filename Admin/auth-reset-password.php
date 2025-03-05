<?php
session_start();
// Include config file
require_once "layouts/config.php";

// Define variables and initialize with empty values
$new_password = $confirm_password = "";
$password_err = $confirm_password_err = $reset_code_err = "";

// Get the email and reset code from the previous page via POST
if (isset($_POST["useremail"])) {
    $email = $_POST["useremail"];
} else {
    die("Invalid access.");
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["new_password"])) {
    // Validate reset code

    // Validate password
    if (empty(trim($_POST["new_password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["new_password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm your password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if ($new_password != $confirm_password) {
            $confirm_password_err = "Passwords did not match.";
        }
    }

    // Check if there are no errors before updating the password
    if (empty($password_err) && empty($confirm_password_err) && empty($reset_code_err)) {
        // Prepare an update statement to change the password
        $sql = "UPDATE users SET password = ? WHERE useremail = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind the new password and email to the prepared statement
            mysqli_stmt_bind_param($stmt, "ss", $param_password, $email);

            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Password updated successfully, redirect to login page
                unset($_SESSION['reset_code']);  // Clear the reset code from session
                header("location: auth-login.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Close the connection
    mysqli_close($link);
}
?>

<?php include 'layouts/head-main.php'; ?>

<head>
    <title>Reset Password | Minia</title>
    <?php include 'layouts/head.php'; ?>
    <?php include 'layouts/head-style.php'; ?>
</head>

<?php include 'layouts/body.php'; ?>

<div class="auth-page">
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-xxl-3 col-lg-4 col-md-5">
                <div class="auth-full-page-content d-flex p-sm-5 p-4">
                    <div class="w-100">
                        <div class="d-flex flex-column h-100">
                            <div class="mb-4 mb-md-5 text-center">
                                <a href="index.php" class="d-block auth-logo">
                                    <img src="assets/images/logo-sm.svg" alt="" height="28"> <span class="logo-txt">Minia</span>
                                </a>
                            </div>
                            <div class="auth-content my-auto">
                                <div class="text-center">
                                    <h5 class="mb-0">Reset Password</h5>
                                    <p class="text-muted mt-2">Please enter your new password.</p>
                                </div>
                                <form class="needs-validation mt-4 pt-2" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <!-- Hidden fields for email and reset code -->
                                    <input type="hidden" name="useremail" value="<?php echo htmlspecialchars($email); ?>">
                                    <input type="hidden" name="resetCode" value="<?php echo htmlspecialchars($reset_code); ?>">

                                    <!-- New password input -->
                                    <div class="mb-3 <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" placeholder="Enter new password" required name="new_password" value="<?php echo $new_password; ?>">
                                        <span class="text-danger"><?php echo $password_err; ?></span>
                                    </div>

                                    <!-- Confirm password input -->
                                    <div class="mb-3 <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" placeholder="Confirm new password" required name="confirm_password" value="<?php echo $confirm_password; ?>">
                                        <span class="text-danger"><?php echo $confirm_password_err; ?></span>
                                    </div>

                                    <!-- Reset code error message -->
                                    <?php if (!empty($reset_code_err)) : ?>
                                        <div class="alert alert-danger"><?php echo $reset_code_err; ?></div>
                                    <?php endif; ?>

                                    <!-- Submit button -->
                                    <div class="mb-3">
                                        <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">Reset Password</button>
                                    </div>
                                </form>

                                <div class="mt-4 text-center">
                                    <p class="text-muted mb-0">Remember your password? <a href="login.php" class="text-primary fw-semibold">Login</a></p>
                                </div>
                            </div>
                            <div class="mt-4 mt-md-5 text-center">
                                <p class="mb-0">© <script>document.write(new Date().getFullYear())</script> Minia . Crafted with <i class="mdi mdi-heart text-danger"></i> by Themesbrand</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end col -->
        </div>
        <!-- end row -->
    </div>
    <!-- end container fluid -->
</div>

<!-- JAVASCRIPT -->
<?php include 'layouts/vendor-scripts.php'; ?>

<script src="assets/js/pages/validation.init.js"></script>

</body>
</html>