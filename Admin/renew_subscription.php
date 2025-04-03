<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include 'layouts/session.php';
include 'layouts/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check if client ID is provided
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Client ID is required'
    ]);
    exit;
}

$client_id = $_GET['id'];
$package_id = $_GET['package'] ?? null;
$custom_start_date = $_GET['start_date'] ?? null;
$branch_id = $_GET['branch_id'] ?? null;

if (empty($package_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Package ID is required'
    ]);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Verify package is from the correct branch
    $package_query = "SELECT * FROM packages WHERE id = :package_id";
    if ($branch_id) {
        $package_query .= " AND branch_id = :branch_id";
    }
    $package_stmt = $pdo->prepare($package_query);
    
    $params = ['package_id' => $package_id];
    if ($branch_id) {
        $params['branch_id'] = $branch_id;
    }
    
    $package_stmt->execute($params);
    $package = $package_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        throw new Exception('Package not found or not available for this branch');
    }
    
    // Calculate total amount and expiry date
    $amount = $package['price'];
    $days = $package['number_of_days'];
    
    // Get client current subscription end date
    $client_query = "SELECT * FROM clients WHERE client_id = :client_id";
    $client_stmt = $pdo->prepare($client_query);
    $client_stmt->execute(['client_id' => $client_id]);
    $client = $client_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        throw new Exception('Client not found');
    }
    
    // Use the branch_id from the parameter or from the client record
    $payment_branch_id = $branch_id ?: $client['branch_id'];
    
    // Determine start date for the new subscription
    if (!empty($custom_start_date)) {
        // Use custom start date if provided
        $start_date = new DateTime($custom_start_date);
        if (!$start_date) {
            throw new Exception('Invalid start date format. Please use YYYY-MM-DD');
        }
    } else {
        // Otherwise use logic based on current subscription
        $current_date = new DateTime();
        
        // If current subscription is active and end date is in the future, extend from there
        if ($client['subscription_status'] == 'active' && 
            !empty($client['subscription_end_date']) && 
            new DateTime($client['subscription_end_date']) > $current_date) {
            $start_date = new DateTime($client['subscription_end_date']);
        } else {
            // Otherwise start from today
            $start_date = $current_date;
        }
    }
    
    // Add days to calculate end date
    $end_date = clone $start_date;
    $end_date->add(new DateInterval("P{$days}D"));
    $new_end_date = $end_date->format('Y-m-d');
    $formatted_start_date = $start_date->format('Y-m-d');
    
    // Generate payment ID
    $payment_id = uniqid();
    
    // Update client subscription
    $update_client_sql = "UPDATE clients SET 
                         subscription_status = 'active', 
                         subscription_end_date = :end_date,
                         package_id = :package_id
                         WHERE client_id = :client_id";
    $update_client_stmt = $pdo->prepare($update_client_sql);
    $update_client_stmt->execute([
        'end_date' => $new_end_date,
        'package_id' => $package_id,
        'client_id' => $client_id
    ]);
    
    // Insert new payment record
    $insert_payment_sql = "INSERT INTO payments 
                         (payment_id, client_id, amount, payment_method, package_id, payment_status, payment_date, branch_id) 
                         VALUES 
                         (:payment_id, :client_id, :amount, 'cash', :package_id, 'Pending', :payment_date, :branch_id)";
    $insert_payment_stmt = $pdo->prepare($insert_payment_sql);
    $insert_payment_stmt->execute([
        'payment_id' => $payment_id,
        'client_id' => $client_id,
        'amount' => $amount,
        'package_id' => $package_id,
        'payment_date' => $formatted_start_date,
        'branch_id' => $payment_branch_id
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Update dashboard data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'ajax/update_dashboard_data.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Subscription renewed successfully',
        'client_id' => $client_id,
        'payment_id' => $payment_id,
        'amount' => $amount,
        'start_date' => $formatted_start_date,
        'new_end_date' => $new_end_date
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>