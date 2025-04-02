-- User permissions table (extends existing users table)
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    can_view_dashboard TINYINT(1) DEFAULT 0,
    can_manage_clients TINYINT(1) DEFAULT 0,
    can_add_client TINYINT(1) DEFAULT 0,
    can_edit_client TINYINT(1) DEFAULT 0,
    can_delete_client TINYINT(1) DEFAULT 0,
    can_manage_inventory TINYINT(1) DEFAULT 0,
    can_manage_invoices TINYINT(1) DEFAULT 0,
    can_use_pos TINYINT(1) DEFAULT 0,
    can_view_reports TINYINT(1) DEFAULT 0,
    can_manage_packages TINYINT(1) DEFAULT 0,
    can_manage_companies TINYINT(1) DEFAULT 0,
    can_manage_branches TINYINT(1) DEFAULT 0,
    can_manage_users TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User-branch assignment table 
CREATE TABLE IF NOT EXISTS user_branches (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    branch_id INT(11) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT(11),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_branch (user_id, branch_id)
);

-- User roles table
CREATE TABLE IF NOT EXISTS user_roles (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    is_system_role TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO user_roles (name, description, is_system_role) VALUES
('Administrator', 'Full access to all system functions', 1),
('Manager', 'Can manage most aspects except user management', 1),
('Receptionist', 'Can manage clients and subscriptions', 1),
('Trainer', 'Limited access to client data only', 1),
('Cashier', 'POS and payment processing access only', 1);

-- Role permissions table
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    role_id INT(11) NOT NULL,
    can_view_dashboard TINYINT(1) DEFAULT 0,
    can_manage_clients TINYINT(1) DEFAULT 0,
    can_add_client TINYINT(1) DEFAULT 0,
    can_edit_client TINYINT(1) DEFAULT 0,
    can_delete_client TINYINT(1) DEFAULT 0,
    can_manage_inventory TINYINT(1) DEFAULT 0,
    can_manage_invoices TINYINT(1) DEFAULT 0,
    can_use_pos TINYINT(1) DEFAULT 0,
    can_view_reports TINYINT(1) DEFAULT 0,
    can_manage_packages TINYINT(1) DEFAULT 0,
    can_manage_companies TINYINT(1) DEFAULT 0,
    can_manage_branches TINYINT(1) DEFAULT 0,
    can_manage_users TINYINT(1) DEFAULT 0,
    FOREIGN KEY (role_id) REFERENCES user_roles(id) ON DELETE CASCADE
);

-- Populate default role permissions
-- Administrator (all permissions)
INSERT INTO role_permissions (
    role_id, 
    can_view_dashboard, can_manage_clients, can_add_client, can_edit_client, can_delete_client,
    can_manage_inventory, can_manage_invoices, can_use_pos, can_view_reports,
    can_manage_packages, can_manage_companies, can_manage_branches, can_manage_users
) VALUES (
    1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1
);

-- Manager
INSERT INTO role_permissions (
    role_id, 
    can_view_dashboard, can_manage_clients, can_add_client, can_edit_client, can_delete_client,
    can_manage_inventory, can_manage_invoices, can_use_pos, can_view_reports,
    can_manage_packages, can_manage_companies, can_manage_branches, can_manage_users
) VALUES (
    2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 0
);

-- Receptionist
INSERT INTO role_permissions (
    role_id, 
    can_view_dashboard, can_manage_clients, can_add_client, can_edit_client, can_delete_client,
    can_manage_inventory, can_manage_invoices, can_use_pos, can_view_reports,
    can_manage_packages, can_manage_companies, can_manage_branches, can_manage_users
) VALUES (
    3, 1, 1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 0, 0
);

-- Trainer
INSERT INTO role_permissions (
    role_id, 
    can_view_dashboard, can_manage_clients, can_add_client, can_edit_client, can_delete_client,
    can_manage_inventory, can_manage_invoices, can_use_pos, can_view_reports,
    can_manage_packages, can_manage_companies, can_manage_branches, can_manage_users
) VALUES (
    4, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0
);

-- Cashier
INSERT INTO role_permissions (
    role_id, 
    can_view_dashboard, can_manage_clients, can_add_client, can_edit_client, can_delete_client,
    can_manage_inventory, can_manage_invoices, can_use_pos, can_view_reports,
    can_manage_packages, can_manage_companies, can_manage_branches, can_manage_users
) VALUES (
    5, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0
);

-- User roles assignment
CREATE TABLE IF NOT EXISTS user_role_assignments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    role_id INT(11) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT(11),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES user_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id)
);

-- Alter the users table to add profile fields if needed
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) AFTER id,
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER email,
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) AFTER phone,
ADD COLUMN IF NOT EXISTS last_login DATETIME AFTER profile_image,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' AFTER last_login,
ADD COLUMN IF NOT EXISTS created_by INT(11) AFTER status,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD FOREIGN KEY IF NOT EXISTS (created_by) REFERENCES users(id) ON DELETE SET NULL; 