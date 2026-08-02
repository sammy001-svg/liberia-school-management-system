-- School Store — selling uniforms, stationery and other items to students
-- ---------------------------------------------------------------------------
-- Deliberately separate from the existing `inventory` table: that one tracks the
-- school's own assets (location, supplier, no selling price), which is a different
-- job from a priced catalogue that moves stock and generates receivables.
--
-- Money flows through the EXISTING finance machinery rather than a parallel set of
-- books: every sale to a student raises an invoice, and a paid sale also writes a
-- payment, so arrears, statements, the collection report and the parent portal all
-- pick store charges up with no extra wiring.
--
-- Safe to re-run: every statement is guarded.

CREATE TABLE IF NOT EXISTS store_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    sku VARCHAR(60) DEFAULT NULL,
    category VARCHAR(80) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    -- What the school pays vs. what it charges. cost_price drives the profit
    -- column on the reports; it is optional because donated stock has no cost.
    cost_price DECIMAL(10,2) DEFAULT NULL,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_qty INT NOT NULL DEFAULT 0,
    -- Stock at or below this level is flagged for reordering. 0 disables the alert.
    reorder_level INT NOT NULL DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'pcs',
    -- Retired items stay in the table so historical sale lines keep resolving,
    -- but drop out of the sell screen.
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_store_sku (tenant_id, sku),
    KEY idx_store_items_tenant (tenant_id, is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    sale_no VARCHAR(50) NOT NULL,
    -- A sale is either to a student (billable to their account) or a walk-in
    -- buyer identified by name only, which must be paid on the spot.
    student_id INT UNSIGNED DEFAULT NULL,
    buyer_name VARCHAR(150) DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash','mpesa','bank','cheque','online','account') DEFAULT 'cash',
    -- 'account' = charged to the student's fee account instead of collected now.
    status ENUM('paid','partial','credit','void') NOT NULL DEFAULT 'paid',
    -- The invoice this sale raised, so a void can reverse the right one.
    invoice_id INT UNSIGNED DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    sold_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    voided_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uniq_store_sale_no (tenant_id, sale_no),
    KEY idx_store_sales_student (tenant_id, student_id),
    KEY idx_store_sales_date (tenant_id, created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED DEFAULT NULL,
    -- Name and prices are snapshotted at the moment of sale so a later price
    -- change (or a deleted item) can never rewrite a historical receipt.
    item_name VARCHAR(150) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    KEY idx_sale_items_sale (sale_id),
    FOREIGN KEY (sale_id) REFERENCES store_sales(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Every change to stock_qty leaves a row here, so a discrepancy can be traced to
-- the sale, delivery or manual correction that caused it.
CREATE TABLE IF NOT EXISTS store_stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    -- Signed: negative for a sale, positive for a restock or a voided sale.
    change_qty INT NOT NULL,
    balance_after INT NOT NULL,
    reason ENUM('restock','sale','void','adjustment','opening') NOT NULL,
    sale_id INT UNSIGNED DEFAULT NULL,
    unit_cost DECIMAL(10,2) DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_movements_item (item_id, created_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_items(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_id) REFERENCES store_sales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- permissions ------------------------------------------------------------
-- store.sell is separate from store.manage so a shop attendant can ring up sales
-- without being able to change prices or write off stock.
INSERT IGNORE INTO permissions (name, module, action, description) VALUES
  ('store.manage', 'store', 'manage', 'Add and edit store items, prices, stock and void sales'),
  ('store.sell',   'store', 'sell',   'Sell store items to students and print receipts');

-- Grant both to the built-in roles that already handle money.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON p.module = 'store'
 WHERE r.tenant_id IS NULL AND r.name IN ('School Admin', 'Accountant');
