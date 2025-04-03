-- Add branch_id column to the sales table
ALTER TABLE sales ADD COLUMN branch_id INT NOT NULL DEFAULT 1;

-- Add foreign key constraint
ALTER TABLE sales 
ADD CONSTRAINT fk_sales_branch 
FOREIGN KEY (branch_id) REFERENCES branches(id) 
ON DELETE RESTRICT;

-- Add branch_id column to the sale_items table
ALTER TABLE sale_items ADD COLUMN branch_id INT NOT NULL DEFAULT 1;

-- Add foreign key constraint
ALTER TABLE sale_items 
ADD CONSTRAINT fk_sale_items_branch 
FOREIGN KEY (branch_id) REFERENCES branches(id) 
ON DELETE RESTRICT;

-- Add branch_id column to inventory_transactions table
ALTER TABLE inventory_transactions ADD COLUMN branch_id INT NOT NULL DEFAULT 1;

-- Add foreign key constraint
ALTER TABLE inventory_transactions 
ADD CONSTRAINT fk_inventory_transactions_branch 
FOREIGN KEY (branch_id) REFERENCES branches(id) 
ON DELETE RESTRICT; 