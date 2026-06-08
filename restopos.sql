-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 02:44 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `restopos`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `att_date` date NOT NULL,
  `status` enum('Present','Absent','Half Day','Leave') DEFAULT 'Present',
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `employee_id_ref` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `att_date`, `status`, `time_in`, `time_out`, `overtime_hours`, `notes`, `employee_id_ref`) VALUES
(1, 4, '2026-06-08', 'Present', '09:00:00', '23:00:00', 6.00, '', NULL),
(9, 6, '2026-06-08', 'Present', '09:04:00', '20:00:00', 0.00, '', NULL),
(14, 6, '2026-06-06', 'Present', '09:04:00', '21:00:00', 0.00, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_no` varchar(50) DEFAULT NULL,
  `balance` decimal(12,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_transactions`
--

CREATE TABLE `bank_transactions` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `txn_date` date NOT NULL,
  `type` enum('deposit','withdrawal','transfer') DEFAULT 'deposit',
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `bill_no` varchar(20) NOT NULL,
  `order_type` enum('Dine-In','Takeaway','Uber Eats','PickMe','Delivery') DEFAULT 'Dine-In',
  `table_no` varchar(10) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `service_charge` decimal(10,2) DEFAULT 0.00,
  `discount_pct` decimal(5,2) DEFAULT 0.00,
  `discount_amt` decimal(10,2) DEFAULT 0.00,
  `tax_amt` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('Cash','Card','Bank Transfer','QR','Credit','Uber Eats','PickMe') DEFAULT 'Cash',
  `cash_given` decimal(10,2) DEFAULT 0.00,
  `change_amt` decimal(10,2) DEFAULT 0.00,
  `status` enum('open','settled','voided') DEFAULT 'open',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debtors`
--

CREATE TABLE `debtors` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `outstanding` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `debtors`
--

INSERT INTO `debtors` (`id`, `name`, `phone`, `email`, `address`, `credit_limit`, `outstanding`, `created_at`) VALUES
(5, 'Aski', '', '', NULL, 10000.00, 0.00, '2026-06-08 12:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `debtor_payments`
--

CREATE TABLE `debtor_payments` (
  `id` int(11) NOT NULL,
  `debtor_id` int(11) NOT NULL,
  `bill_id` int(11) DEFAULT NULL,
  `txn_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('charge','payment') DEFAULT 'charge',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `nic` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `basic_salary` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `epf_applicable` tinyint(1) DEFAULT 1,
  `joined_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `nic`, `phone`, `position`, `basic_salary`, `allowances`, `epf_applicable`, `joined_date`, `active`, `created_at`) VALUES
(1, '[DEL] Kasun Perera', '199012345678', '+94771234567', 'Manager', 65000.00, 8000.00, 1, '2022-01-01', 0, '2026-06-08 09:44:36'),
(2, '[DEL] Nimali Silva', '199534567890', '+94772345678', 'Cashier', 42000.00, 5000.00, 1, '2022-03-15', 0, '2026-06-08 09:44:36'),
(3, '[DEL] Thilina Fernando', '198890123456', '+94773456789', 'Chef', 55000.00, 7000.00, 1, '2021-06-01', 0, '2026-06-08 09:44:36'),
(4, '[DEL] Chamari Jayawardena', '200012345670', '+94774567890', 'Waiter', 35000.00, 4000.00, 1, '2023-01-10', 0, '2026-06-08 09:44:36'),
(5, '[DEL] Ruwan Bandara', '199678901234', '+94775678901', 'Kitchen Helper', 28000.00, 3000.00, 1, '2023-05-20', 0, '2026-06-08 09:44:36'),
(6, 'Aski Ahamed', '991215982V', '0779930640', 'Accountant', 50000.00, 0.00, 0, '2026-06-08', 1, '2026-06-08 10:26:40');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Card','Bank Transfer') DEFAULT 'Cash',
  `supplier` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`) VALUES
(1, 'Utilities'),
(2, 'Rent'),
(3, 'Purchases'),
(4, 'Transport'),
(5, 'Maintenance'),
(6, 'Marketing'),
(7, 'Salaries'),
(8, 'Miscellaneous');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`) VALUES
(1, 'Grains'),
(2, 'Proteins'),
(3, 'Oils'),
(4, 'Produce'),
(5, 'Beverages'),
(6, 'Utilities');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `unit` varchar(20) DEFAULT 'kg',
  `qty` decimal(10,3) DEFAULT 0.000,
  `min_qty` decimal(10,3) DEFAULT 0.000,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

CREATE TABLE `menu_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`id`, `name`, `sort_order`, `active`) VALUES
(1, 'Rice Meals', 1, 1),
(2, 'Kottu', 2, 1),
(3, 'Noodles', 3, 1),
(4, 'Beverages', 4, 1),
(5, 'Snacks', 5, 1),
(6, 'Desserts', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `price`, `description`, `active`, `created_at`) VALUES
(1, 1, 'Chicken Rice', 450.00, NULL, 1, '2026-06-08 09:44:33'),
(2, 1, 'Fish Curry Rice', 500.00, NULL, 1, '2026-06-08 09:44:33'),
(3, 1, 'Veggie Rice', 350.00, NULL, 1, '2026-06-08 09:44:33'),
(4, 1, 'Fried Rice', 480.00, NULL, 1, '2026-06-08 09:44:33'),
(5, 2, 'Veggie Kottu', 380.00, NULL, 1, '2026-06-08 09:44:33'),
(6, 2, 'Egg Kottu', 420.00, NULL, 1, '2026-06-08 09:44:33'),
(7, 2, 'Chicken Kottu', 520.00, NULL, 1, '2026-06-08 09:44:33'),
(8, 2, 'Prawn Kottu', 680.00, NULL, 1, '2026-06-08 09:44:33'),
(9, 2, 'Cheese Kottu', 580.00, NULL, 1, '2026-06-08 09:44:33'),
(10, 3, 'Veg Noodles', 380.00, NULL, 1, '2026-06-08 09:44:33'),
(11, 3, 'Chicken Noodles', 480.00, NULL, 1, '2026-06-08 09:44:33'),
(12, 4, 'Plain Tea', 80.00, NULL, 1, '2026-06-08 09:44:33'),
(13, 4, 'Milk Tea', 120.00, NULL, 1, '2026-06-08 09:44:33'),
(14, 4, 'Mango Juice', 200.00, NULL, 1, '2026-06-08 09:44:33'),
(15, 4, 'Soft Drink', 150.00, NULL, 1, '2026-06-08 09:44:33'),
(16, 4, 'Water Bottle', 80.00, NULL, 1, '2026-06-08 09:44:33'),
(17, 5, 'Chicken Sandwich', 280.00, NULL, 1, '2026-06-08 09:44:33'),
(18, 5, 'Samosa', 60.00, NULL, 1, '2026-06-08 09:44:33'),
(19, 5, 'Garlic Bread', 150.00, NULL, 1, '2026-06-08 09:44:33'),
(20, 5, 'Short Eats', 45.00, NULL, 1, '2026-06-08 09:44:33');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_month` date NOT NULL COMMENT 'First day of the month',
  `basic_salary` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `gross_salary` decimal(10,2) DEFAULT 0.00,
  `salary_advance` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `epf_employee` decimal(10,2) DEFAULT 0.00,
  `epf_employer` decimal(10,2) DEFAULT 0.00,
  `etf_employer` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','approved','paid') DEFAULT 'draft',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'business_name', 'Cove Restaurant', '2026-06-08 12:24:48'),
(2, 'address', 'Colombo 07, Sri Lanka', '2026-06-08 09:44:33'),
(3, 'phone', '+94 11 234 5678', '2026-06-08 09:44:33'),
(4, 'email', 'info@myrestaurant.lk', '2026-06-08 09:44:33'),
(5, 'service_charge_pct', '10', '2026-06-08 09:44:33'),
(6, 'tax_pct', '8', '2026-06-08 09:44:33'),
(7, 'ubereats_commission', '30', '2026-06-08 09:44:33'),
(8, 'pickme_commission', '28', '2026-06-08 09:44:33'),
(9, 'currency', 'Rs.', '2026-06-08 09:44:33');

-- --------------------------------------------------------

--
-- Table structure for table `stock_purchases`
--

CREATE TABLE `stock_purchases` (
  `id` int(11) NOT NULL,
  `purchase_date` date NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty` decimal(10,3) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `payment_method` enum('Cash','Card','Bank Transfer','Credit') DEFAULT 'Cash',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','cashier') DEFAULT 'cashier',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `active`, `created_at`) VALUES
(1, 'Administrator', 'admin', 'admin123', 'admin', 1, '2026-06-08 09:44:32'),
(4, 'Aski Ahamed', 'aski', '246912', 'admin', 1, '2026-06-08 12:21:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_emp_date` (`employee_id`,`att_date`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bill_no` (`bill_no`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `debtors`
--
ALTER TABLE `debtors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `debtor_payments`
--
ALTER TABLE `debtor_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `debtor_id` (`debtor_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_purchases`
--
ALTER TABLE `stock_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `debtors`
--
ALTER TABLE `debtors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `debtor_payments`
--
ALTER TABLE `debtor_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `stock_purchases`
--
ALTER TABLE `stock_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `bank_accounts` (`id`);

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bill_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);

--
-- Constraints for table `debtor_payments`
--
ALTER TABLE `debtor_payments`
  ADD CONSTRAINT `debtor_payments_ibfk_1` FOREIGN KEY (`debtor_id`) REFERENCES `debtors` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`);

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `stock_purchases`
--
ALTER TABLE `stock_purchases`
  ADD CONSTRAINT `stock_purchases_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `stock_purchases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
