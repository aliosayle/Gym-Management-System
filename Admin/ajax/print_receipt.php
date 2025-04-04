<?php
session_start();
include '../layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    die('User not logged in');
}

// Check if sale_id is provided
if (!isset($_GET['sale_id'])) {
    die('Sale ID not provided');
}

$sale_id = $_GET['sale_id'];

// Get branch_id from request or session
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : 
            (isset($_SESSION['selected_branch_id']) ? $_SESSION['selected_branch_id'] : 1);

try {
    // Get branch and company information
    $branchQuery = "SELECT b.name as branch_name, c.name as company_name, 
                   c.id_nat, c.vat_number, c.rccm, c.nif, c.address, c.phone
                   FROM branches b
                   LEFT JOIN companies c ON b.company_id = c.id
                   WHERE b.id = :branch_id";
    $branchStmt = $pdo->prepare($branchQuery);
    $branchStmt->execute(['branch_id' => $branch_id]);
    $branchResult = $branchStmt->fetch(PDO::FETCH_ASSOC);
    $branch_name = $branchResult ? $branchResult['branch_name'] : 'Unknown Branch';
    $company_name = $branchResult ? $branchResult['company_name'] : 'Gym Management System';

    // Get sale details
    $query = "SELECT s.*, 
              GROUP_CONCAT(CONCAT(p.description, ' x', si.quantity) SEPARATOR ', ') as items_list
              FROM sales s
              LEFT JOIN sale_items si ON s.sale_id = si.sale_id
              LEFT JOIN products p ON si.product_id = p.product_id
              WHERE s.sale_id = :sale_id AND s.branch_id = :branch_id
              GROUP BY s.sale_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'sale_id' => $sale_id, 
        'branch_id' => $branch_id
    ]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        die('Sale not found');
    }

    // Get sale items
    $query = "SELECT si.*, 
              p.description, 
              p.name as product_name, 
              si.price * si.quantity as item_total 
              FROM sale_items si
              LEFT JOIN products p ON si.product_id = p.product_id
              WHERE si.sale_id = :sale_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['sale_id' => $sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output HTML for printing
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Receipt #<?php echo $sale_id; ?></title>
        <style>
            @media print {
                @page {
                    margin: 0;
                    size: 80mm auto; /* Set width to standard receipt width and auto height */
                }
                html, body {
                    margin: 0;
                    padding: 0;
                    width: 100%;
                    page-break-after: avoid;
                }
                body {
                    -webkit-print-color-adjust: exact;
                }
                /* Hide browser headers and footers */
                header, footer, nav, aside {
                    display: none !important;
                }
                /* Force printer to stop after content */
                .receipt-footer:after {
                    content: "";
                    display: block;
                    page-break-after: always;
                }
            }
            
            body {
                font-family: 'Courier New', monospace;
                line-height: 1.4;
                margin: 0;
                padding: 10px;
                font-size: 12px;
                width: 100%;
                max-width: 300px; /* Limit maximum width */
                box-sizing: border-box;
                overflow: visible;
                font-weight: normal;
            }
            .receipt-container {
                width: 100%;
                /* No fixed height to allow content to determine length */
            }
            .receipt-header {
                text-align: center;
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 1px dashed #000;
            }
            .receipt-header h4 {
                font-size: 14px;
                margin: 4px 0;
                font-weight: bold;
            }
            .receipt-header p {
                margin: 3px 0;
                font-size: 12px;
            }
            .receipt-header .row-info span {
                font-size: 9px;
                display: inline-block;
            }
            .receipt-items {
                margin-bottom: 8px;
            }
            .receipt-total {
                border-top: 1px dashed #000;
                padding-top: 8px;
                font-weight: bold;
                font-size: 13px;
            }
            .receipt-footer {
                text-align: center;
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed #000;
                margin-bottom: 0;
                padding-bottom: 0;
                font-size: 11px;
            }
            .row-info {
                width: 100%;
                clear: both;
                overflow: hidden;
                margin-bottom: 3px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed; /* Add fixed table layout */
            }
            th, td {
                padding: 3px;
                text-align: left;
                vertical-align: top; /* Align to top */
            }
            th {
                text-align: right;
                font-size: 12px;
                font-weight: bold;
            }
            td {
                font-size: 12px;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .item-description {
                word-wrap: break-word;
                max-width: 160px;
                font-weight: normal;
                white-space: normal; /* Allow text to wrap */
            }
            
            /* Column widths */
            table th:nth-child(1), table td:nth-child(1) { width: 50%; } /* Description */
            table th:nth-child(2), table td:nth-child(2) { width: 10%; } /* Qty */
            table th:nth-child(3), table td:nth-child(3) { width: 20%; } /* Price */
            table th:nth-child(4), table td:nth-child(4) { width: 20%; } /* Total */
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <div class="receipt-header">
                <h4><?php echo htmlspecialchars($company_name); ?></h4>
                <p><?php echo htmlspecialchars($branch_name); ?></p>
                <?php if (!empty($branchResult['address'])): ?>
                <p><?php echo htmlspecialchars($branchResult['address']); ?></p>
                <?php endif; ?>
                
                <div class="row-info">
                    <?php if (!empty($branchResult['phone'])): ?>
                    <span>Tel: <?php echo htmlspecialchars($branchResult['phone']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($branchResult['id_nat'])): ?>
                    <span style="float:right;">ID NAT: <?php echo htmlspecialchars($branchResult['id_nat']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="row-info">
                    <?php if (!empty($branchResult['vat_number'])): ?>
                    <span>VAT: <?php echo htmlspecialchars($branchResult['vat_number']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($branchResult['rccm'])): ?>
                    <span style="float:right;">RCCM: <?php echo htmlspecialchars($branchResult['rccm']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($branchResult['nif'])): ?>
                <p>NIF: <?php echo htmlspecialchars($branchResult['nif']); ?></p>
                <?php endif; ?>
                
                <p>Sale Receipt</p>
                <p>Date: <?php echo date('Y-m-d H:i:s', strtotime($sale['sale_date'])); ?></p>
                <p>Receipt #: <?php echo $sale_id; ?></p>
                <?php if (isset($sale['customer_name']) && !empty($sale['customer_name'])): ?>
                    <p>Customer: <?php echo htmlspecialchars($sale['customer_name']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="receipt-items">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="item-description">
                                    <?php 
                                    // Always use description, as name might contain barcode
                                    if (!empty($item['description'])) {
                                        echo htmlspecialchars($item['description']);
                                    } else {
                                        echo htmlspecialchars($item['product_name'] ?: 'Unknown Item');
                                    }
                                    ?>
                                </td>
                                <td class="text-right"><?php echo $item['quantity']; ?></td>
                                <td class="text-right">$<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-right">$<?php echo number_format($item['item_total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="receipt-total">
                <table>
                    <?php 
                    // Calculate subtotal (remove VAT from total)
                    $total_with_vat = $sale['total_amount'];
                    $subtotal = $total_with_vat / 1.16; // Remove 16% VAT
                    $vat_amount = $total_with_vat - $subtotal;
                    ?>
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-right">$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td>VAT (16%):</td>
                        <td class="text-right">$<?php echo number_format($vat_amount, 2); ?></td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td>Total:</td>
                        <td class="text-right">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="receipt-footer">
                <p>Thank you for your purchase!</p>
                <p>Please come again.</p>
            </div>
        </div>

        <script>
            // Automatically trigger print when page loads
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
} 