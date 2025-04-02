<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'layouts/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    die("Unauthorized access");
}

// Get requested format
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';

// Validate format
if (!in_array($format, ['csv', 'pdf', 'excel'])) {
    die("Invalid export format");
}

// Fetch product data
try {
    $query = "SELECT 
        p.product_id,
        p.name,
        p.description,
        p.price,
        p.quantity_in_stock,
        p.price * p.quantity_in_stock AS total_value,
        p.created_at
    FROM 
        products p
    ORDER BY 
        p.name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Set filename
$timestamp = date('Y-m-d_H-i-s');
$filename = "inventory_export_{$timestamp}";

// Export based on format
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'ID',
        'Product Name',
        'Description',
        'Price ($)',
        'Stock Quantity',
        'Total Value ($)',
        'Created Date'
    ]);
    
    // Add product data
    foreach ($products as $product) {
        fputcsv($output, [
            $product['product_id'],
            $product['name'],
            $product['description'],
            number_format($product['price'], 2),
            $product['quantity_in_stock'],
            number_format($product['total_value'], 2),
            date('Y-m-d H:i:s', strtotime($product['created_at']))
        ]);
    }
    
    fclose($output);
    exit;
} elseif ($format === 'pdf') {
    // Check if TCPDF is available
    if (!file_exists('libraries/tcpdf/tcpdf.php')) {
        // If TCPDF is not available, create a basic PDF with raw PHP
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        
        // Create a basic PDF using PHP's PDF creation functions
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Inventory Report - ' . date('Y-m-d'));
        
        // Add table headers
        $pdf->Ln(15);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(10, 7, 'ID', 1);
        $pdf->Cell(40, 7, 'Product Name', 1);
        $pdf->Cell(40, 7, 'Price ($)', 1);
        $pdf->Cell(30, 7, 'Quantity', 1);
        $pdf->Cell(40, 7, 'Total Value ($)', 1);
        
        // Add product data
        $pdf->SetFont('Arial', '', 12);
        foreach ($products as $product) {
            $pdf->Ln();
            $pdf->Cell(10, 6, $product['product_id'], 1);
            $pdf->Cell(40, 6, substr($product['name'], 0, 20), 1);
            $pdf->Cell(40, 6, number_format($product['price'], 2), 1);
            $pdf->Cell(30, 6, $product['quantity_in_stock'], 1);
            $pdf->Cell(40, 6, number_format($product['total_value'], 2), 1);
        }
        
        $pdf->Output('D', $filename . '.pdf');
        exit;
    } else {
        // If TCPDF is available, use it for better formatting
        require_once('libraries/tcpdf/tcpdf.php');
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Gym Management System');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Inventory Report');
        $pdf->SetSubject('Inventory Report');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'Inventory Report', 'Generated on ' . date('Y-m-d H:i:s'));
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Create the table content
        $html = '<h1>Inventory Report</h1>';
        $html .= '<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color: #f5f5f5; font-weight: bold;">
                    <th width="8%">ID</th>
                    <th width="22%">Product Name</th>
                    <th width="25%">Description</th>
                    <th width="15%">Price ($)</th>
                    <th width="15%">Quantity</th>
                    <th width="15%">Total Value ($)</th>
                </tr>
            </thead>
            <tbody>';
            
        $total_inventory_value = 0;
        
        foreach ($products as $product) {
            $total_inventory_value += $product['total_value'];
            
            $html .= '<tr' . ($product['quantity_in_stock'] <= 0 ? ' style="background-color: #ffcccc;"' : ($product['quantity_in_stock'] <= 10 ? ' style="background-color: #fff3cd;"' : '')) . '>
                <td>' . $product['product_id'] . '</td>
                <td>' . htmlspecialchars($product['name']) . '</td>
                <td>' . htmlspecialchars(substr($product['description'], 0, 100)) . '</td>
                <td align="right">' . number_format($product['price'], 2) . '</td>
                <td align="right">' . $product['quantity_in_stock'] . '</td>
                <td align="right">' . number_format($product['total_value'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            <tfoot>
                <tr style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="5" align="right">Total Inventory Value:</td>
                    <td align="right">$' . number_format($total_inventory_value, 2) . '</td>
                </tr>
            </tfoot>
        </table>';
        
        $html .= '<p style="font-size: 10px;">* Red rows indicate out of stock items, yellow rows indicate low stock items (10 or fewer in stock).</p>';
        
        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    }
} elseif ($format === 'excel') {
    // Check if PhpSpreadsheet is available
    if (!file_exists('vendor/autoload.php')) {
        // If PhpSpreadsheet is not available, fallback to CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            'ID',
            'Product Name',
            'Description',
            'Price ($)',
            'Stock Quantity',
            'Total Value ($)',
            'Created Date'
        ]);
        
        // Add product data
        foreach ($products as $product) {
            fputcsv($output, [
                $product['product_id'],
                $product['name'],
                $product['description'],
                number_format($product['price'], 2),
                $product['quantity_in_stock'],
                number_format($product['total_value'], 2),
                date('Y-m-d H:i:s', strtotime($product['created_at']))
            ]);
        }
        
        fclose($output);
        exit;
    } else {
        // If PhpSpreadsheet is available, use it
        require 'vendor/autoload.php';
        
        use PhpOffice\PhpSpreadsheet\Spreadsheet;
        use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
        use PhpOffice\PhpSpreadsheet\Style\Fill;
        use PhpOffice\PhpSpreadsheet\Style\Border;
        use PhpOffice\PhpSpreadsheet\Style\Alignment;
        
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Gym Management System')
            ->setLastModifiedBy('Admin')
            ->setTitle('Inventory Report')
            ->setSubject('Inventory Report')
            ->setDescription('Inventory Report generated on ' . date('Y-m-d H:i:s'));
        
        // Add header
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Inventory Report - Generated on ' . date('Y-m-d H:i:s'));
        $sheet->mergeCells('A1:G1');
        
        // Style the header
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Add column headers
        $sheet->setCellValue('A3', 'ID');
        $sheet->setCellValue('B3', 'Product Name');
        $sheet->setCellValue('C3', 'Description');
        $sheet->setCellValue('D3', 'Price ($)');
        $sheet->setCellValue('E3', 'Stock Quantity');
        $sheet->setCellValue('F3', 'Total Value ($)');
        $sheet->setCellValue('G3', 'Created Date');
        
        // Style the column headers
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
        $sheet->getStyle('A3:G3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Add product data
        $row = 4;
        $total_value = 0;
        
        foreach ($products as $product) {
            $total_value += $product['total_value'];
            
            $sheet->setCellValue('A' . $row, $product['product_id']);
            $sheet->setCellValue('B' . $row, $product['name']);
            $sheet->setCellValue('C' . $row, $product['description']);
            $sheet->setCellValue('D' . $row, $product['price']);
            $sheet->setCellValue('E' . $row, $product['quantity_in_stock']);
            $sheet->setCellValue('F' . $row, $product['total_value']);
            $sheet->setCellValue('G' . $row, date('Y-m-d H:i:s', strtotime($product['created_at'])));
            
            // Format cells
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('0.00');
            
            // Color low stock and out of stock items
            if ($product['quantity_in_stock'] <= 0) {
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCCCC');
            } elseif ($product['quantity_in_stock'] <= 10) {
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
            }
            
            $row++;
        }
        
        // Add total row
        $sheet->setCellValue('E' . $row, 'Total Inventory Value:');
        $sheet->setCellValue('F' . $row, $total_value);
        
        // Style the total row
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('0.00');
        
        // Add legend
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Legend:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Red: Out of stock items');
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCCCC');
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Yellow: Low stock items (10 or fewer)');
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
        
        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Save to output
        $writer->save('php://output');
        exit;
    }
} 