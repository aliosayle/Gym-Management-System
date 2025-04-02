<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require 'layouts/config.php';

try {
    // Check if branch_id column already exists in packages table
    $checkColumnQuery = "SHOW COLUMNS FROM packages LIKE 'branch_id'";
    $checkColumnStmt = $pdo->prepare($checkColumnQuery);
    $checkColumnStmt->execute();
    
    if ($checkColumnStmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $alterTableQuery = "ALTER TABLE packages ADD COLUMN branch_id INT(11) NOT NULL DEFAULT 1";
        $pdo->exec($alterTableQuery);
        echo "SUCCESS: Added branch_id column to packages table.<br>";
    } else {
        echo "INFO: branch_id column already exists in packages table.<br>";
    }
    
    // Also check if we need to add branch_id to clients table
    $checkClientColumnQuery = "SHOW COLUMNS FROM clients LIKE 'branch_id'";
    $checkClientColumnStmt = $pdo->prepare($checkClientColumnQuery);
    $checkClientColumnStmt->execute();
    
    if ($checkClientColumnStmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $alterClientTableQuery = "ALTER TABLE clients ADD COLUMN branch_id INT(11) NOT NULL DEFAULT 1";
        $pdo->exec($alterClientTableQuery);
        echo "SUCCESS: Added branch_id column to clients table.<br>";
    } else {
        echo "INFO: branch_id column already exists in clients table.<br>";
    }
    
    echo "Schema update complete!";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?> 