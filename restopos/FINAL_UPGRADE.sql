-- ══════════════════════════════════════════════════════════════
-- RestoPOS Sri Lanka — FINAL CONSOLIDATED UPGRADE SQL
-- Run this ONCE in phpMyAdmin → SQL tab (select your restopos
-- database first, then paste this whole file and click Go).
--
-- This is SAFE to run on your EXISTING live database — it does
-- NOT delete or reset any of your current data (bills, debtors,
-- inventory, payroll, etc). It only:
--   1. Adds the Reservations module table
--   2. Adds the Activity Log table
--   3. Adds Kitchen Display order-status tracking
--   4. Fixes the "role" column so Kitchen Boy (and any future
--      role) can always be saved correctly
--
-- If a statement below says a table/column "already exists" when
-- you run it, that's fine — it just means that part was already
-- applied before. Continue running the rest.
-- ══════════════════════════════════════════════════════════════


-- ─── 1. RESERVATIONS MODULE ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    contact VARCHAR(50) NOT NULL,
    res_date DATE NOT NULL,
    res_time TIME NOT NULL,
    pax INT NOT NULL DEFAULT 1,
    location VARCHAR(150),
    notes TEXT,
    status ENUM('Confirmed','Pending','Cancelled','Completed') DEFAULT 'Confirmed',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- ─── 2. ACTIVITY LOG MODULE ──────────────────────────────────────
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


-- ─── 3. KITCHEN DISPLAY SYSTEM ───────────────────────────────────
-- Tracks each order item's kitchen progress: pending → preparing → ready → served
-- (If you already ran this before, MySQL will say "Duplicate column" —
--  that's fine, it just means this part is already done. Skip and continue.)
ALTER TABLE bill_items ADD COLUMN kitchen_status
    ENUM('pending','preparing','ready','served') DEFAULT 'pending';


-- ─── 4. USER ROLES — PERMANENT FIX ───────────────────────────────
-- Converts the role column from a restrictive ENUM list to a flexible
-- VARCHAR. This permanently fixes the bug where MySQL silently
-- rejected any role not already in its predefined list (which is
-- exactly what was blocking "Kitchen Boy" from saving).
ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'cashier';


-- ─── 5. FIX ANY USERS WITH A BLANK ROLE ──────────────────────────
-- If any user ended up with an empty role due to the old bug above,
-- this safely sets them to 'cashier' as a default so they're never
-- locked out. You can change their role properly afterward in
-- Settings → System Users → Edit.
UPDATE users SET role = 'cashier' WHERE role IS NULL OR role = '';


-- ─── 6. SET AHMED ATHIF TO KITCHEN BOY ───────────────────────────
-- Direct fix for the specific user mentioned in this conversation.
-- Remove or change this line if this isn't what you want.
UPDATE users SET role = 'kitchen' WHERE username = 'athif';


-- ─── DONE — Verify everything ────────────────────────────────────
SELECT id, name, username, role FROM users ORDER BY id;

-- ══════════════════════════════════════════════════════════════
-- All done! Refresh your RestoPOS Settings page to confirm.
-- ══════════════════════════════════════════════════════════════
