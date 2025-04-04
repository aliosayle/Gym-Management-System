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

// Add a new company
if ($action === 'add_company') {
    // Check required fields
    if (empty($_POST['name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Company name is required']);
        exit;
    }

    try {
        $query = "INSERT INTO companies (name, contact_person, address, phone, email, id_nat, vat_number, rccm, nif) 
                  VALUES (:name, :contact_person, :address, :phone, :email, :id_nat, :vat_number, :rccm, :nif)";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'name' => $_POST['name'],
            'contact_person' => $_POST['contact_person'] ?? null,
            'address' => $_POST['address'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'id_nat' => $_POST['id_nat'] ?? null,
            'vat_number' => $_POST['vat_number'] ?? null,
            'rccm' => $_POST['rccm'] ?? null,
            'nif' => $_POST['nif'] ?? null
        ]);
     
     if ($result) {
         echo json_encode(['status' => 'success', 'message' => 'Company added successfully']);
     } else {
         echo json_encode(['status' => 'error', 'message' => 'Failed to add company']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
     }
}

// Update an existing company
else if ($action === 'update_company') {
    // Check required fields
    if (empty($_POST['id']) || empty($_POST['name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Company ID and name are required']);
        exit;
    }

    try {
        $query = "UPDATE companies SET 
                 name = :name, 
                 contact_person = :contact_person, 
                 address = :address, 
                 phone = :phone, 
                 email = :email,
                 id_nat = :id_nat,
                 vat_number = :vat_number,
                 rccm = :rccm,
                 nif = :nif
                 WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'id' => $_POST['id'],
            'name' => $_POST['name'],
            'contact_person' => $_POST['contact_person'] ?? null,
            'address' => $_POST['address'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'email' => $_POST['email'] ?? null,
            'id_nat' => $_POST['id_nat'] ?? null,
            'vat_number' => $_POST['vat_number'] ?? null,
            'rccm' => $_POST['rccm'] ?? null,
            'nif' => $_POST['nif'] ?? null
        ]);
     
     if ($result) {
         echo json_encode(['status' => 'success', 'message' => 'Company updated successfully']);
     } else {
         echo json_encode(['status' => 'error', 'message' => 'Failed to update company']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
     }
}

// Delete a company
else if ($action === 'delete_company') {
    // Check required fields
    if (empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Company ID is required']);
        exit;
    }

    try {
        $query = "DELETE FROM companies WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'id' => $_POST['id']
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Company deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete company']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Get company details
else if ($action === 'get_company') {
    // Check required fields
    if (empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Company ID is required']);
        exit;
    }

    try {
        $query = "SELECT * FROM companies WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'id' => $_POST['id']
        ]);
        
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($company) {
            echo json_encode(['status' => 'success', 'company' => $company]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Company not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Invalid action
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} 