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

// Check if format is specified
if (!isset($_GET['format'])) {
    die('Error: No export format specified');
}

$format = $_GET['format'];
$validFormats = ['csv', 'pdf', 'excel'];

if (!in_array($format, $validFormats)) {
    die('Error: Invalid export format');
}

// Get filter type if set
$filterType = isset($_GET['filter']) ? $_GET['filter'] : null;

// Prepare query based on filter
if ($filterType == 'active') {
    $query = "SELECT c.client_id, c.name, c.phone_number, c.subscription_status, 
             c.subscription_end_date, p.name as package_name, p.price as package_price  
             FROM clients c 
             LEFT JOIN packages p ON c.package_id = p.id 
             WHERE c.subscription_status = 'active'";
} elseif ($filterType == 'ending_soon') {
    $query = "SELECT c.client_id, c.name, c.phone_number, c.subscription_status, 
             c.subscription_end_date, p.name as package_name, p.price as package_price  
             FROM clients c 
             LEFT JOIN packages p ON c.package_id = p.id 
             WHERE c.subscription_end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
             AND c.subscription_status = 'active'";
} elseif ($filterType == 'expired') {
    $query = "SELECT c.client_id, c.name, c.phone_number, c.subscription_status, 
             c.subscription_end_date, p.name as package_name, p.price as package_price  
             FROM clients c 
             LEFT JOIN packages p ON c.package_id = p.id 
             WHERE c.subscription_status = 'expired'";
} elseif ($filterType == 'pending_payments') {
    $query = "SELECT c.client_id, c.name, c.phone_number, c.subscription_status, 
             c.subscription_end_date, p.name as package_name, p.price as package_price,
             pay.payment_id, pay.amount as pending_amount  
             FROM clients c 
             LEFT JOIN packages p ON c.package_id = p.id 
             JOIN payments pay ON c.client_id = pay.client_id 
             WHERE pay.payment_status = 'Pending'";
} else {
    $query = "SELECT c.client_id, c.name, c.phone_number, c.subscription_status, 
             c.subscription_end_date, p.name as package_name, p.price as package_price  
             FROM clients c 
             LEFT JOIN packages p ON c.package_id = p.id";
}

try {
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($clients)) {
        die('No data to export');
    }
    
    // Generate filename
    $timestamp = date('Y-m-d_H-i-s');
    $filterText = $filterType ? "_" . $filterType : "";
    $filename = "clients_export" . $filterText . "_" . $timestamp;
    
    // Export based on format
    if ($format == 'csv') {
        exportToCsv($clients, $filename);
    } elseif ($format == 'pdf') {
        exportToPdf($clients, $filename);
    } elseif ($format == 'excel') {
        exportToExcel($clients, $filename);
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

/**
 * Export data to CSV
 */
function exportToCsv($clients, $filename) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'Client ID', 
        'Name', 
        'Phone Number', 
        'Status', 
        'Subscription End Date',
        'Package',
        'Package Price',
        isset($clients[0]['pending_amount']) ? 'Pending Amount' : null
    ]);
    
    // Add data rows
    foreach ($clients as $client) {
        $row = [
            $client['client_id'],
            $client['name'],
            $client['phone_number'],
            $client['subscription_status'],
            $client['subscription_end_date'],
            $client['package_name'],
            $client['package_price']
        ];
        
        if (isset($client['pending_amount'])) {
            $row[] = $client['pending_amount'];
        }
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Export data to PDF
 */
function exportToPdf($clients, $filename) {
    // Check if TCPDF library is available
    if (!file_exists('assets/libs/tcpdf/tcpdf.php')) {
        // If TCPDF is not available, create a simple text version
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        
        $pdf = "Clients Export\n\n";
        $pdf .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($clients as $client) {
            $pdf .= "Client ID: " . $client['client_id'] . "\n";
            $pdf .= "Name: " . $client['name'] . "\n";
            $pdf .= "Phone: " . $client['phone_number'] . "\n";
            $pdf .= "Status: " . $client['subscription_status'] . "\n";
            $pdf .= "End Date: " . $client['subscription_end_date'] . "\n";
            $pdf .= "Package: " . $client['package_name'] . "\n";
            $pdf .= "Price: $" . $client['package_price'] . "\n";
            
            if (isset($client['pending_amount'])) {
                $pdf .= "Pending Amount: $" . $client['pending_amount'] . "\n";
            }
            
            $pdf .= "\n---------------------------\n\n";
        }
        
        echo $pdf;
        exit;
    }
    
    // Use TCPDF library
    require_once('assets/libs/tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Gym Management System');
    $pdf->SetAuthor('Administrator');
    $pdf->SetTitle('Clients Export');
    $pdf->SetSubject('Clients Data');
    
    // Set default header and footer data
    $pdf->setHeaderData('', 0, 'Clients Export', 'Generated on: ' . date('Y-m-d H:i:s'), array(0,64,255), array(0,64,128));
    $pdf->setFooterData(array(0,64,0), array(0,64,128));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Create table header
    $html = '<table border="1" cellpadding="4">
        <tr style="background-color:#3281FF;color:#ffffff;font-weight:bold;">
            <th>Name</th>
            <th>Phone Number</th>
            <th>Status</th>
            <th>End Date</th>
            <th>Package</th>
            <th>Price</th>';
    
    if (isset($clients[0]['pending_amount'])) {
        $html .= '<th>Pending</th>';
    }
    
    $html .= '</tr>';
    
    // Add data rows
    foreach ($clients as $client) {
        $status_color = '';
        switch ($client['subscription_status']) {
            case 'active': $status_color = 'green'; break;
            case 'expired': $status_color = 'red'; break;
            default: $status_color = 'gray';
        }
        
        $html .= '<tr>
            <td>' . htmlspecialchars($client['name']) . '</td>
            <td>' . htmlspecialchars($client['phone_number']) . '</td>
            <td><span style="color:' . $status_color . '">' . htmlspecialchars($client['subscription_status']) . '</span></td>
            <td>' . htmlspecialchars($client['subscription_end_date']) . '</td>
            <td>' . htmlspecialchars($client['package_name']) . '</td>
            <td>$' . htmlspecialchars($client['package_price']) . '</td>';
            
        if (isset($client['pending_amount'])) {
            $html .= '<td>$' . htmlspecialchars($client['pending_amount']) . '</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Output HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output($filename . '.pdf', 'D');
    exit;
}

/**
 * Export data to Excel
 */
function exportToExcel($clients, $filename) {
    // Check if PHPSpreadsheet is available
    if (!file_exists('assets/libs/phpspreadsheet/vendor/autoload.php')) {
        // If PHPSpreadsheet is not available, offer CSV instead
        exportToCsv($clients, $filename);
        exit;
    }
    
    require 'assets/libs/phpspreadsheet/vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = [
        'A1' => 'Client ID',
        'B1' => 'Name',
        'C1' => 'Phone Number',
        'D1' => 'Status',
        'E1' => 'Subscription End Date',
        'F1' => 'Package',
        'G1' => 'Package Price'
    ];
    
    if (isset($clients[0]['pending_amount'])) {
        $headers['H1'] = 'Pending Amount';
    }
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Add data rows
    $row = 2;
    foreach ($clients as $client) {
        $sheet->setCellValue('A' . $row, $client['client_id']);
        $sheet->setCellValue('B' . $row, $client['name']);
        $sheet->setCellValue('C' . $row, $client['phone_number']);
        $sheet->setCellValue('D' . $row, $client['subscription_status']);
        $sheet->setCellValue('E' . $row, $client['subscription_end_date']);
        $sheet->setCellValue('F' . $row, $client['package_name']);
        $sheet->setCellValue('G' . $row, $client['package_price']);
        
        if (isset($client['pending_amount'])) {
            $sheet->setCellValue('H' . $row, $client['pending_amount']);
        }
        
        $row++;
    }
    
    // Style the header row
    $styleArray = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '3281FF'],
        ],
    ];
    
    $lastColumn = isset($client['pending_amount']) ? 'H1' : 'G1';
    $sheet->getStyle('A1:' . $lastColumn)->applyFromArray($styleArray);
    
    // Auto-size columns
    foreach (range('A', $lastColumn[0]) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // Set content type and headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Save to output
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?> 