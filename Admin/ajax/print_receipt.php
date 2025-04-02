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

try {
    // Get sale details
    $query = "SELECT s.*, 
              GROUP_CONCAT(CONCAT(p.description, ' x', si.quantity) SEPARATOR ', ') as items_list
              FROM sales s
              LEFT JOIN sale_items si ON s.sale_id = si.sale_id
              LEFT JOIN products p ON si.product_id = p.product_id
              WHERE s.sale_id = :sale_id
              GROUP BY s.sale_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['sale_id' => $sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        die('Sale not found');
    }

    // Get sale items
    $query = "SELECT si.*, p.description as product_name, p.name as barcode, si.price * si.quantity as item_total 
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
            body {
                font-family: 'Courier New', monospace;
                line-height: 1.4;
                margin: 0;
                padding: 20px;
                font-size: 12px;
            }
            .receipt-header {
                text-align: center;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px dashed #000;
            }
            .receipt-items {
                margin-bottom: 15px;
            }
            .receipt-total {
                border-top: 1px dashed #000;
                padding-top: 10px;
                font-weight: bold;
            }
            .receipt-footer {
                text-align: center;
                margin-top: 15px;
                border-top: 1px dashed #000;
                padding-top: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                padding: 5px;
                text-align: left;
            }
            th {
                text-align: right;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="receipt-header">
            <h4>Gym Management System</h4>
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
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
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
                <tr>
                    <td>Total:</td>
                    <td class="text-right">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="receipt-footer">
            <p>Thank you for your purchase!</p>
            <p>Please come again.</p>
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