<?php
// Include database configuration
require_once '../layouts/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authorized
if (!isset($_SESSION['id'])) {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Get days parameter (default to 30 days if not provided)
$days = isset($_POST['days']) ? intval($_POST['days']) : 30;

// Validate days parameter
if ($days <= 0 || $days > 365) {
    $days = 30; // Default to 30 days for invalid inputs
}

try {
    // Generate date range based on days
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime("-$days days"));
    
    // Prepare response arrays
    $labels = [];
    $revenue = [];
    $transactions = [];
    
    // For shorter time periods (30 days or less), show daily data
    if ($days <= 30) {
        // Get daily revenue data
        $query = "SELECT 
                    DATE_FORMAT(payment_date, '%d %b') as day_label,
                    DATE(payment_date) as payment_day,
                    COALESCE(SUM(amount), 0) as daily_revenue,
                    COUNT(*) as transaction_count
                  FROM payments 
                  WHERE payment_status = 'completed'
                  AND payment_date BETWEEN :start_date AND :end_date
                  GROUP BY payment_day
                  ORDER BY payment_day ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in any missing days
        $current = new DateTime($start_date);
        $last = new DateTime($end_date);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($current, $interval, $last->modify('+1 day'));
        
        // Create a map of existing data
        $dayData = [];
        foreach ($data as $row) {
            $dayData[$row['payment_day']] = $row;
        }
        
        // Generate complete dataset with all days
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $displayDate = $date->format('d M');
            
            $labels[] = $displayDate;
            
            if (isset($dayData[$dateStr])) {
                $revenue[] = (float)$dayData[$dateStr]['daily_revenue'];
                $transactions[] = (int)$dayData[$dateStr]['transaction_count'];
            } else {
                $revenue[] = 0;
                $transactions[] = 0;
            }
        }
    } 
    // For medium periods (31-90 days), show weekly data
    else if ($days <= 90) {
        // Get weekly revenue data
        $query = "SELECT 
                    YEARWEEK(payment_date, 1) as year_week,
                    MIN(DATE(payment_date)) as week_start,
                    MAX(DATE(payment_date)) as week_end,
                    COALESCE(SUM(amount), 0) as weekly_revenue,
                    COUNT(*) as transaction_count
                  FROM payments 
                  WHERE payment_status = 'completed'
                  AND payment_date BETWEEN :start_date AND :end_date
                  GROUP BY year_week
                  ORDER BY year_week ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($data as $row) {
            $weekLabel = date('d M', strtotime($row['week_start'])) . ' - ' . 
                         date('d M', strtotime($row['week_end']));
            
            $labels[] = $weekLabel;
            $revenue[] = (float)$row['weekly_revenue'];
            $transactions[] = (int)$row['transaction_count'];
        }
    }
    // For longer periods (91+ days), show monthly data
    else {
        // Get monthly revenue data
        $query = "SELECT 
                    DATE_FORMAT(payment_date, '%b %Y') as month_label,
                    CONCAT(YEAR(payment_date), '-', MONTH(payment_date)) as year_month,
                    COALESCE(SUM(amount), 0) as monthly_revenue,
                    COUNT(*) as transaction_count
                  FROM payments 
                  WHERE payment_status = 'completed'
                  AND payment_date BETWEEN :start_date AND :end_date
                  GROUP BY year_month
                  ORDER BY year_month ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($data as $row) {
            $labels[] = $row['month_label'];
            $revenue[] = (float)$row['monthly_revenue'];
            $transactions[] = (int)$row['transaction_count'];
        }
    }
    
    // Return JSON response
    echo json_encode([
        'labels' => $labels,
        'revenue' => $revenue,
        'transactions' => $transactions,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    
} catch (PDOException $e) {
    // Log error
    error_log("AJAX chart data error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
}
?> 