-- Create user_permissions table if it doesn't exist
CREATE TABLE IF NOT EXISTS `user_permissions` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `can_view_dashboard` TINYINT(1) DEFAULT 0,
    `can_manage_clients` TINYINT(1) DEFAULT 0,
    `can_add_client` TINYINT(1) DEFAULT 0,
    `can_edit_client` TINYINT(1) DEFAULT 0,
    `can_delete_client` TINYINT(1) DEFAULT 0,
    `can_manage_inventory` TINYINT(1) DEFAULT 0,
    `can_manage_invoices` TINYINT(1) DEFAULT 0,
    `can_use_pos` TINYINT(1) DEFAULT 0,
    `can_view_reports` TINYINT(1) DEFAULT 0,
    `can_manage_packages` TINYINT(1) DEFAULT 0,
    `can_manage_companies` TINYINT(1) DEFAULT 0,
    `can_manage_branches` TINYINT(1) DEFAULT 0,
    `can_manage_users` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Create user_branches table if it doesn't exist
CREATE TABLE IF NOT EXISTS `user_branches` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `branch_id` INT(11) NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `assigned_by` INT(11),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_user_branch` (`user_id`, `branch_id`)
);

-- Create default permissions for existing admin users
INSERT INTO `user_permissions` (
    `user_id`, 
    `can_view_dashboard`,
    `can_manage_clients`,
    `can_add_client`,
    `can_edit_client`,
    `can_delete_client`,
    `can_manage_inventory`,
    `can_manage_invoices`,
    `can_use_pos`,
    `can_view_reports`,
    `can_manage_packages`,
    `can_manage_companies`,
    `can_manage_branches`,
    `can_manage_users`
)
SELECT 
    `id`,
    1, -- can_view_dashboard
    1, -- can_manage_clients
    1, -- can_add_client
    1, -- can_edit_client
    1, -- can_delete_client
    1, -- can_manage_inventory
    1, -- can_manage_invoices
    1, -- can_use_pos
    1, -- can_view_reports
    1, -- can_manage_packages
    1, -- can_manage_companies
    1, -- can_manage_branches
    1  -- can_manage_users
FROM `users` 
WHERE `isadmin` = 1
AND NOT EXISTS (
    SELECT 1 FROM `user_permissions` WHERE `user_permissions`.`user_id` = `users`.`id`
);

-- Create minimal permissions for existing non-admin users
INSERT INTO `user_permissions` (
    `user_id`, 
    `can_view_dashboard`,
    `can_manage_clients`,
    `can_add_client`,
    `can_edit_client`,
    `can_delete_client`,
    `can_manage_inventory`,
    `can_manage_invoices`,
    `can_use_pos`,
    `can_view_reports`,
    `can_manage_packages`,
    `can_manage_companies`,
    `can_manage_branches`,
    `can_manage_users`
)
SELECT 
    `id`,
    1, -- can_view_dashboard
    1, -- can_manage_clients
    1, -- can_add_client
    1, -- can_edit_client
    0, -- can_delete_client
    0, -- can_manage_inventory
    0, -- can_manage_invoices
    1, -- can_use_pos
    0, -- can_view_reports
    0, -- can_manage_packages
    0, -- can_manage_companies
    0, -- can_manage_branches
    0  -- can_manage_users
FROM `users` 
WHERE `isadmin` = 0
AND NOT EXISTS (
    SELECT 1 FROM `user_permissions` WHERE `user_permissions`.`user_id` = `users`.`id`
); 