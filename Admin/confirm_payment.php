<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'layouts/session.php';
include 'layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Check if payment ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = 'Error: Payment ID is required';
    header('Location: clients.php');
    exit;
}

$payment_id = $_GET['id'];

// Determine where to redirect after processing
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'clients.php';

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get payment details
    $payment_query = "SELECT p.*, c.name as client_name, pk.name as package_name
                     FROM payments p
                     JOIN clients c ON p.client_id = c.client_id
                     JOIN packages pk ON p.package_id = pk.id
                     WHERE p.payment_id = :payment_id";
    $payment_stmt = $pdo->prepare($payment_query);
    $payment_stmt->execute(['payment_id' => $payment_id]);
    $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    if ($payment['payment_status'] !== 'Pending') {
        throw new Exception('Payment is already ' . $payment['payment_status']);
    }
    
    // Update payment status
    $update_query = "UPDATE payments 
                    SET payment_status = 'Completed' 
                    WHERE payment_id = :payment_id";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->execute(['payment_id' => $payment_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Update dashboard data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'ajax/update_dashboard_data.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    
    // Set success message
    $_SESSION['message'] = 'Payment confirmed successfully for ' . $payment['client_name'] . 
                           ' - ' . $payment['package_name'] . ' ($' . $payment['amount'] . ')';
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Set error message
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
}

// Redirect to the appropriate page
header('Location: ' . $redirect);
exit;
?>
