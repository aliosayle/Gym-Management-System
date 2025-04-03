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

// Get branch_id from session
$branch_id = isset($_SESSION['selected_branch_id']) ? $_SESSION['selected_branch_id'] : 1;

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
    $revenueSubscriptions = [];
    $revenuePOS = [];
    $transactions = [];
    
    // For shorter time periods (30 days or less), show daily data
    if ($days <= 30) {
        // Create a full range of dates
        $current = new DateTime($start_date);
        $last = new DateTime($end_date);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($current, $interval, $last->modify('+1 day'));
        
        // Generate the complete set of labels
        $all_dates = [];
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $displayDate = $date->format('d M');
            $all_dates[$dateStr] = [
                'label' => $displayDate,
                'subscription_revenue' => 0,
                'pos_revenue' => 0,
                'transaction_count' => 0
            ];
        }
        
        // Get subscription revenue data
        $query = "SELECT 
                    DATE(payment_date) as payment_day,
                    COALESCE(SUM(amount), 0) as daily_revenue,
                    COUNT(*) as transaction_count
                  FROM payments 
                  WHERE payment_status = 'completed'
                  AND payment_date BETWEEN :start_date AND :end_date
                  AND branch_id = :branch_id
                  GROUP BY payment_day
                  ORDER BY payment_day ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'branch_id' => $branch_id
        ]);
        
        $subscriptionData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge subscription data
        foreach ($subscriptionData as $row) {
            $dateStr = $row['payment_day'];
            if (isset($all_dates[$dateStr])) {
                $all_dates[$dateStr]['subscription_revenue'] = (float)$row['daily_revenue'];
                $all_dates[$dateStr]['transaction_count'] += (int)$row['transaction_count'];
            }
        }
        
        // Get POS sales data
        $query = "SELECT 
                    DATE(sale_date) as sale_day,
                    COALESCE(SUM(total_amount), 0) as daily_revenue,
                    COUNT(*) as transaction_count
                  FROM sales 
                  WHERE sale_date BETWEEN :start_date AND :end_date
                  AND branch_id = :branch_id
                  GROUP BY sale_day
                  ORDER BY sale_day ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'branch_id' => $branch_id
        ]);
        
        $posData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge POS data
        foreach ($posData as $row) {
            $dateStr = $row['sale_day'];
            if (isset($all_dates[$dateStr])) {
                $all_dates[$dateStr]['pos_revenue'] = (float)$row['daily_revenue'];
                $all_dates[$dateStr]['transaction_count'] += (int)$row['transaction_count'];
            }
        }
        
        // Convert to arrays for response
        foreach ($all_dates as $date_data) {
            $labels[] = $date_data['label'];
            $revenueSubscriptions[] = $date_data['subscription_revenue'];
            $revenuePOS[] = $date_data['pos_revenue'];
            $transactions[] = $date_data['transaction_count'];
        }
    } 
    // For medium periods (31-90 days), show weekly data
    else if ($days <= 90) {
        // Get subscription weekly revenue data
        $query = "SELECT 
                    YEARWEEK(payment_date, 1) as year_week,
                    MIN(DATE(payment_date)) as week_start,
                    COALESCE(SUM(amount), 0) as weekly_revenue,
                    COUNT(*) as transaction_count
                  FROM payments 
                  WHERE payment_status = 'completed'
                  AND payment_date BETWEEN :start_date AND :end_date
                  AND branch_id = :branch_id
                  GROUP BY year_week
                  ORDER BY year_week ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'branch_id' => $branch_id
        ]);
        
        $subscriptionData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create week map for subscription data
        $weekData = [];
        foreach ($subscriptionData as $row) {
            $weekData[$row['year_week']] = [
                'week_start' => $row['week_start'],
                'subscription_revenue' => (float)$row['weekly_revenue'],
                'pos_revenue' => 0,
                'transaction_count' => (int)$row['transaction_count']
            ];
        }
        
        // Get POS weekly revenue data
        $query = "SELECT 
                    YEARWEEK(sale_date, 1) as year_week,
                    MIN(DATE(sale_date)) as week_start,
                    COALESCE(SUM(total_amount), 0) as weekly_revenue,
                    COUNT(*) as transaction_count
                  FROM sales 
                  WHERE sale_date BETWEEN :start_date AND :end_date
                  AND branch_id = :branch_id
                  GROUP BY year_week
                  ORDER BY year_week ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'branch_id' => $branch_id
        ]);
        
        $posData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge POS data into week map
        foreach ($posData as $row) {
            $yearWeek = $row['year_week'];
            if (isset($weekData[$yearWeek])) {
                $weekData[$yearWeek]['pos_revenue'] = (float)$row['weekly_revenue'];
                $weekData[$yearWeek]['transaction_count'] += (int)$row['transaction_count'];
            } else {
                $weekData[$yearWeek] = [
                    'week_start' => $row['week_start'],
                    'subscription_revenue' => 0,
                    'pos_revenue' => (float)$row['weekly_revenue'],
                    'transaction_count' => (int)$row['transaction_count']
                ];
            }
        }
        
        // Sort weeks by start date
        ksort($weekData);
        
        // Generate response data
        foreach ($weekData as $week) {
            $weekStart = new DateTime($week['week_start']);
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+6 days');
            
            $weekLabel = $weekStart->format('d M') . ' - ' . $weekEnd->format('d M');
            
            $labels[] = $weekLabel;
            $revenueSubscriptions[] = $week['subscription_revenue'];
            $revenuePOS[] = $week['pos_revenue'];
            $transactions[] = $week['transaction_count'];
        }
    }
    // For longer periods (91+ days), show monthly data
    else {
        // Get subscription monthly revenue data
        $query = "SELECT 
                    DATE_FORMAT(payment_date, '%b %Y') as month_label,
                    CONCAT(YEAR(payment_date), '-', MONTH(payment_date)) as year_month,
                    COALESCE(SUM(amount), 0) as monthly_revenue,
                    COUNT(*) as transaction_count
                  FROM payments 
                  WHERE payment_status = 'completed'
                  AND payment_date BETWEEN :start_date AND :end_date
                  AND branch_id = :branch_id
                  GROUP BY year_month
                  ORDER BY year_month ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'branch_id' => $branch_id
        ]);
        
        $subscriptionData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create month map for subscription data
        $monthData = [];
        foreach ($subscriptionData as $row) {
            $monthData[$row['year_month']] = [
                'month_label' => $row['month_label'],
                'subscription_revenue' => (float)$row['monthly_revenue'],
                'pos_revenue' => 0,
                'transaction_count' => (int)$row['transaction_count']
            ];
        }
        
        // Get POS monthly revenue data
        $query = "SELECT 
                    DATE_FORMAT(sale_date, '%b %Y') as month_label,
                    CONCAT(YEAR(sale_date), '-', MONTH(sale_date)) as year_month,
                    COALESCE(SUM(total_amount), 0) as monthly_revenue,
                    COUNT(*) as transaction_count
                  FROM sales 
                  WHERE sale_date BETWEEN :start_date AND :end_date
                  AND branch_id = :branch_id
                  GROUP BY year_month
                  ORDER BY year_month ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'branch_id' => $branch_id
        ]);
        
        $posData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge POS data into month map
        foreach ($posData as $row) {
            $yearMonth = $row['year_month'];
            if (isset($monthData[$yearMonth])) {
                $monthData[$yearMonth]['pos_revenue'] = (float)$row['monthly_revenue'];
                $monthData[$yearMonth]['transaction_count'] += (int)$row['transaction_count'];
            } else {
                $monthData[$yearMonth] = [
                    'month_label' => $row['month_label'],
                    'subscription_revenue' => 0,
                    'pos_revenue' => (float)$row['monthly_revenue'],
                    'transaction_count' => (int)$row['transaction_count']
                ];
            }
        }
        
        // Sort months by year and month
        ksort($monthData);
        
        // Generate response data
        foreach ($monthData as $month) {
            $labels[] = $month['month_label'];
            $revenueSubscriptions[] = $month['subscription_revenue'];
            $revenuePOS[] = $month['pos_revenue'];
            $transactions[] = $month['transaction_count'];
        }
    }
    
    // Return JSON response
    echo json_encode([
        'labels' => $labels,
        'revenueSubscriptions' => $revenueSubscriptions,
        'revenuePOS' => $revenuePOS,
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