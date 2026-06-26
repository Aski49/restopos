-- ══════════════════════════════════════════════════════════════
-- RestoPOS Sri Lanka — FINAL CONSOLIDATED UPGRADE SQL
-- (Covers every update made across the whole project)
--
-- Run this ONCE in phpMyAdmin → SQL tab (select your restopos
-- database first, then paste this whole file and click Go).
--
-- This is SAFE to run on your EXISTING live database — it does
-- NOT delete or reset any of your current data (bills, debtors,
-- inventory, payroll, etc). It only ADDS what's missing:
--
--   1. Reservations module
--   2. Activity Log module
--   3. Kitchen Display System (order status tracking)
--   4. User roles fixed permanently (so Kitchen Boy, or any
--      future role, always saves correctly)
--   5. Inventory Recipes + automatic stock usage tracking
--
-- If any statement below says something "already exists" when
-- you run it, that's fine — it just means that part was already
-- applied before. Continue running the rest; nothing will break.
-- ══════════════════════════════════════════════════════════════


-- ─── 1. RESERVATIONS MODULE ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    contact VARCHAR(50) NOT NULL,
    res_date DATE NOT NULL,
    res_time TIME NOT NULL,
    res_end_time TIME NULL,
    pax INT NOT NULL DEFAULT 1,
    location VARCHAR(150),
    notes TEXT,
    status ENUM('Confirmed','Pending','Cancelled','Completed') DEFAULT 'Confirmed',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add end time column if table already existed without it
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS res_end_time TIME NULL AFTER res_time;


-- ─── 2. ACTIVITY LOG MODULE ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(150),
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ─── 3. KITCHEN DISPLAY SYSTEM ────────────────────────────────────
-- Tracks each order item's kitchen progress: pending → preparing → ready → served
-- (If you already ran this before, MySQL will say "Duplicate column" —
--  that's fine, it just means this part is already done. Skip and continue.)
ALTER TABLE bill_items ADD COLUMN kitchen_status
    ENUM('pending','preparing','ready','served') DEFAULT 'pending';


-- ─── 4. USER ROLES — PERMANENT FIX ────────────────────────────────
-- Converts the role column from a restrictive ENUM list to a flexible
-- VARCHAR. This permanently fixes the bug where MySQL silently
-- rejected any role not already in its predefined list (e.g. when
-- "Kitchen Boy" first failed to save).
ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'cashier';

-- Fix any users that ended up with a blank role from the old bug above.
-- They're set to 'cashier' as a safe default — change their role
-- properly afterward in Settings → System Users → Edit if needed.
UPDATE users SET role = 'cashier' WHERE role IS NULL OR role = '';


-- ─── 5. INVENTORY — RECIPES + AUTOMATIC USAGE TRACKING ────────────
-- Links each menu item to the inventory items (and quantities) it
-- consumes per unit sold, and automatically logs every deduction
-- so it can be reported on by date / month / year.
CREATE TABLE IF NOT EXISTS menu_item_recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    qty_per_unit DECIMAL(10,4) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_menu_inv (menu_item_id, inventory_item_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stock_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    usage_date DATE NOT NULL,
    qty DECIMAL(10,3) NOT NULL,
    source ENUM('bill','manual') DEFAULT 'bill',
    bill_id INT NULL,
    menu_item_name VARCHAR(150) NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);


-- ─── DONE — Verify everything ─────────────────────────────────────
SELECT id, name, username, role FROM users ORDER BY id;

-- ══════════════════════════════════════════════════════════════
-- All done! Refresh RestoPOS and check:
--   • Settings → System Users → roles display and save correctly
--   • Sidebar shows Reservations, Kitchen Display, Activity Log
--   • Inventory → Recipes tab is available
--   • Inventory → Usage Report tab is available
-- ══════════════════════════════════════════════════════════════


-- ─── 6. ONLINE ORDERING SYSTEM ────────────────────────────────
CREATE TABLE IF NOT EXISTS online_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(30) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_note TEXT,
    order_type ENUM('takeaway','card','bank_transfer') DEFAULT 'takeaway',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    service_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('new','confirmed','preparing','ready','completed','cancelled') DEFAULT 'new',
    seen TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS online_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT,
    item_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES online_orders(id) ON DELETE CASCADE
);


-- ─── 7. MENU ITEM IMAGES ──────────────────────────────────────
ALTER TABLE menu_items ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL AFTER description;
