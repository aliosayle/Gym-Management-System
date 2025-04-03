<?php
session_start();
include '../layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    die(json_encode(['error' => 'User not logged in']));
}

// Get DataTables parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 25;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$order_column = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
$order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

// Get filter parameters
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$branch_id = isset($_POST['branch_id']) ? $_POST['branch_id'] : 
             (isset($_SESSION['selected_branch_id']) ? $_SESSION['selected_branch_id'] : 1);

try {
    // Build the WHERE clause
    $where_clause = "WHERE s.sale_date BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)";
    $params = [':start_date' => $start_date, ':end_date' => $end_date];
    
    // Add branch filter
    $where_clause .= " AND s.branch_id = :branch_id";
    $params[':branch_id'] = $branch_id;
    
    if (!empty($payment_method)) {
        $where_clause .= " AND s.payment_method = :payment_method";
        $params[':payment_method'] = $payment_method;
    }
    
    if (!empty($search)) {
        $where_clause .= " AND (s.sale_id LIKE :search OR s.customer_name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Get total records count
    $count_query = "SELECT COUNT(*) as total FROM sales s $where_clause";
    $stmt = $pdo->prepare($count_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Define column names for ordering
    $columns = ['s.sale_id', 's.sale_date', 's.customer_name', 'items', 's.total_amount'];
    $order_by = $columns[$order_column] . ' ' . $order_dir;
    
    // Get filtered records
    $query = "SELECT s.*, 
              GROUP_CONCAT(CONCAT(p.description, ' x', si.quantity) SEPARATOR ', ') as items
              FROM sales s
              LEFT JOIN sale_items si ON s.sale_id = si.sale_id
              LEFT JOIN products p ON si.product_id = p.product_id
              $where_clause
              GROUP BY s.sale_id
              ORDER BY $order_by
              LIMIT :start, :length";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for DataTables
    $data = array_map(function($sale) {
        return [
            'sale_id' => $sale['sale_id'],
            'date' => date('Y-m-d H:i:s', strtotime($sale['sale_date'])),
            'customer' => $sale['customer_name'] ?? 'Walk-in Customer',
            'items' => $sale['items'] ?? 'No items',
            'total_amount' => number_format($sale['total_amount'], 2)
        ];
    }, $sales);
    
    // Return the response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ]);
    
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
} 