<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'layouts/config.php';

header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['status' => 'error', 'message' => 'Connection not established: ' . $pdo->errorInfo()]);
    exit;
}

if (isset($_GET['id'])) {
    $client_id = $_GET['id'];
    $package_id = null;

    // Fetch package_id
    $package_id_sql = "SELECT package_id FROM clients WHERE client_id = :client_id";
    $stmt = $pdo->prepare($package_id_sql);
    $stmt->execute(['client_id' => $client_id]);
    $package_id = $stmt->fetchColumn();

    if ($package_id === false) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid client ID']);
        exit;
    }

    $payment_id = uniqid();
    $amount = null;

    // Get the package price
    $package_price_sql = "SELECT price FROM packages WHERE id = :package_id";
    $stmt = $pdo->prepare($package_price_sql);
    $stmt->execute(['package_id' => $package_id]);
    $amount = $stmt->fetchColumn();

    if ($amount === false) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid package ID']);
        exit;
    }

    $payment_method = 'cash';
    $payment_status = 'Pending';

    // Insert new payment
    $insert_payment_sql = "INSERT INTO payments (payment_id, client_id, amount, payment_method, package_id, payment_status) VALUES (:payment_id, :client_id, :amount, :payment_method, :package_id, :payment_status)";
    $stmt = $pdo->prepare($insert_payment_sql);
    if ($stmt->execute([
        'payment_id' => $payment_id,
        'client_id' => $client_id,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'package_id' => $package_id,
        'payment_status' => $payment_status
    ])) {
        echo json_encode(['status' => 'success', 'payment_id' => $payment_id, 'amount' => $amount]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to execute insert statement: ' . implode(", ", $stmt->errorInfo())]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
}
?>