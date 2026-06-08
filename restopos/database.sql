-- ============================================================
--  RestoPOS Sri Lanka — Database Schema
--  MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS restopos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restopos;

-- ─── USERS ───────────────────────────────────────────────────
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','cashier') DEFAULT 'cashier',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin: admin / admin123
INSERT INTO users (name, username, password, role) VALUES
('Administrator', 'admin', 'admin123', 'admin'),
('Manager', 'manager', 'manager123', 'manager'),
('Cashier', 'cashier', 'cashier123', 'cashier');

-- ─── BUSINESS SETTINGS ───────────────────────────────────────
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings (setting_key, setting_value) VALUES
('business_name', 'My Restaurant'),
('address', 'Colombo 07, Sri Lanka'),
('phone', '+94 11 234 5678'),
('email', 'info@myrestaurant.lk'),
('service_charge_pct', '10'),
('tax_pct', '8'),
('ubereats_commission', '30'),
('pickme_commission', '28'),
('currency', 'Rs.');

-- ─── MENU CATEGORIES ─────────────────────────────────────────
CREATE TABLE menu_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1
);

INSERT INTO menu_categories (name, sort_order) VALUES
('Rice Meals', 1), ('Kottu', 2), ('Noodles', 3),
('Beverages', 4), ('Snacks', 5), ('Desserts', 6);

-- ─── MENU ITEMS ──────────────────────────────────────────────
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id)
);

INSERT INTO menu_items (category_id, name, price) VALUES
(1, 'Chicken Rice', 450), (1, 'Fish Curry Rice', 500), (1, 'Veggie Rice', 350), (1, 'Fried Rice', 480),
(2, 'Veggie Kottu', 380), (2, 'Egg Kottu', 420), (2, 'Chicken Kottu', 520), (2, 'Prawn Kottu', 680), (2, 'Cheese Kottu', 580),
(3, 'Veg Noodles', 380), (3, 'Chicken Noodles', 480),
(4, 'Plain Tea', 80), (4, 'Milk Tea', 120), (4, 'Mango Juice', 200), (4, 'Soft Drink', 150), (4, 'Water Bottle', 80),
(5, 'Chicken Sandwich', 280), (5, 'Samosa', 60), (5, 'Garlic Bread', 150), (5, 'Short Eats', 45);

-- ─── BILLS (ORDERS) ──────────────────────────────────────────
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_no VARCHAR(20) UNIQUE NOT NULL,
    order_type ENUM('Dine-In','Takeaway','Uber Eats','PickMe','Delivery') DEFAULT 'Dine-In',
    table_no VARCHAR(10),
    subtotal DECIMAL(10,2) DEFAULT 0,
    service_charge DECIMAL(10,2) DEFAULT 0,
    discount_pct DECIMAL(5,2) DEFAULT 0,
    discount_amt DECIMAL(10,2) DEFAULT 0,
    tax_amt DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    payment_method ENUM('Cash','Card','Bank Transfer','QR','Credit','Uber Eats','PickMe') DEFAULT 'Cash',
    cash_given DECIMAL(10,2) DEFAULT 0,
    change_amt DECIMAL(10,2) DEFAULT 0,
    status ENUM('open','settled','voided') DEFAULT 'open',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ─── BILL ITEMS ───────────────────────────────────────────────
CREATE TABLE bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- ─── EXPENSES ────────────────────────────────────────────────
CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

INSERT INTO expense_categories (name) VALUES
('Utilities'),('Rent'),('Purchases'),('Transport'),
('Maintenance'),('Marketing'),('Salaries'),('Miscellaneous');

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash','Card','Bank Transfer') DEFAULT 'Cash',
    supplier VARCHAR(150),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

INSERT INTO expenses (expense_date, category_id, description, amount, payment_method) VALUES
(CURDATE(), 1, 'Electricity Bill', 18500, 'Bank Transfer'),
(CURDATE(), 2, 'Monthly Rent', 85000, 'Bank Transfer'),
(CURDATE(), 3, 'Chicken & Fish Purchase', 22000, 'Cash'),
(CURDATE(), 4, 'Delivery Fuel', 4500, 'Cash');

-- ─── INVENTORY ───────────────────────────────────────────────
CREATE TABLE inventory_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);
INSERT INTO inventory_categories (name) VALUES ('Grains'),('Proteins'),('Oils'),('Produce'),('Beverages'),('Utilities');

CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    qty DECIMAL(10,3) DEFAULT 0,
    min_qty DECIMAL(10,3) DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(id)
);

INSERT INTO inventory_items (category_id, name, unit, qty, min_qty, unit_cost) VALUES
(1,'Rice','kg',45,20,180),(2,'Chicken','kg',8,10,1100),(2,'Fish','kg',5,8,900),
(1,'Flour','kg',30,15,210),(3,'Coconut Oil','L',12,5,520),(4,'Vegetables','kg',6,10,150),
(5,'Tea Packets','packs',3,5,850),(6,'Gas Cylinders','cylinders',2,2,4500);

-- ─── STOCK PURCHASES ─────────────────────────────────────────
CREATE TABLE stock_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_date DATE NOT NULL,
    item_id INT NOT NULL,
    qty DECIMAL(10,3) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    supplier VARCHAR(150),
    invoice_no VARCHAR(50),
    payment_method ENUM('Cash','Card','Bank Transfer','Credit') DEFAULT 'Cash',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ─── EMPLOYEES ───────────────────────────────────────────────
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

INSERT INTO employees (name, nic, phone, position, basic_salary, allowances, joined_date) VALUES
('Kasun Perera','199012345678','+94771234567','Manager',65000,8000,'2022-01-01'),
('Nimali Silva','199534567890','+94772345678','Cashier',42000,5000,'2022-03-15'),
('Thilina Fernando','198890123456','+94773456789','Chef',55000,7000,'2021-06-01'),
('Chamari Jayawardena','200012345670','+94774567890','Waiter',35000,4000,'2023-01-10'),
('Ruwan Bandara','199678901234','+94775678901','Kitchen Helper',28000,3000,'2023-05-20');

-- ─── ATTENDANCE ───────────────────────────────────────────────
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
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    pay_month DATE NOT NULL COMMENT 'First day of the month',
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
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ─── DEBTORS ─────────────────────────────────────────────────
CREATE TABLE debtors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    credit_limit DECIMAL(10,2) DEFAULT 0,
    outstanding DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO debtors (name, phone, outstanding) VALUES
('ABC Company','+94112345678',28500),
('XYZ Office','+94113456789',12000),
('Colombo Hotel','+94114567890',67000),
('Mr. Pradeep','+94775678901',3500);

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

-- ─── BANKING ─────────────────────────────────────────────────
CREATE TABLE bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    account_no VARCHAR(50),
    balance DECIMAL(12,2) DEFAULT 0,
    active TINYINT(1) DEFAULT 1
);

INSERT INTO bank_accounts (bank_name, account_no, balance) VALUES
('Commercial Bank','****4521',285000),
('BOC','****8834',100000);

CREATE TABLE bank_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    txn_date DATE NOT NULL,
    type ENUM('deposit','withdrawal','transfer') DEFAULT 'deposit',
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES bank_accounts(id)
);

-- Sample bills for reports
INSERT INTO bills (bill_no, order_type, table_no, subtotal, service_charge, tax_amt, total, payment_method, status, created_at) VALUES
('B-001','Dine-In','T1',1200,120,105.6,1425.6,'Cash','settled',NOW()-INTERVAL 5 HOUR),
('B-002','Dine-In','T2',850,85,75.2,1010.2,'Card','settled',NOW()-INTERVAL 4 HOUR),
('B-003','Takeaway',NULL,640,0,51.2,691.2,'Cash','settled',NOW()-INTERVAL 3 HOUR),
('B-004','Uber Eats',NULL,1800,0,144,1944,'Uber Eats','settled',NOW()-INTERVAL 2 HOUR),
('B-005','Dine-In','T3',2200,220,193.6,2613.6,'Card','settled',NOW()-INTERVAL 1 HOUR);
