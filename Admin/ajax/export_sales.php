<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
require_once '../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Check if required parameters are provided
if (!isset($_POST['format']) || empty($_POST['format'])) {
    http_response_code(400);
    exit('Format is required');
}

$format = $_POST['format'];
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

// Add one day to end date for SQL BETWEEN
$end_date_sql = date('Y-m-d', strtotime($end_date . ' +1 day'));

try {
    // Fetch sales data
    $query = "SELECT s.*, GROUP_CONCAT(si.product_id, ':', si.quantity, ':', si.price SEPARATOR '|') as items 
             FROM sales s 
             LEFT JOIN sale_items si ON s.sale_id = si.sale_id 
             WHERE s.sale_date BETWEEN ? AND ? 
             GROUP BY s.sale_id 
             ORDER BY s.sale_date DESC";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date_sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sales = array();
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    
    // Export based on the requested format
    if ($format === 'csv') {
        exportToCsv($sales);
    } elseif ($format === 'pdf') {
        exportToPdf($sales);
    } else {
        http_response_code(400);
        exit('Invalid format');
    }
    
} catch (Exception $e) {
    error_log("Error exporting sales data: " . $e->getMessage());
    http_response_code(500);
    exit('An error occurred while exporting sales data');
}

// Function to export to CSV
function exportToCsv($sales) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, array('Sale ID', 'Date', 'Time', 'Items', 'Total Amount'));
    
    // Add data rows
    foreach ($sales as $sale) {
        $items_count = 0;
        if (!empty($sale['items'])) {
            $items_array = explode('|', $sale['items']);
            $items_count = count($items_array);
        }
        
        $date = date('Y-m-d', strtotime($sale['sale_date']));
        $time = date('H:i:s', strtotime($sale['sale_date']));
        
        fputcsv($output, array(
            $sale['sale_id'],
            $date,
            $time,
            $items_count . ' item(s)',
            number_format($sale['total_amount'], 2)
        ));
    }
    
    // Close output stream
    fclose($output);
    exit;
}

// Function to export to PDF
function exportToPdf($sales) {
    // Check if TCPDF is available, if not, return a message
    if (!class_exists('TCPDF')) {
        // Create a simple PDF using PHP's basic output functions
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.pdf"');
        
        // Generate a simple text PDF
        $pdf_content = "Sales Report - " . date('Y-m-d') . "\n\n";
        $pdf_content .= "Sale ID | Date | Items | Total Amount\n";
        $pdf_content .= "--------------------------------------------\n";
        
        foreach ($sales as $sale) {
            $items_count = 0;
            if (!empty($sale['items'])) {
                $items_array = explode('|', $sale['items']);
                $items_count = count($items_array);
            }
            
            $date = date('Y-m-d H:i:s', strtotime($sale['sale_date']));
            
            $pdf_content .= $sale['sale_id'] . " | " . $date . " | " . $items_count . " item(s) | $" . number_format($sale['total_amount'], 2) . "\n";
        }
        
        echo $pdf_content;
        exit;
    }
    
    // If TCPDF is available, use it to generate a proper PDF
    require_once('../libs/tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Gym Management System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Sales Report');
    $pdf->SetSubject('Sales Report');
    $pdf->SetKeywords('Sales, Report, Gym');
    
    // Set default header and footer
    $pdf->SetHeaderData('', 0, 'Sales Report', 'Generated on ' . date('Y-m-d H:i:s'));
    $pdf->setHeaderFont(Array('helvetica', '', 10));
    $pdf->setFooterFont(Array('helvetica', '', 8));
    
    // Set margins
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Table header
    $html = '<table border="1" cellpadding="5">
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <th>Sale ID</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                </tr>';
    
    // Table data
    foreach ($sales as $sale) {
        $items_count = 0;
        if (!empty($sale['items'])) {
            $items_array = explode('|', $sale['items']);
            $items_count = count($items_array);
        }
        
        $date = date('Y-m-d H:i:s', strtotime($sale['sale_date']));
        
        $html .= '<tr>
                    <td>' . $sale['sale_id'] . '</td>
                    <td>' . $date . '</td>
                    <td>' . $items_count . ' item(s)</td>
                    <td>$' . number_format($sale['total_amount'], 2) . '</td>
                  </tr>';
    }
    
    $html .= '</table>';
    
    // Print table
    $pdf->writeHTML($html, true, false, false, false, '');
    
    // Close and output PDF
    $pdf->Output('sales_report_' . date('Y-m-d') . '.pdf', 'D');
    exit;
}
?> 