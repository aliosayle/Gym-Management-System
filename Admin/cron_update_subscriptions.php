<?php
/**
 * Cron job script to check and update subscription statuses
 * This script should be run daily via system cron job
 * 
 * It performs two main tasks:
 * 1. Sets expired subscriptions to 'expired' status
 * 2. Sends notifications for subscriptions expiring soon
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine if the script is being executed from CLI or web server
$is_cli = (php_sapi_name() == 'cli');

// Include configuration
$base_path = dirname(__FILE__);
require_once($base_path . '/layouts/config.php');
require_once($base_path . '/whatsapp_helper.php');

// Log function
function log_message($message) {
    global $is_cli;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    
    if ($is_cli) {
        echo $log_message;
    }
    
    // Log to file
    file_put_contents($base_path . '/logs/subscription_cron.log', $log_message, FILE_APPEND);
}

// Create logs directory if it doesn't exist
if (!is_dir($base_path . '/logs')) {
    mkdir($base_path . '/logs', 0755, true);
}

log_message("Starting subscription status update...");

if (!$pdo) {
    log_message("Error: Database connection failed.");
    exit(1);
}

try {
    // 1. Update expired subscriptions
    $update_expired_sql = "UPDATE clients 
                          SET subscription_status = 'expired' 
                          WHERE subscription_end_date < CURDATE() 
                          AND subscription_status = 'active'";
    $stmt = $pdo->prepare($update_expired_sql);
    $stmt->execute();
    $expired_count = $stmt->rowCount();
    log_message("Updated $expired_count subscriptions to expired status.");
    
    // 2. Identify clients with subscriptions ending soon (next 7 days)
    $ending_soon_sql = "SELECT client_id, name, phone_number, subscription_end_date 
                       FROM clients 
                       WHERE subscription_status = 'active' 
                       AND subscription_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                       AND (last_notification_date IS NULL OR last_notification_date < CURDATE())";
    $stmt = $pdo->prepare($ending_soon_sql);
    $stmt->execute();
    $ending_soon_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Send notifications to clients with ending subscriptions
    $notification_count = 0;
    foreach ($ending_soon_clients as $client) {
        $days_left = (new DateTime($client['subscription_end_date']))->diff(new DateTime())->days;
        $message = "Dear " . $client['name'] . ", your gym subscription is ending in " . $days_left . " days on " . $client['subscription_end_date'] . ". Please renew it soon to continue enjoying our services.";
        
        // Skip clients without phone numbers
        if (empty($client['phone_number'])) {
            log_message("Client ID {$client['client_id']} has no phone number, skipping notification.");
            continue;
        }
        
        // Send WhatsApp notification if function exists
        if (function_exists('sendWhatsAppMessage')) {
            $result = sendWhatsAppMessage($client['phone_number'], $message);
            $success = isset($result['success']) ? $result['success'] : false;
            
            if ($success) {
                // Update last notification date
                $update_notification_sql = "UPDATE clients 
                                           SET last_notification_date = CURDATE() 
                                           WHERE client_id = :client_id";
                $stmt = $pdo->prepare($update_notification_sql);
                $stmt->execute(['client_id' => $client['client_id']]);
                $notification_count++;
                
                log_message("Notification sent to {$client['name']} ({$client['phone_number']})");
            } else {
                log_message("Failed to send notification to {$client['name']} ({$client['phone_number']})");
            }
        } else {
            log_message("WhatsApp sending function not available");
        }
    }
    
    log_message("Sent notifications to $notification_count clients with ending subscriptions.");
    log_message("Subscription status update completed successfully.");
    
} catch (PDOException $e) {
    log_message("Error: " . $e->getMessage());
    exit(1);
}
?> 