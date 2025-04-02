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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
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

// Add a new user
if ($action === 'add_user') {
    // Validate inputs
    if (!isset($_POST['username']) || !isset($_POST['email']) || !isset($_POST['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $isadmin = (isset($_POST['isadmin']) && $_POST['isadmin'] === 'true') ? 1 : 0;

    // Check if username already exists
    $check_query = "SELECT id FROM users WHERE username = :username";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['username' => $username]);
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
        exit;
    }

    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE useremail = :email";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['email' => $email]);
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Insert user
        $query = "INSERT INTO users (username, useremail, password, isadmin, created_at) 
                  VALUES (:username, :email, :password, :isadmin, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'isadmin' => $isadmin
        ]);

        $new_user_id = $pdo->lastInsertId();

        // Create default permissions
        $permissions_query = "INSERT INTO user_permissions (user_id) VALUES (:user_id)";
        $permissions_stmt = $pdo->prepare($permissions_query);
        $permissions_stmt->execute(['user_id' => $new_user_id]);

        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => 'User added successfully']);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Update an existing user
else if ($action === 'update_user') {
    // Validate inputs
    if (!isset($_POST['id']) || !isset($_POST['username']) || !isset($_POST['email'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }

    $id = $_POST['id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $isadmin = (isset($_POST['isadmin']) && $_POST['isadmin'] === 'true') ? 1 : 0;

    // Check if username already exists for another user
    $check_query = "SELECT id FROM users WHERE username = :username AND id != :id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['username' => $username, 'id' => $id]);
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
        exit;
    }

    // Check if email already exists for another user
    $check_query = "SELECT id FROM users WHERE useremail = :email AND id != :id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['email' => $email, 'id' => $id]);
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Update user with or without password
        if (!empty($password)) {
            $query = "UPDATE users SET username = :username, useremail = :email, password = :password, isadmin = :isadmin WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'isadmin' => $isadmin
            ]);
        } else {
            $query = "UPDATE users SET username = :username, useremail = :email, isadmin = :isadmin WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'isadmin' => $isadmin
            ]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Delete a user
else if ($action === 'delete_user') {
    if (!isset($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User ID not provided']);
        exit;
    }

    $id = $_POST['id'];
    
    // Don't allow deletion of current user
    if ($id == $user_id) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot delete yourself']);
        exit;
    }

    try {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);

        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get user details
else if ($action === 'get_user') {
    if (!isset($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User ID not provided']);
        exit;
    }

    $id = $_POST['id'];
    
    try {
        // Get user details
        $query = "SELECT id, username, useremail, isadmin FROM users WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode(['status' => 'success', 'user' => $user]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get user permissions
else if ($action === 'get_permissions') {
    if (!isset($_POST['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User ID not provided']);
        exit;
    }

    $user_id = $_POST['user_id'];
    
    try {
        // Get user permissions
        $query = "SELECT * FROM user_permissions WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        
        $permissions = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($permissions) {
            echo json_encode(['status' => 'success', 'permissions' => $permissions]);
            exit;
        } else {
            // Create default permissions if not exist
            $query = "INSERT INTO user_permissions (user_id) VALUES (:user_id)";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            
            // Get the newly created permissions
            $query = "SELECT * FROM user_permissions WHERE user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            
            $permissions = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'permissions' => $permissions]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Save user permissions
else if ($action === 'save_permissions') {
    if (!isset($_POST['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User ID not provided']);
        exit;
    }

    $user_id = $_POST['user_id'];
    
    try {
        // Build update query
        $update_fields = [];
        $params = ['user_id' => $user_id];
        
        // Permission fields
        $permission_fields = [
            'can_view_dashboard',
            'can_manage_clients',
            'can_add_client',
            'can_edit_client',
            'can_delete_client',
            'can_manage_inventory',
            'can_manage_invoices',
            'can_use_pos',
            'can_view_reports',
            'can_manage_packages',
            'can_manage_companies',
            'can_manage_branches',
            'can_manage_users'
        ];
        
        foreach ($permission_fields as $field) {
            if (isset($_POST[$field])) {
                $value = ($_POST[$field] === 'true') ? 1 : 0;
                $update_fields[] = "$field = :$field";
                $params[$field] = $value;
            }
        }
        
        if (empty($update_fields)) {
            echo json_encode(['status' => 'error', 'message' => 'No permissions provided']);
            exit;
        }
        
        // Check if permissions exist
        $check_query = "SELECT COUNT(*) as count FROM user_permissions WHERE user_id = :user_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute(['user_id' => $user_id]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            // Update existing permissions
            $query = "UPDATE user_permissions SET " . implode(', ', $update_fields) . " WHERE user_id = :user_id";
        } else {
            // Insert new permissions
            $fields = ['user_id'];
            $values = [':user_id'];
            
            foreach ($permission_fields as $field) {
                if (isset($_POST[$field])) {
                    $fields[] = $field;
                    $values[] = ":$field";
                }
            }
            
            $query = "INSERT INTO user_permissions (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        echo json_encode(['status' => 'success', 'message' => 'Permissions saved successfully']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get user branch assignments
else if ($action === 'get_branches') {
    if (!isset($_POST['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User ID not provided']);
        exit;
    }

    $user_id = $_POST['user_id'];
    
    try {
        // Get user branches
        $query = "SELECT branch_id FROM user_branches WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $branches = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['status' => 'success', 'branches' => $branches]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Save user branch assignments
else if ($action === 'save_branches') {
    if (!isset($_POST['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'User ID not provided']);
        exit;
    }

    $user_id = $_POST['user_id'];
    
    try {
        // Delete existing branch assignments
        $delete_query = "DELETE FROM user_branches WHERE user_id = :user_id";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute(['user_id' => $user_id]);
        
        // Insert new branch assignments
        if (isset($_POST['branches']) && is_array($_POST['branches'])) {
            $branches = $_POST['branches'];
            
            foreach ($branches as $branch_id) {
                $query = "INSERT INTO user_branches (user_id, branch_id, assigned_by, assigned_at) 
                          VALUES (:user_id, :branch_id, :assigned_by, NOW())";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'user_id' => $user_id,
                    'branch_id' => $branch_id,
                    'assigned_by' => $user_id
                ]);
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Branch assignments saved successfully']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Get all branches
else if ($action === 'get_all_branches') {
    try {
        // Get all branches with company names
        $query = "SELECT b.id, b.name, c.name as company_name 
                 FROM branches b 
                 JOIN companies c ON b.company_id = c.id 
                 ORDER BY c.name, b.name";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'branches' => $branches
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error retrieving branches: ' . $e->getMessage()
        ]);
        exit;
    }
}

// If we get here, no valid action was found
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
exit; 