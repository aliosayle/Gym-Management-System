<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Include database connection
include '../layouts/config.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if user is admin
$user_id = $_SESSION['id'];
$query = "SELECT isadmin FROM users WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($user['isadmin']) || $user['isadmin'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// Check if action is specified
if (!isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'];

// Get branches for a company
if ($action === 'get_branches') {
    // Check required fields
    if (empty($_POST['company_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Company ID is required']);
        exit;
    }

    try {
        $query = "SELECT * FROM branches WHERE company_id = :company_id ORDER BY name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'company_id' => $_POST['company_id']
        ]);
        
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'branches' => $branches]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Get all branches (for preloading)
else if ($action === 'get_all_branches') {
    try {
        $query = "SELECT * FROM branches ORDER BY company_id, name";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'branches' => $branches]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Add a new branch
else if ($action === 'add_branch') {
    // Check required fields
    if (empty($_POST['company_id']) || empty($_POST['name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Company ID and branch name are required']);
        exit;
    }

    try {
        $query = "INSERT INTO branches (company_id, name, manager, address, phone, email) 
                  VALUES (:company_id, :name, :manager, :address, :phone, :email)";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'company_id' => $_POST['company_id'],
            'name' => $_POST['name'],
            'manager' => $_POST['manager'] ?? null,
            'address' => $_POST['address'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Branch added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add branch']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Update an existing branch
else if ($action === 'update_branch') {
    // Check required fields
    if (empty($_POST['id']) || empty($_POST['company_id']) || empty($_POST['name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Branch ID, company ID, and name are required']);
        exit;
    }

    try {
        $query = "UPDATE branches SET company_id = :company_id, name = :name, manager = :manager, 
                 address = :address, phone = :phone, email = :email WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'id' => $_POST['id'],
            'company_id' => $_POST['company_id'],
            'name' => $_POST['name'],
            'manager' => $_POST['manager'] ?? null,
            'address' => $_POST['address'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Branch updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update branch']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Delete a branch
else if ($action === 'delete_branch') {
    // Check required fields
    if (empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Branch ID is required']);
        exit;
    }

    try {
        $query = "DELETE FROM branches WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'id' => $_POST['id']
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Branch deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete branch']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Get branch details
else if ($action === 'get_branch') {
    // Check required fields
    if (empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Branch ID is required']);
        exit;
    }

    try {
        $query = "SELECT * FROM branches WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'id' => $_POST['id']
        ]);
        
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($branch) {
            echo json_encode(['status' => 'success', 'branch' => $branch]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Branch not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Invalid action
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} 