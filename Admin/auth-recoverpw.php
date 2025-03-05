<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the function to send WhatsApp messages
include './whatsapp_helper.php'; // Adjust the path to where the function is defined

// Include config file
require_once "layouts/config.php";

$useremail_err = $msg = "";

if (isset($_POST['submit'])) {
    $useremail = $_POST['useremail'];

    // Using prepared statement to avoid SQL injection
    $stmt = $link->prepare("SELECT * FROM users WHERE useremail = ?");
    $stmt->bind_param("s", $useremail);
    $stmt->execute();
    $result = $stmt->get_result();
    $userdata = $result->fetch_assoc();

    if ($userdata) {
        $username = $userdata['username'];
        $phoneNumber = $userdata['phone_number']; // Assuming phone_number is stored in the users table

        // Generate a random reset code
        $reset_code = rand(100000, 999999);

        // Store the reset code in session
        $_SESSION['reset_code'] = $reset_code;

        // Message to be sent via WhatsApp
        $messageBody = "Hi, $username. Your reset code is: $reset_code";

        // Call the sendWhatsAppMessage function
        $result = sendWhatsAppMessage($phoneNumber, $messageBody);

        if ($result['success']) {
            $msg = "We have sent you a WhatsApp message with the reset code!";
            // Display form to input reset code
            echo '<form action="" method="POST">
                    <div class="mb-3">
                        <label for="resetCode" class="form-label">Enter the Reset Code</label>
                        <input type="text" class="form-control" id="resetCode" name="resetCode" required>
                    </div>
                    <input type="hidden" name="useremail" value="' . htmlspecialchars($useremail) . '">
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </form>';
        } else {
            $useremail_err = "Failed to send WhatsApp message. HTTP Code: " . $result['http_code'];
        }
    } else {
        $useremail_err = "No Email Found";
    }

    $stmt->close();
}
?>

<?php
// Check if user has entered the reset code
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resetCode']) && isset($_POST['useremail'])) {
    $input_code = $_POST['resetCode'];
    $useremail = $_POST['useremail'];

    // Validate the reset code
    if ($input_code == $_SESSION['reset_code']) {
        // Code is correct, redirect to password reset page
        echo '<form id="resetForm" action="auth-reset-password.php" method="POST">
                <input type="hidden" name="useremail" value="' . htmlspecialchars($useremail) . '">
              </form>';
        echo '<script>
                document.getElementById("resetForm").submit();
              </script>';
    } else {
        echo '<div class="alert alert-danger">Invalid reset code. Please try again.</div>';
    }
}
?>

<?php include 'layouts/head-main.php'; ?>

<head>
    <title>Recover Password | Minia - Admin & Dashboard Template</title>
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
                                    <p class="text-muted mt-2">Reset Password.</p>
                                </div>
                                <?php if ($msg) { ?>
                                    <div class="alert alert-success text-center my-4" role="alert">
                                        <?php echo $msg; ?>
                                    </div>
                                <?php } ?>

                                <form class="mt-4" action="<?php echo htmlentities($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="mb-3 <?php echo (!empty($useremail_err)) ? 'has-error' : ''; ?>">
                                        <label class="form-label">Email</label>
                                        <input type="text" class="form-control" id="email" name="useremail" placeholder="Enter email">
                                        <span class="text-danger"><?php echo $useremail_err; ?></span>
                                    </div>
                                    <div class="mb-3 mt-4">
                                        <button class="btn btn-primary w-100 waves-effect waves-light" type='submit' name='submit' value='Submit'>Reset</button>
                                    </div>
                                </form>

                                <div class="mt-5 text-center">
                                    <p class="text-muted mb-0">Remember It ? <a href="auth-login.php" class="text-primary fw-semibold"> Sign In </a> </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-9 col-lg-8 col-md-7">
                <div class="auth-bg pt-md-5 p-4 d-flex">
                    <div class="bg-overlay bg-primary"></div>
                    <ul class="bg-bubbles">
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                    </ul>
                    <div class="row justify-content-center align-items-center">
                        <div class="col-xl-7">
                            <div class="p-0 p-sm-4 px-xl-0">
                                <div id="reviewcarouselIndicators" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-indicators carousel-indicators-rounded justify-content-start ms-0 mb-0">
                                        <button type="button" data-bs-target="#reviewcarouselIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                        <button type="button" data-bs-target="#reviewcarouselIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                        <button type="button" data-bs-target="#reviewcarouselIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
                                    </div>
                                    <div class="carousel-inner">
                                        <!-- Carousel content here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/vendor-scripts.php'; ?>

</body>

</html>