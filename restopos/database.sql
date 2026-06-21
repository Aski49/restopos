-- ══════════════════════════════════════════════════════════════
-- RestoPOS Sri Lanka — Complete Database Schema
-- Version 2.0 | Created by Aski Ahamed
-- Import via phpMyAdmin: Create DB 'restopos' → Import this file
-- ══════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── USERS ───────────────────────────────────────────────────
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'cashier',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO users (name, username, password, role) VALUES
('Administrator', 'admin',   'admin123',   'admin'),
('Manager',       'manager', 'manager123', 'manager'),
('Cashier',       'cashier', 'cashier123', 'cashier'),
('Kitchen Boy',   'kitchen', 'kitchen123', 'kitchen');

-- ─── SETTINGS ────────────────────────────────────────────────
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO settings (setting_key, setting_value) VALUES
('business_name',      'My Restaurant'),
('address',            'Colombo, Sri Lanka'),
('phone',              '+94 11 234 5678'),
('email',              'info@myrestaurant.lk'),
('service_charge_pct', '10'),
('tax_pct',            '8'),
('ubereats_commission','30'),
('pickme_commission',  '28'),
('currency',           'Rs.');

-- ─── MENU CATEGORIES ─────────────────────────────────────────
DROP TABLE IF EXISTS menu_categories;
CREATE TABLE menu_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1
);
INSERT INTO menu_categories (name, sort_order) VALUES
('Rice Meals', 1), ('Kottu', 2), ('Noodles', 3),
('Beverages', 4),  ('Snacks', 5), ('Desserts', 6);

-- ─── MENU ITEMS ──────────────────────────────────────────────
DROP TABLE IF EXISTS menu_items;
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id)
);
INSERT INTO menu_items (category_id, name, price) VALUES
(1,'Chicken Rice',450),(1,'Fish Curry Rice',500),(1,'Fried Rice',480),(1,'Veggie Rice',350),
(2,'Veggie Kottu',380),(2,'Egg Kottu',420),(2,'Chicken Kottu',520),(2,'Cheese Kottu',580),(2,'Prawn Kottu',680),
(3,'Veg Noodles',380),(3,'Chicken Noodles',480),
(4,'Plain Tea',80),(4,'Milk Tea',120),(4,'Mango Juice',200),(4,'Soft Drink',150),(4,'Water Bottle',80),
(5,'Chicken Sandwich',280),(5,'Samosa',60),(5,'Short Eats',45),(5,'Garlic Bread',150);

-- ─── BILLS ───────────────────────────────────────────────────
DROP TABLE IF EXISTS bills;
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_no VARCHAR(30) NOT NULL UNIQUE,
    order_type ENUM('Dine-In','Takeaway','Uber Eats','PickMe','Delivery') DEFAULT 'Dine-In',
    table_no VARCHAR(10),
    subtotal DECIMAL(10,2) DEFAULT 0,
    service_charge DECIMAL(10,2) DEFAULT 0,
    discount_pct DECIMAL(5,2) DEFAULT 0,
    discount_amt DECIMAL(10,2) DEFAULT 0,
    tax_amt DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    cash_given DECIMAL(10,2) DEFAULT 0,
    change_amt DECIMAL(10,2) DEFAULT 0,
    status ENUM('settled','voided','pending') DEFAULT 'settled',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── BILL ITEMS ──────────────────────────────────────────────
DROP TABLE IF EXISTS bill_items;
CREATE TABLE bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    menu_item_id INT,
    item_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    kitchen_status ENUM('pending','preparing','ready','served') DEFAULT 'pending',
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
);

-- ─── PROMOTIONS ──────────────────────────────────────────────
DROP TABLE IF EXISTS promotions;
CREATE TABLE promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    promo_type ENUM('percent_off','fixed_off','buy_x_get_y') NOT NULL DEFAULT 'percent_off',
    discount_value DECIMAL(10,2) DEFAULT 0,
    buy_qty INT DEFAULT 1,
    get_qty INT DEFAULT 1,
    applies_to ENUM('all','category','item') DEFAULT 'all',
    applies_id INT DEFAULT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    valid_from DATE,
    valid_to DATE,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO promotions (name, description, promo_type, discount_value, applies_to, valid_from, valid_to) VALUES
('10% Off All Orders', 'Get 10% discount on your entire bill', 'percent_off', 10, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
('Happy Hour Rs.100 Off', 'Rs. 100 off on orders above Rs. 500', 'fixed_off', 100, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY));

-- ─── BILL PROMOTIONS ─────────────────────────────────────────
DROP TABLE IF EXISTS bill_promotions;
CREATE TABLE bill_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    promo_id INT,
    promo_name VARCHAR(150),
    discount_amt DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
);

-- ─── EXPENSE CATEGORIES ──────────────────────────────────────
DROP TABLE IF EXISTS expense_categories;
CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    active TINYINT(1) DEFAULT 1
);
INSERT INTO expense_categories (name) VALUES
('Utilities'),('Rent'),('Purchases'),('Transport'),
('Maintenance'),('Marketing'),('Salaries'),('Miscellaneous');

-- ─── EXPENSES ────────────────────────────────────────────────
DROP TABLE IF EXISTS expenses;
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category_id INT,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method ENUM('Cash','Card','Bank Transfer') DEFAULT 'Cash',
    supplier VARCHAR(150),
    invoice_no VARCHAR(100),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id)
);

-- ─── INVENTORY CATEGORIES ────────────────────────────────────
DROP TABLE IF EXISTS inventory_categories;
CREATE TABLE inventory_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);
INSERT INTO inventory_categories (name) VALUES
('Grains'),('Proteins'),('Oils'),('Produce'),('Dairy'),('Beverages'),('Utilities');

-- ─── INVENTORY ITEMS ─────────────────────────────────────────
DROP TABLE IF EXISTS inventory_items;
CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    qty DECIMAL(10,3) DEFAULT 0,
    min_qty DECIMAL(10,3) DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(id)
);
INSERT INTO inventory_items (category_id, name, unit, qty, min_qty, unit_cost) VALUES
(1,'Rice','kg',45,20,180),(2,'Chicken','kg',15,10,1100),(2,'Fish','kg',8,8,900),
(1,'Flour','kg',30,15,210),(3,'Coconut Oil','L',12,5,520),(4,'Vegetables','kg',10,10,150),
(6,'Tea Packets','packs',5,5,850),(7,'Gas Cylinders','cylinders',2,2,4500);

-- ─── STOCK PURCHASES ─────────────────────────────────────────
DROP TABLE IF EXISTS stock_purchases;
CREATE TABLE stock_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    qty DECIMAL(10,3) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    supplier VARCHAR(150),
    invoice_no VARCHAR(100),
    payment_method ENUM('Cash','Card','Bank Transfer') DEFAULT 'Cash',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

-- ─── EMPLOYEES ───────────────────────────────────────────────
DROP TABLE IF EXISTS employees;
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    nic VARCHAR(20),
    phone VARCHAR(20),
    position VARCHAR(100),
    basic_salary DECIMAL(10,2) DEFAULT 0,
    allowances DECIMAL(10,2) DEFAULT 0,
    epf_applicable TINYINT(1) DEFAULT 1,
    joined_date DATE,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO employees (name, nic, phone, position, basic_salary, allowances, epf_applicable, joined_date) VALUES
('Kasun Perera',      '199012345678', '+94771234567', 'Manager',        65000, 8000, 1, '2022-01-01'),
('Nimali Silva',      '199534567890', '+94772345678', 'Cashier',        42000, 5000, 1, '2022-03-15'),
('Thilina Fernando',  '198890123456', '+94773456789', 'Chef',           55000, 7000, 1, '2021-06-01'),
('Chamari Jayawardena','200012345670','+94774567890', 'Waiter',         35000, 4000, 1, '2023-01-10'),
('Ruwan Bandara',     '199678901234', '+94775678901', 'Kitchen Helper', 28000, 3000, 0, '2023-05-20');

-- ─── ATTENDANCE ──────────────────────────────────────────────
DROP TABLE IF EXISTS attendance;
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    att_date DATE NOT NULL,
    status ENUM('Present','Absent','Half Day','Leave') DEFAULT 'Present',
    time_in TIME,
    time_out TIME,
    overtime_hours DECIMAL(4,2) DEFAULT 0,
    notes VARCHAR(255),
    UNIQUE KEY uq_emp_date (employee_id, att_date),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ─── PAYROLL ─────────────────────────────────────────────────
DROP TABLE IF EXISTS payroll;
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    pay_month DATE NOT NULL,
    basic_salary DECIMAL(10,2) DEFAULT 0,
    allowances DECIMAL(10,2) DEFAULT 0,
    overtime_pay DECIMAL(10,2) DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0,
    gross_salary DECIMAL(10,2) DEFAULT 0,
    salary_advance DECIMAL(10,2) DEFAULT 0,
    other_deductions DECIMAL(10,2) DEFAULT 0,
    epf_employee DECIMAL(10,2) DEFAULT 0,
    epf_employer DECIMAL(10,2) DEFAULT 0,
    etf_employer DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) DEFAULT 0,
    status ENUM('draft','approved','paid') DEFAULT 'draft',
    paid_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_month (employee_id, pay_month),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ─── DEBTORS ─────────────────────────────────────────────────
DROP TABLE IF EXISTS debtors;
CREATE TABLE debtors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    credit_limit DECIMAL(10,2) DEFAULT 0,
    outstanding DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO debtors (name, phone, outstanding) VALUES
('ABC Company',    '+94112345678', 28500),
('XYZ Office',     '+94113456789', 12000),
('Colombo Hotel',  '+94114567890', 67000),
('Mr. Pradeep',    '+94775678901', 3500);

-- ─── DEBTOR PAYMENTS ─────────────────────────────────────────
DROP TABLE IF EXISTS debtor_payments;
CREATE TABLE debtor_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    debtor_id INT NOT NULL,
    bill_id INT,
    txn_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('charge','payment') DEFAULT 'charge',
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (debtor_id) REFERENCES debtors(id)
);

-- ─── BANK ACCOUNTS ───────────────────────────────────────────
DROP TABLE IF EXISTS bank_accounts;
CREATE TABLE bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    account_no VARCHAR(50),
    account_name VARCHAR(150),
    balance DECIMAL(12,2) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO bank_accounts (bank_name, account_no, account_name, balance) VALUES
('Commercial Bank', '1234567890', 'My Restaurant', 285000),
('BOC',             '0987654321', 'My Restaurant', 100000);

-- ─── BANK TRANSACTIONS ───────────────────────────────────────
DROP TABLE IF EXISTS bank_transactions;
CREATE TABLE bank_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    txn_date DATE NOT NULL,
    type ENUM('deposit','withdrawal','transfer') DEFAULT 'deposit',
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    reference VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES bank_accounts(id)
);

SET FOREIGN_KEY_CHECKS = 1;

-- ─── RESERVATIONS ────────────────────────────────────────────
DROP TABLE IF EXISTS reservations;
CREATE TABLE reservations (
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

-- ─── ACTIVITY LOG ────────────────────────────────────────────
DROP TABLE IF EXISTS activity_log;
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(150),
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ══════════════════════════════════════════════════════════════
-- Done! Tables created:
-- users, settings, menu_categories, menu_items,
-- bills, bill_items (incl. kitchen_status), promotions, bill_promotions,
-- expense_categories, expenses,
-- inventory_categories, inventory_items, stock_purchases,
-- employees, attendance, payroll,
-- debtors, debtor_payments,
-- bank_accounts, bank_transactions,
-- reservations, activity_log
-- ══════════════════════════════════════════════════════════════
