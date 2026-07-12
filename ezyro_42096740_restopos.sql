-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql107.byetcluster.com
-- Generation Time: Jul 12, 2026 at 03:10 AM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ezyro_42096740_restopos`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `user_name`, `action`, `module`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'Administrator', 'Logged In', 'auth', 'Administrator (admin) signed in', '::1', '2026-06-23 03:49:09'),
(2, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #1 → confirmed', '::1', '2026-06-23 03:50:45'),
(3, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #1 → preparing', '::1', '2026-06-23 03:51:11'),
(4, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #1 → ready', '::1', '2026-06-23 03:51:13'),
(5, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #1 → completed', '::1', '2026-06-23 03:51:14'),
(6, 1, 'Administrator', 'Settled Bill', 'pos', 'Bill B-20260623-0001 — Rs. 950.40 via Cash', '::1', '2026-06-23 03:51:59'),
(7, 1, 'Administrator', 'Logged In', 'auth', 'Administrator (admin) signed in', '::1', '2026-06-23 03:58:43'),
(8, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #2 → confirmed', '::1', '2026-06-23 03:59:25'),
(9, 1, 'Administrator', 'Auto-Created Bill', 'online_orders', 'Bill ONL-20260623-0002 (Cash) created from online order ONL-20260623-3885', '::1', '2026-06-23 03:59:25'),
(10, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #2 → preparing', '::1', '2026-06-23 03:59:26'),
(11, 1, 'Administrator', 'Deleted Bill', 'bills', 'Bill B-20260623-0001 permanently deleted', '::1', '2026-06-23 04:00:00'),
(12, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #2 → ready', '::1', '2026-06-23 04:01:43'),
(13, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #2 → completed', '::1', '2026-06-23 04:01:44'),
(14, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #3 → confirmed', '::1', '2026-06-23 04:06:03'),
(15, 1, 'Administrator', 'Auto-Created Bill', 'online_orders', 'Bill ONL-20260623-0003 (Card) created from online order ONL-20260623-3258', '::1', '2026-06-23 04:06:04'),
(16, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #3 → preparing', '::1', '2026-06-23 04:06:04'),
(17, 1, 'Administrator', 'Logged In', 'auth', 'Administrator (admin) signed in', '::1', '2026-06-23 07:53:38'),
(18, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #4 → confirmed', '::1', '2026-06-23 07:54:35'),
(19, 1, 'Administrator', 'Auto-Created Bill', 'online_orders', 'Bill ONL-20260623-0004 (Cash) created from online order ONL-20260623-2181', '::1', '2026-06-23 07:54:35'),
(20, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #4 → preparing', '::1', '2026-06-23 07:54:37'),
(21, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #4 → ready', '::1', '2026-06-23 07:54:52'),
(22, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #3 → ready', '::1', '2026-06-23 07:54:53'),
(23, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #4 → completed', '::1', '2026-06-23 07:54:54'),
(24, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #3 → completed', '::1', '2026-06-23 07:54:55'),
(25, 1, 'Administrator', 'Deleted Bill', 'bills', 'Bill ONL-20260623-0004 permanently deleted', '::1', '2026-06-23 07:55:08'),
(26, 1, 'Administrator', 'Deleted Bill', 'bills', 'Bill ONL-20260623-0003 permanently deleted', '::1', '2026-06-23 07:55:11'),
(27, 1, 'Administrator', 'Deleted Bill', 'bills', 'Bill ONL-20260623-0002 permanently deleted', '::1', '2026-06-23 07:55:13'),
(28, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #5 → cancelled', '::1', '2026-06-23 08:03:18'),
(29, 1, 'Administrator', 'Logged In', 'auth', 'Administrator (admin) signed in', '::1', '2026-06-23 08:13:33'),
(30, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-4184 deleted', '::1', '2026-06-23 08:16:08'),
(31, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-2181 deleted', '::1', '2026-06-23 08:16:10'),
(32, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-3258 deleted', '::1', '2026-06-23 08:16:13'),
(33, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-3885 deleted', '::1', '2026-06-23 08:16:15'),
(34, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-9400 deleted', '::1', '2026-06-23 08:16:17'),
(35, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #6 → confirmed', '::1', '2026-06-23 08:16:18'),
(36, 1, 'Administrator', 'Auto-Created Bill', 'online_orders', 'Bill ONL-20260623-0006 (Cash) from ONL-20260623-3424', '::1', '2026-06-23 08:16:18'),
(37, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #6 → preparing', '::1', '2026-06-23 08:16:20'),
(38, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #6 → ready', '::1', '2026-06-23 08:16:22'),
(39, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #6 → completed', '::1', '2026-06-23 08:16:23'),
(40, 1, 'Administrator', 'Logged In', 'auth', 'Administrator (admin) signed in', '::1', '2026-06-23 08:36:51'),
(41, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-3424 deleted', '::1', '2026-06-23 08:38:47'),
(42, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #7 → confirmed', '::1', '2026-06-23 08:41:42'),
(43, 1, 'Administrator', 'Auto-Created Bill', 'online_orders', 'Bill ONL-20260623-0007 (Cash) from ONL-20260623-8385', '::1', '2026-06-23 08:41:42'),
(44, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #7 → preparing', '::1', '2026-06-23 08:41:44'),
(45, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #7 → ready', '::1', '2026-06-23 08:41:45'),
(46, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #7 → completed', '::1', '2026-06-23 08:41:47'),
(47, 1, 'Administrator', 'Logged Out', 'auth', 'Administrator signed out', '::1', '2026-06-23 08:46:15'),
(48, 3, 'Cashier', 'Logged In', 'auth', 'Cashier (cashier) signed in', '::1', '2026-06-23 08:46:21'),
(49, 3, 'Cashier', 'Logged Out', 'auth', 'Cashier signed out', '::1', '2026-06-23 08:46:40'),
(50, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '::1', '2026-06-23 08:46:47'),
(51, 1, 'Administrator', 'Logged In', 'auth', 'Administrator (admin) signed in', '::1', '2026-06-23 08:51:05'),
(52, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-9876 deleted', '::1', '2026-06-23 08:52:13'),
(53, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-8385 deleted', '::1', '2026-06-23 08:52:16'),
(54, 1, 'Administrator', 'Deleted Reservation', 'reservations', 'Reservation for Ahmed Azim', '::1', '2026-06-23 08:53:42'),
(55, 1, 'Administrator', 'Deleted Reservation', 'reservations', 'Reservation for Ahmed Azim', '::1', '2026-06-23 08:53:44'),
(56, 1, 'Administrator', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-5228 deleted', '::1', '2026-06-23 08:53:48'),
(57, 1, 'Administrator', 'Logged In', 'auth', 'Administrator (admin) signed in', '124.43.13.81', '2026-06-23 08:58:21'),
(58, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.215.116', '2026-06-23 08:59:02'),
(59, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #10 → confirmed', '124.43.13.81', '2026-06-23 09:01:40'),
(60, 1, 'Administrator', 'Auto-Created Bill', 'online_orders', 'Bill ONL-20260623-0010 (Cash) from ONL-20260623-2945', '124.43.13.81', '2026-06-23 09:01:40'),
(61, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #10 → preparing', '124.43.13.81', '2026-06-23 09:01:41'),
(62, 1, 'Administrator', 'Updated Online Order', 'online_orders', 'Order #10 → ready', '124.43.13.81', '2026-06-23 09:01:42'),
(63, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.215.116', '2026-06-23 09:02:32'),
(64, 5, 'Aski Ahamed', 'Updated Online Order', 'online_orders', 'Order #10 → completed', '175.157.215.116', '2026-06-23 09:03:37'),
(65, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-06-23 10:42:09'),
(66, 5, 'Aski Ahamed', 'Updated Online Order', 'online_orders', 'Order #11 → confirmed', '124.43.13.81', '2026-06-23 10:43:25'),
(67, 5, 'Aski Ahamed', 'Auto-Created Bill', 'online_orders', 'Bill ONL-20260623-0011 (Cash) from ONL-20260623-4021', '124.43.13.81', '2026-06-23 10:43:25'),
(68, 5, 'Aski Ahamed', 'Updated Online Order', 'online_orders', 'Order #11 → preparing', '124.43.13.81', '2026-06-23 10:43:28'),
(69, 5, 'Aski Ahamed', 'Updated Online Order', 'online_orders', 'Order #11 → ready', '124.43.13.81', '2026-06-23 10:43:29'),
(70, 5, 'Aski Ahamed', 'Updated Online Order', 'online_orders', 'Order #11 → completed', '124.43.13.81', '2026-06-23 10:43:31'),
(71, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-06-23 10:58:59'),
(72, 5, 'Aski Ahamed', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-4710 deleted', '124.43.13.81', '2026-06-23 10:59:39'),
(73, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '112.134.219.227', '2026-06-23 11:38:03'),
(74, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '111.223.161.233', '2026-06-23 11:38:15'),
(75, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.219.227', '2026-06-23 11:39:47'),
(76, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for Zainab Hassan on 2026-06-27 17:00 – 20:00', '112.134.219.227', '2026-06-23 11:57:13'),
(77, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.215.49', '2026-06-23 16:44:56'),
(78, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-06-25 03:32:58'),
(79, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-06-25 03:46:42'),
(80, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-06-25 03:51:14'),
(81, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-06-25 06:44:00'),
(82, 5, 'Aski Ahamed', 'Deleted Reservation', 'reservations', 'Reservation for Trail', '124.43.13.81', '2026-06-25 07:10:37'),
(83, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-06-25 07:22:36'),
(84, 5, 'Aski Ahamed', 'Deleted Reservation', 'reservations', 'Reservation for aski', '124.43.13.81', '2026-06-25 07:23:38'),
(85, 5, 'Aski Ahamed', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-4021 deleted', '124.43.13.81', '2026-06-25 07:27:01'),
(86, 5, 'Aski Ahamed', 'Deleted Online Order', 'online_orders', 'Order ONL-20260623-2945 deleted', '124.43.13.81', '2026-06-25 07:27:04'),
(87, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.219.164', '2026-06-25 10:51:39'),
(88, 6, 'Ragee', 'Updated Reservation', 'reservations', 'Reservation #4 for SARA', '112.134.219.164', '2026-06-25 10:54:04'),
(89, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.222.122', '2026-06-25 15:52:01'),
(90, 5, 'Aski Ahamed', 'Deleted Online Order', 'online_orders', 'Order ONL-20260625-2161 deleted', '175.157.222.122', '2026-06-25 15:53:03'),
(91, 5, 'Aski Ahamed', 'Deleted Reservation', 'reservations', 'Reservation for aski trail', '175.157.222.122', '2026-06-25 15:54:01'),
(92, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.218.185', '2026-06-25 17:31:57'),
(93, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.219.227', '2026-06-25 17:32:49'),
(94, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for DILASNI on 2026-06-26 12:00 – 15:00', '112.134.219.164', '2026-06-26 05:05:21'),
(95, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.217.22', '2026-06-26 19:02:36'),
(96, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for AMANDA NUGARA on 2026-06-27 20:15 – 22:15', '112.134.217.22', '2026-06-26 19:07:48'),
(97, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for DULMIN DHARMA on 2026-06-29 13:00 – 16:00', '112.134.217.22', '2026-06-26 19:16:28'),
(98, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for ALFAN on 2026-06-27 10:00 – 12:00', '112.134.217.22', '2026-06-26 19:19:02'),
(99, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for PRASANA on 2026-06-28 14:00 – 19:00', '112.134.217.22', '2026-06-26 19:23:57'),
(100, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for YEHANSA on 2026-06-28 14:00 – 16:00', '112.134.217.22', '2026-06-26 19:26:14'),
(101, 6, 'Ragee', 'Deleted Reservation', 'reservations', 'Reservation for Ragee', '112.134.217.22', '2026-06-26 19:26:45'),
(102, 6, 'Ragee', 'Deleted Reservation', 'reservations', 'Reservation for Aski Ahamed', '112.134.217.22', '2026-06-26 19:26:55'),
(103, 6, 'Ragee', 'Updated Reservation', 'reservations', 'Reservation #12 for DULMIN DHARMA', '112.134.217.22', '2026-06-26 19:29:29'),
(104, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.219.227', '2026-06-27 05:17:21'),
(105, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for Madu on 2026-06-28 16:15 – 07:15', '112.134.219.227', '2026-06-27 05:23:09'),
(106, 6, 'Ragee', 'Updated Reservation', 'reservations', 'Reservation #15 for YEHANSA', '175.157.45.185', '2026-06-27 05:32:37'),
(107, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.45.185', '2026-06-27 08:11:55'),
(108, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for Haroon on 2026-06-27 15:00 – 17:00', '175.157.45.185', '2026-06-27 08:19:01'),
(109, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for Haroon on 2026-06-27 15:00 – 17:00', '175.157.45.185', '2026-06-27 08:28:36'),
(110, 6, 'Ragee', 'Deleted Reservation', 'reservations', 'Reservation for Haroon', '175.157.45.185', '2026-06-27 08:28:52'),
(111, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for RAIHAN on 2026-06-28 16:00 – 18:00', '175.157.45.185', '2026-06-27 08:59:21'),
(112, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for Raihan on 2026-06-27 15:00 – 16:00', '175.157.45.185', '2026-06-27 09:02:22'),
(113, 6, 'Ragee', 'Updated Reservation', 'reservations', 'Reservation #20 for Raihan', '175.157.45.185', '2026-06-27 09:03:47'),
(114, 6, 'Ragee', 'Updated Reservation', 'reservations', 'Reservation #19 for RAIHAN', '175.157.45.185', '2026-06-27 09:05:07'),
(115, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for STELLA RACHEL on 2026-06-27 19:00 – 22:00', '175.157.45.185', '2026-06-27 10:08:03'),
(116, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.45.185', '2026-06-27 10:47:17'),
(117, 6, 'Ragee', 'Updated Reservation', 'reservations', 'Reservation #21 for STELLA RACHEL', '175.157.45.185', '2026-06-27 10:48:46'),
(118, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.219.166', '2026-06-27 15:59:18'),
(119, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for HASSAN MOHIDEEN on 2026-07-05 19:30 – 22:30', '112.134.219.166', '2026-06-27 16:00:57'),
(120, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-06-28 03:33:28'),
(121, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-06-29 03:16:27'),
(122, 6, 'Ragee', 'Deleted Reservation', 'reservations', 'Reservation for HASSAN MOHIDEEN', '112.134.220.225', '2026-06-29 03:17:02'),
(123, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '111.223.177.213', '2026-07-02 06:26:32'),
(124, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for NUZKI on 2026-07-02 19:00 – 00:00', '111.223.177.213', '2026-07-02 06:35:16'),
(125, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.153.40', '2026-07-02 18:14:05'),
(126, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.153.40', '2026-07-02 18:14:05'),
(127, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-03 07:52:24'),
(128, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for Nuzki on 2026-07-03 19:00 – 00:00', '112.134.220.225', '2026-07-03 07:57:24'),
(129, 6, 'Ragee', 'Deleted Reservation', 'reservations', 'Reservation for NUZKI', '112.134.220.225', '2026-07-03 07:57:51'),
(130, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for SHANSHANA on 2026-07-09 19:00 – 21:00', '175.157.254.84', '2026-07-03 13:07:43'),
(131, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.219.166', '2026-07-03 17:23:43'),
(132, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.36.142', '2026-07-03 21:47:27'),
(133, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.36.142', '2026-07-03 21:47:29'),
(134, 5, 'Aski Ahamed', 'Logged Out', 'auth', 'Aski Ahamed signed out', '175.157.36.142', '2026-07-03 21:49:05'),
(135, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-04 11:07:20'),
(136, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for SARA on 2026-07-04 16:30 – 17:30', '112.134.220.225', '2026-07-04 11:10:12'),
(137, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '112.134.189.22', '2026-07-04 18:28:28'),
(138, 5, 'Aski Ahamed', 'Logged Out', 'auth', 'Aski Ahamed signed out', '112.134.189.22', '2026-07-04 18:34:50'),
(139, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-05 08:26:17'),
(140, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for DHINOSHA on 2026-07-05 14:00 – 17:00', '112.134.221.151', '2026-07-05 08:38:25'),
(141, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for KUMUTH on 2026-07-05 14:00 – 16:00', '112.134.221.151', '2026-07-05 08:40:06'),
(142, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.216.34', '2026-07-05 08:47:01'),
(143, 5, 'Aski Ahamed', 'Logged Out', 'auth', 'Aski Ahamed signed out', '175.157.216.34', '2026-07-05 09:03:29'),
(144, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-05 11:20:21'),
(145, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260705-0001 — Rs. 415.80 via Cash', '112.134.221.151', '2026-07-05 11:59:49'),
(146, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.208.198', '2026-07-05 12:04:37'),
(147, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.208.198', '2026-07-05 12:04:38'),
(148, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0001 permanently deleted', '112.134.221.151', '2026-07-05 12:10:34'),
(149, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-05 12:11:19'),
(150, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-05 12:11:57'),
(151, 5, 'Aski Ahamed', 'Settled Bill', 'pos', 'Bill B-20260705-0001 — Rs. 385.00 via Cash', '175.157.208.198', '2026-07-05 12:12:07'),
(152, 5, 'Aski Ahamed', 'Logged Out', 'auth', 'Aski Ahamed signed out', '175.157.208.198', '2026-07-05 12:12:26'),
(153, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.208.198', '2026-07-05 12:12:48'),
(154, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260705-0002 — Rs. 1,650.00 via Cash', '175.157.208.198', '2026-07-05 12:13:50'),
(155, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260705-0003 — Rs. 3,564.00 via Cash', '112.134.221.151', '2026-07-05 12:19:51'),
(156, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0003 permanently deleted', '112.134.221.151', '2026-07-05 12:21:37'),
(157, 6, 'Ragee', 'Logged Out', 'auth', 'Ragee signed out', '175.157.208.198', '2026-07-05 12:21:56'),
(158, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260705-0003 — Rs. 5,785.56 via Cash', '112.134.221.151', '2026-07-05 17:47:36'),
(159, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0003 permanently deleted', '112.134.221.151', '2026-07-05 17:49:36'),
(160, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0002 permanently deleted', '112.134.221.151', '2026-07-05 17:49:43'),
(161, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0001 permanently deleted', '112.134.221.151', '2026-07-05 17:49:47'),
(162, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-05 17:50:08'),
(163, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260705-0001 — Rs. 5,785.56 via Cash', '112.134.221.151', '2026-07-05 17:55:02'),
(164, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260705-0002 — Rs. 5,286.60 via Cash', '112.134.221.151', '2026-07-05 17:59:08'),
(165, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260705-0003 — Rs. 831.60 via Cash', '112.134.221.151', '2026-07-05 17:59:49'),
(166, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260705-0004 — Rs. 5,346.00 via Cash', '112.134.221.151', '2026-07-05 18:01:04'),
(167, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-06 04:45:10'),
(168, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.184.141', '2026-07-06 13:01:29'),
(169, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-06 16:48:55'),
(170, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '112.134.221.151', '2026-07-06 17:01:55'),
(171, 5, 'Aski Ahamed', 'Deleted Reservation', 'reservations', 'Reservation for SHANSHANA', '112.134.221.151', '2026-07-06 17:21:45'),
(172, 5, 'Aski Ahamed', 'Created Reservation', 'reservations', 'Reservation for GIHAN on 2026-07-06 16:00 – 18:00', '112.134.221.151', '2026-07-06 17:24:18'),
(173, 5, 'Aski Ahamed', 'Created Reservation', 'reservations', 'Reservation for ANUSHI on 2026-07-06 19:00 – 21:30', '112.134.221.151', '2026-07-06 17:27:54'),
(174, 5, 'Aski Ahamed', 'Created Reservation', 'reservations', 'Reservation for Hiru on 2026-07-05 16:00 – 16:30', '112.134.221.151', '2026-07-06 17:30:37'),
(175, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.217.234', '2026-07-06 23:55:36'),
(176, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.217.234', '2026-07-06 23:55:37'),
(177, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '124.43.13.81', '2026-07-07 04:33:43'),
(178, 6, 'Ragee', 'Logged Out', 'auth', 'Ragee signed out', '124.43.13.81', '2026-07-07 04:34:54'),
(179, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.14.168', '2026-07-07 05:43:51'),
(180, 6, 'Ragee', 'Logged Out', 'auth', 'Ragee signed out', '175.157.14.168', '2026-07-07 05:45:50'),
(181, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.14.168', '2026-07-07 05:45:59'),
(182, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0004 permanently deleted', '175.157.14.168', '2026-07-07 05:46:56'),
(183, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0003 permanently deleted', '175.157.14.168', '2026-07-07 05:46:58'),
(184, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0002 permanently deleted', '175.157.14.168', '2026-07-07 05:47:00'),
(185, 6, 'Ragee', 'Deleted Bill', 'bills', 'Bill B-20260705-0001 permanently deleted', '175.157.14.168', '2026-07-07 05:47:01'),
(186, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '175.157.14.168', '2026-07-07 05:49:38'),
(187, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '124.43.13.81', '2026-07-07 09:24:04'),
(188, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-07-07 09:29:15'),
(189, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-07 10:53:43'),
(190, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-07 10:58:11'),
(191, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for Hiru on 2026-07-05 16:00 – 16:30', '112.134.220.225', '2026-07-07 10:58:22'),
(192, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-07 11:01:50'),
(193, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-07 17:06:32'),
(194, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-07-08 04:33:44'),
(195, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-07-08 04:36:02'),
(196, 5, 'Aski Ahamed', 'Logged Out', 'auth', 'Aski Ahamed signed out', '124.43.13.81', '2026-07-08 04:49:10'),
(197, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-08 09:02:44'),
(198, 6, 'Ragee', 'Created Reservation', 'reservations', 'Reservation for MINHAR AZEEZ on 2026-07-08 21:30 – 00:30', '112.134.220.225', '2026-07-08 09:05:52'),
(199, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-07-08 10:08:00'),
(200, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '124.43.13.81', '2026-07-08 10:18:06'),
(201, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-08 12:10:25'),
(202, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-08 13:10:05'),
(203, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.35.221', '2026-07-08 13:16:22'),
(204, 5, 'Aski Ahamed', 'Logged Out', 'auth', 'Aski Ahamed signed out', '175.157.35.221', '2026-07-08 13:17:50'),
(205, 5, 'Aski Ahamed', 'Logged In', 'auth', 'Aski Ahamed (admin) signed in', '175.157.35.221', '2026-07-08 13:18:41'),
(206, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-08 16:11:50'),
(207, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260708-0001 — Rs. 5,115.00 via Cash', '112.134.221.151', '2026-07-08 17:52:24'),
(208, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-10 06:00:53'),
(209, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-10 07:13:02'),
(210, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.220.225', '2026-07-10 14:45:20'),
(211, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-10 16:15:17'),
(212, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260710-0001 — Rs. 18,315.00 via Cash', '112.134.221.151', '2026-07-10 16:27:23'),
(213, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260710-0002 — Rs. 385.00 via Cash', '112.134.221.151', '2026-07-10 17:15:40'),
(214, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260710-0003 — Rs. 3,190.00 via Cash', '112.134.221.151', '2026-07-10 17:19:53'),
(215, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260710-0004 — Rs. 715.00 via Cash', '112.134.221.151', '2026-07-10 17:21:27'),
(216, 6, 'Ragee', 'Edited Bill', 'bills', 'Bill ID 22 details updated', '112.134.221.151', '2026-07-10 17:22:28'),
(217, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260710-0005 — Rs. 2,640.00 via Card', '112.134.221.151', '2026-07-10 17:24:49'),
(218, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260710-0006 — Rs. 11,000.00 via Card', '112.134.221.151', '2026-07-10 18:01:34'),
(219, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-11 09:27:55'),
(220, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-11 16:38:13'),
(221, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260711-0001 — Rs. 4,620.00 via Card', '112.134.221.151', '2026-07-11 16:40:21'),
(222, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260711-0002 — Rs. 3,000.00 via Card', '112.134.221.151', '2026-07-11 16:41:12'),
(223, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260711-0003 — Rs. 1,980.00 via Card', '112.134.221.151', '2026-07-11 16:44:00'),
(224, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260711-0004 — Rs. 9,295.00 via Card', '175.157.88.220', '2026-07-11 17:54:39'),
(225, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260711-0005 — Rs. 3,500.00 via Cash', '175.157.88.220', '2026-07-11 17:55:46'),
(226, 6, 'Ragee', 'Edited Bill', 'bills', 'Bill ID 29 details updated', '175.157.88.220', '2026-07-11 17:57:29'),
(227, 6, 'Ragee', 'Logged In', 'auth', 'Ragee (admin) signed in', '112.134.221.151', '2026-07-11 18:13:40'),
(228, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260711-0006 — Rs. 13,200.00 via Card', '112.134.221.151', '2026-07-11 18:17:14'),
(229, 6, 'Ragee', 'Settled Bill', 'pos', 'Bill B-20260711-0007 — Rs. 9,460.00 via Card', '112.134.221.151', '2026-07-11 18:19:32');

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
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `att_date`, `status`, `time_in`, `time_out`, `overtime_hours`, `notes`) VALUES
(1, 4, '2026-06-23', 'Present', '09:00:00', '22:00:00', '0.00', ''),
(2, 4, '2026-06-05', 'Absent', '09:00:00', '22:00:00', '0.00', ''),
(3, 4, '2026-06-22', 'Present', '09:00:00', '22:00:00', '4.00', NULL),
(4, 1, '2026-06-22', 'Present', '09:09:00', '20:00:00', '1.75', NULL),
(5, 1, '2026-06-23', 'Present', '09:05:00', '20:00:00', '1.92', ''),
(6, 6, '2026-07-03', 'Present', '15:00:00', '00:00:00', '0.00', ''),
(10, 8, '2026-07-03', 'Absent', '12:00:00', '21:00:00', '0.00', ''),
(15, 9, '2026-07-03', 'Present', '08:00:00', '17:00:00', '0.00', NULL),
(20, 10, '2026-07-03', 'Present', '10:40:00', '19:40:00', '0.00', NULL),
(23, 7, '2026-07-03', 'Present', '15:10:00', '00:00:00', '0.00', NULL),
(27, 13, '2026-07-03', 'Present', '08:00:00', '00:00:00', '0.00', NULL),
(30, 9, '2026-07-07', 'Leave', NULL, NULL, '0.00', ''),
(31, 10, '2026-07-07', 'Present', '16:15:00', '00:00:00', '0.00', ''),
(32, 6, '2026-07-07', 'Present', '08:00:00', '00:00:00', '7.00', ''),
(33, 13, '2026-07-07', 'Present', '09:30:00', '17:10:00', '0.00', NULL),
(34, 3, '2026-07-07', 'Present', '07:00:00', '09:00:00', '0.00', ''),
(35, 8, '2026-07-07', 'Absent', NULL, NULL, '0.00', ''),
(36, 7, '2026-07-07', 'Present', '08:00:00', '12:30:00', '30.00', ''),
(45, 9, '2026-07-08', 'Present', '16:30:00', '00:00:00', '0.00', NULL),
(46, 10, '2026-07-08', 'Present', '09:30:00', '00:00:00', '0.00', NULL),
(47, 6, '2026-07-08', 'Present', '08:00:00', '00:00:00', '0.00', NULL),
(48, 13, '2026-07-08', 'Present', '09:30:00', '17:30:00', '0.00', NULL),
(49, 3, '2026-07-08', 'Absent', NULL, NULL, '0.00', NULL),
(50, 8, '2026-07-08', 'Absent', NULL, NULL, '0.00', NULL),
(51, 7, '2026-07-08', 'Present', '15:00:00', '12:00:00', '0.00', NULL),
(59, 9, '2026-07-04', 'Present', '12:00:00', '21:00:00', '0.00', ''),
(60, 10, '2026-07-04', 'Present', '17:40:00', '00:00:00', '0.00', NULL),
(61, 6, '2026-07-04', 'Present', '15:00:00', '00:00:00', '0.00', NULL),
(62, 13, '2026-07-04', 'Present', '08:00:00', '18:20:00', '1.33', ''),
(63, 3, '2026-07-04', 'Present', '19:00:00', '09:00:00', '0.00', NULL),
(64, 8, '2026-07-04', 'Present', '08:10:00', '17:15:00', '0.08', NULL),
(65, 7, '2026-07-04', 'Leave', NULL, NULL, '0.00', NULL),
(71, 10, '2026-07-12', 'Present', '09:15:00', NULL, '0.00', NULL),
(72, 6, '2026-07-12', 'Present', '12:00:00', NULL, '0.00', NULL),
(73, 3, '2026-07-12', 'Present', '06:00:00', '08:30:00', '0.00', NULL),
(74, 8, '2026-07-12', 'Present', '08:00:00', NULL, '0.00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_no` varchar(50) DEFAULT NULL,
  `account_name` varchar(150) DEFAULT NULL,
  `balance` decimal(12,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_no`, `account_name`, `balance`, `active`, `created_at`) VALUES
(1, 'Commercial Bank', '1234567890', 'My Restaurant', '285000.00', 1, '2026-06-23 03:48:36'),
(2, 'BOC', '0987654321', 'My Restaurant', '100000.00', 1, '2026-06-23 03:48:36');

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
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `bill_no` varchar(30) NOT NULL,
  `order_type` enum('Dine-In','Takeaway','Uber Eats','PickMe','Delivery') DEFAULT 'Dine-In',
  `table_no` varchar(10) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `service_charge` decimal(10,2) DEFAULT 0.00,
  `discount_pct` decimal(5,2) DEFAULT 0.00,
  `discount_amt` decimal(10,2) DEFAULT 0.00,
  `tax_amt` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `cash_given` decimal(10,2) DEFAULT 0.00,
  `change_amt` decimal(10,2) DEFAULT 0.00,
  `status` enum('settled','voided','pending') DEFAULT 'settled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `bill_no`, `order_type`, `table_no`, `subtotal`, `service_charge`, `discount_pct`, `discount_amt`, `tax_amt`, `total`, `payment_method`, `cash_given`, `change_amt`, `status`, `notes`, `created_by`, `created_at`) VALUES
(5, 'ONL-20260623-0006', 'Takeaway', 'Online Ord', '950.00', '95.00', '0.00', '0.00', '83.60', '1128.60', 'Cash', '0.00', '0.00', 'settled', 'Online Order — Customer: aski | ONL-20260623-3424', 1, '2026-06-23 08:16:18'),
(6, 'ONL-20260623-0007', 'Takeaway', 'Online Ord', '950.00', '95.00', '0.00', '0.00', '83.60', '1128.60', 'Cash', '0.00', '0.00', 'settled', 'Online Order — Customer: aski | ONL-20260623-8385', 1, '2026-06-23 08:41:42'),
(7, 'ONL-20260623-0010', 'Takeaway', 'Online Ord', '950.00', '95.00', '0.00', '0.00', '83.60', '1128.60', 'Cash', '0.00', '0.00', 'settled', 'Online Order — Customer: Aski Ahamed | ONL-20260623-2945', 1, '2026-06-23 09:01:40'),
(8, 'ONL-20260623-0011', 'Takeaway', 'Online Ord', '680.00', '68.00', '0.00', '0.00', '59.84', '807.84', 'Cash', '0.00', '0.00', 'settled', 'Online Order — Customer: Athif | ONL-20260623-4021', 5, '2026-06-23 10:43:25'),
(18, 'B-20260708-0001', 'Dine-In', 'T1', '4650.00', '465.00', '0.00', '0.00', '0.00', '5115.00', 'Cash', '0.00', '0.00', 'settled', NULL, 6, '2026-07-09 06:22:23'),
(19, 'B-20260710-0001', 'Dine-In', 'T1', '16650.00', '1665.00', '0.00', '0.00', '0.00', '18315.00', 'Cash', '20000.00', '1685.00', 'settled', NULL, 6, '2026-07-11 04:57:23'),
(20, 'B-20260710-0002', 'Dine-In', 'T1', '350.00', '35.00', '0.00', '0.00', '0.00', '385.00', 'Cash', '400.00', '15.00', 'settled', NULL, 6, '2026-07-11 05:45:41'),
(21, 'B-20260710-0003', 'Dine-In', 'T1', '2900.00', '290.00', '0.00', '0.00', '0.00', '3190.00', 'Cash', '5000.00', '1810.00', 'settled', NULL, 6, '2026-07-11 05:49:54'),
(22, 'B-20260710-0004', 'Takeaway', 'T1', '650.00', '65.00', '0.00', '0.00', '0.00', '715.00', 'Cash', '650.00', '0.00', 'settled', '', 6, '2026-07-11 05:51:28'),
(23, 'B-20260710-0005', 'Dine-In', 'T1', '2400.00', '240.00', '0.00', '0.00', '0.00', '2640.00', 'Card', '0.00', '0.00', 'settled', NULL, 6, '2026-07-11 05:54:49'),
(24, 'B-20260710-0006', 'Dine-In', 'T1', '10000.00', '1000.00', '0.00', '0.00', '0.00', '11000.00', 'Card', '0.00', '0.00', 'settled', NULL, 6, '2026-07-11 06:31:34'),
(25, 'B-20260711-0001', 'Dine-In', 'T1', '4200.00', '420.00', '0.00', '0.00', '0.00', '4620.00', 'Card', '0.00', '0.00', 'settled', NULL, 6, '2026-07-12 05:10:21'),
(26, 'B-20260711-0002', 'Dine-In', 'T1', '3000.00', '300.00', '10.00', '300.00', '0.00', '3000.00', 'Card', '0.00', '0.00', 'settled', NULL, 6, '2026-07-12 05:11:12'),
(27, 'B-20260711-0003', 'Dine-In', 'T1', '1800.00', '180.00', '0.00', '0.00', '0.00', '1980.00', 'Card', '0.00', '0.00', 'settled', NULL, 6, '2026-07-12 05:14:00'),
(28, 'B-20260711-0004', 'Dine-In', 'T1', '8450.00', '845.00', '0.00', '0.00', '0.00', '9295.00', 'Card', '0.00', '0.00', 'settled', NULL, 6, '2026-07-12 06:24:39'),
(29, 'B-20260711-0005', 'Takeaway', 'T1', '3500.00', '350.00', '10.00', '350.00', '0.00', '3500.00', 'Cash', '0.00', '0.00', 'settled', '', 6, '2026-07-12 06:25:46'),
(30, 'B-20260711-0006', 'Dine-In', 'T1', '12000.00', '1200.00', '0.00', '0.00', '0.00', '13200.00', 'Card', '0.00', '0.00', 'settled', NULL, 6, '2026-07-12 06:47:14'),
(31, 'B-20260711-0007', 'Dine-In', 'T1', '8600.00', '860.00', '0.00', '0.00', '0.00', '9460.00', 'Card', '0.00', '0.00', 'settled', NULL, 6, '2026-07-12 06:49:32');

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `line_total` decimal(10,2) NOT NULL,
  `kitchen_status` enum('pending','preparing','ready','served') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`id`, `bill_id`, `menu_item_id`, `item_name`, `price`, `qty`, `line_total`, `kitchen_status`) VALUES
(5, 5, 1, 'Chicken Rice', '450.00', 1, '450.00', 'served'),
(6, 5, 2, 'Fish Curry Rice', '500.00', 1, '500.00', 'served'),
(7, 6, 1, 'Chicken Rice', '450.00', 1, '450.00', 'served'),
(8, 6, 2, 'Fish Curry Rice', '500.00', 1, '500.00', 'served'),
(9, 7, 1, 'Chicken Rice', '450.00', 1, '450.00', 'pending'),
(10, 7, 2, 'Fish Curry Rice', '500.00', 1, '500.00', 'pending'),
(11, 8, 11, 'Chicken Noodles', '480.00', 1, '480.00', 'pending'),
(12, 8, 14, 'Mango Juice', '200.00', 1, '200.00', 'pending'),
(29, 18, 165, 'Crispy chicken burger', '1650.00', 1, '1650.00', 'pending'),
(30, 18, 174, 'LOVE 696', '3000.00', 1, '3000.00', 'pending'),
(31, 19, 155, 'Coke 400ml', '350.00', 2, '700.00', 'pending'),
(32, 19, 113, 'Strawberry ', '1000.00', 1, '1000.00', 'pending'),
(33, 19, 200, 'Matcha Milkshake ', '1500.00', 1, '1500.00', 'pending'),
(34, 19, 161, 'Tandoori pizza', '3500.00', 1, '3500.00', 'pending'),
(35, 19, 163, 'Kottu 4 pcs', '4500.00', 1, '4500.00', 'pending'),
(36, 19, 77, 'Devilled Chicken ', '1800.00', 1, '1800.00', 'pending'),
(37, 19, 199, 'Chicken fried rice L', '2200.00', 1, '2200.00', 'pending'),
(38, 19, 157, 'Water 500ml', '100.00', 4, '400.00', 'pending'),
(39, 19, 54, 'Browni with Ice Cream ', '1050.00', 1, '1050.00', 'pending'),
(40, 20, 198, 'Soda', '350.00', 1, '350.00', 'pending'),
(41, 21, 77, 'Devilled Chicken ', '1800.00', 1, '1800.00', 'pending'),
(42, 21, 88, 'Devilled wings', '1100.00', 1, '1100.00', 'pending'),
(43, 22, 53, 'Brownie ', '650.00', 1, '650.00', 'pending'),
(44, 23, 140, 'Espresso single ', '600.00', 4, '2400.00', 'pending'),
(45, 24, 175, 'PAAN', '3000.00', 2, '6000.00', 'pending'),
(46, 24, 101, 'All meat Royal pizza', '3800.00', 1, '3800.00', 'pending'),
(47, 24, 157, 'Water 500ml', '100.00', 2, '200.00', 'pending'),
(48, 25, 74, 'Devilled chicken wrap', '1500.00', 1, '1500.00', 'pending'),
(49, 25, 170, 'TWO APPLE WITH MINT', '2700.00', 1, '2700.00', 'pending'),
(50, 26, 159, 'KAROKE ', '1500.00', 2, '3000.00', 'pending'),
(51, 27, 157, 'Water 500ml', '100.00', 2, '200.00', 'pending'),
(52, 27, 180, 'Chicken Fried Rice', '1600.00', 1, '1600.00', 'pending'),
(53, 28, 9, 'Prawn Kottu', '1950.00', 1, '1950.00', 'pending'),
(54, 28, 43, 'Mixed Green Salad ', '1100.00', 1, '1100.00', 'pending'),
(55, 28, 157, 'Water 500ml', '100.00', 1, '100.00', 'pending'),
(56, 28, 77, 'Devilled Chicken ', '1800.00', 1, '1800.00', 'pending'),
(57, 28, 162, 'BBQ PIZZA', '3500.00', 1, '3500.00', 'pending'),
(58, 29, 161, 'Tandoori pizza', '3500.00', 1, '3500.00', 'pending'),
(59, 30, 174, 'LOVE 696', '3000.00', 1, '3000.00', 'pending'),
(60, 30, 86, 'Cheesy chicken Fries ', '1400.00', 1, '1400.00', 'pending'),
(61, 30, 74, 'Devilled chicken wrap', '1500.00', 1, '1500.00', 'pending'),
(62, 30, 159, 'KAROKE ', '1500.00', 3, '4500.00', 'pending'),
(63, 30, 153, 'Ginger beer 400ml', '400.00', 2, '800.00', 'pending'),
(64, 30, 156, 'Sprite 400ml', '350.00', 1, '350.00', 'pending'),
(65, 30, 198, 'Soda', '350.00', 1, '350.00', 'pending'),
(66, 30, 157, 'Water 500ml', '100.00', 1, '100.00', 'pending'),
(67, 31, 175, 'PAAN', '3000.00', 1, '3000.00', 'pending'),
(68, 31, 86, 'Cheesy chicken Fries ', '1400.00', 1, '1400.00', 'pending'),
(69, 31, 29, 'Watermelon ', '800.00', 1, '800.00', 'pending'),
(70, 31, 28, 'Mango', '1000.00', 1, '1000.00', 'pending'),
(71, 31, 157, 'Water 500ml', '100.00', 2, '200.00', 'pending'),
(72, 31, 199, 'Chicken fried rice L', '2200.00', 1, '2200.00', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `bill_promotions`
--

CREATE TABLE `bill_promotions` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `promo_id` int(11) DEFAULT NULL,
  `promo_name` varchar(150) DEFAULT NULL,
  `discount_amt` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `debtors`
--

CREATE TABLE `debtors` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `credit_limit` decimal(10,2) DEFAULT 0.00,
  `outstanding` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `debtors`
--

INSERT INTO `debtors` (`id`, `name`, `phone`, `email`, `credit_limit`, `outstanding`, `notes`, `active`, `created_at`) VALUES
(1, 'ABC Company', '+94112345678', NULL, '0.00', '28500.00', NULL, 1, '2026-06-23 03:48:36'),
(2, 'XYZ Office', '+94113456789', NULL, '0.00', '12000.00', NULL, 1, '2026-06-23 03:48:36'),
(3, 'Colombo Hotel', '+94114567890', NULL, '0.00', '67000.00', NULL, 1, '2026-06-23 03:48:36'),
(4, 'Mr. Pradeep', '+94775678901', NULL, '0.00', '3500.00', NULL, 1, '2026-06-23 03:48:36');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `nic`, `phone`, `position`, `basic_salary`, `allowances`, `epf_applicable`, `joined_date`, `active`, `created_at`) VALUES
(1, '[DEL] Kasun Perera', '199012345678', '+94771234567', 'Manager', '65000.00', '8000.00', 1, '2022-01-01', 0, '2026-06-23 03:48:35'),
(2, '[DEL] Nimali Silva', '199534567890', '+94772345678', 'Cashier', '42000.00', '5000.00', 1, '2022-03-15', 0, '2026-06-23 03:48:35'),
(3, 'KAMAL', '198890123456', '+94773456789', 'Gardener ', '45000.00', '0.00', 0, '2021-06-01', 1, '2026-06-23 03:48:35'),
(4, '[DEL] Chamari Jayawardena', '200012345670', '+94774567890', 'Waiter', '35000.00', '4000.00', 1, '2023-01-10', 0, '2026-06-23 03:48:35'),
(5, '[DEL] Ruwan Bandara', '199678901234', '+94775678901', 'Kitchen Helper', '28000.00', '3000.00', 0, '2023-05-20', 0, '2026-06-23 03:48:35'),
(6, 'Gimhan', '', '0703290650', 'H. Barista ', '40000.00', '20000.00', 0, '2026-05-02', 1, '2026-07-03 13:00:44'),
(7, 'VIHANGA ARUNALU WETHTHASINGHA', '', '0702688871', 'Kitchen ', '30000.00', '0.00', 0, '2026-04-03', 1, '2026-07-03 13:05:00'),
(8, 'SACHIN ', '', '', 'Barista ', '45000.00', '5000.00', 1, '2026-06-05', 1, '2026-07-03 17:45:30'),
(9, 'ANJANA', '', '', 'T. Barista ', '30000.00', '5000.00', 0, '2026-06-06', 1, '2026-07-03 17:51:29'),
(10, 'ASHEN', '', '', 'Chef', '50000.00', '20000.00', 0, '2026-05-02', 1, '2026-07-03 17:56:31'),
(11, '[DEL] Ashen ', '', '', 'Chef', '50000.00', '20000.00', 0, '2026-05-09', 0, '2026-07-03 17:57:45'),
(12, '[DEL] ASHEN', '', '', 'Chef', '50000.00', '20000.00', 0, '2026-05-09', 0, '2026-07-03 17:58:37'),
(13, 'HIRU', '', '', 'Head chef', '75000.00', '15000.00', 0, '2026-06-02', 1, '2026-07-03 18:02:42');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('Cash','Card','Bank Transfer') DEFAULT 'Cash',
  `supplier` varchar(150) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_date`, `category_id`, `description`, `amount`, `payment_method`, `supplier`, `invoice_no`, `notes`, `created_by`, `created_at`) VALUES
(1, '2026-07-02', 3, 'Vegetable ', '9057.80', 'Cash', 'Keels', NULL, 'Potatoes 1.2kg, pineapple, bread, lime, avocado,watermelon, yogurt 6, brocolli', 6, '2026-07-03 08:02:49'),
(2, '2026-07-02', 3, 'Egg 100 / flour 25kg', '8025.00', 'Cash', 'Market ', NULL, '', 6, '2026-07-03 08:05:05'),
(3, '2026-07-01', 3, 'Capsicum  0.388g', '213.00', 'Cash', 'Keels', NULL, '', 6, '2026-07-03 08:06:41'),
(4, '2026-07-02', 3, 'Sheesha bowl 1 and clips 100pcs', '2800.00', 'Cash', 'Shisha hub', NULL, '', 6, '2026-07-03 08:07:56'),
(5, '2026-07-02', 5, 'Flour 25kg 1 bag', '4125.00', 'Cash', 'Market', NULL, '', 6, '2026-07-03 08:10:16'),
(6, '2026-07-02', 3, 'Vegetables ', '3800.00', 'Cash', 'Market', NULL, 'Carrot, onion, lettuce, chilli', 6, '2026-07-03 08:13:33'),
(8, '2026-07-03', 3, 'Chicken 10', '19000.00', 'Bank Transfer', 'Havelock chicken shop', NULL, '', 6, '2026-07-03 12:23:41'),
(9, '2026-07-03', 4, 'Ragy', '1550.00', 'Cash', 'Pickme', NULL, 'Lap pick- cafe', 6, '2026-07-03 12:38:01'),
(10, '2026-07-02', 4, 'Ragy transport ', '1250.00', 'Cash', 'Pickme ', NULL, 'Home-cafe', 6, '2026-07-03 12:39:38'),
(11, '2026-07-01', 4, 'Book shop -market- cafe', '950.00', 'Cash', 'Uber', NULL, 'Promotion items buying ', 6, '2026-07-03 12:48:22'),
(12, '2026-07-02', 6, 'Paper', '300.00', 'Cash', 'Market book shop', NULL, 'For table tent', 6, '2026-07-03 12:54:30'),
(13, '2026-07-04', 3, 'Cuttle fish  5.364kg', '9011.00', 'Cash', 'Sirilak sea food', NULL, '', 6, '2026-07-04 11:13:19'),
(14, '2026-07-04', 4, 'Home/seafood/cafe', '1150.00', 'Cash', 'Uber', NULL, '', 6, '2026-07-04 11:13:59'),
(15, '2026-07-04', 4, 'Ragy transport ', '1450.00', 'Cash', 'Uber', NULL, '', 6, '2026-07-04 11:14:27'),
(16, '2026-07-04', 3, 'Vegetables ', '1142.32', 'Cash', 'Food city', NULL, 'Lettuce 140g, spring onions 232g, tomatoes 816g, chicken ham 150g', 6, '2026-07-04 11:19:56'),
(17, '2026-07-03', 1, 'Cafe phone reload ', '500.00', 'Cash', 'Dialog ', NULL, '', 6, '2026-07-04 11:21:30'),
(18, '2026-07-05', 3, 'Coke 400ml 24 bottles ', '4080.00', 'Cash', 'DIDURSHAN TRADERS', NULL, '', 6, '2026-07-05 08:27:45'),
(19, '2026-07-05', 4, 'Ragy transport ', '1750.00', 'Cash', 'Uber', NULL, '', 6, '2026-07-05 08:32:35'),
(20, '2026-07-05', 5, 'Chicken', '6050.00', 'Cash', 'Havelock chicken shop', NULL, '', 6, '2026-07-05 17:39:33'),
(21, '2026-07-05', 5, 'Button mushrooms ', '595.00', 'Cash', 'Keels', NULL, '', 6, '2026-07-05 17:40:28'),
(22, '2026-07-06', 3, 'Vegetables ', '1150.00', 'Cash', 'Market veg shop', NULL, '', 6, '2026-07-06 13:08:17'),
(23, '2026-07-06', 4, 'Ragy transport ', '1650.00', 'Cash', '', NULL, '', 5, '2026-07-06 17:21:19'),
(24, '2026-07-07', 3, 'Grocery ', '4992.88', 'Cash', 'Keels', NULL, 'Veg, fruits, ice cream, yogurt ', 6, '2026-07-07 05:53:26'),
(25, '2026-07-08', 3, 'Shisha flavour ', '17200.00', 'Bank Transfer', 'Shisha hub', NULL, 'Strawberry , peach, melon, watermelon 100g, paan 250g, mouth Tip 100pcs', 6, '2026-07-07 11:58:49'),
(26, '2026-07-07', 4, 'Keels', '750.00', 'Cash', 'Uber', NULL, '', 6, '2026-07-07 12:33:49'),
(27, '2026-07-07', 4, 'Ragy transport ', '1650.00', 'Cash', 'Uber', NULL, '', 6, '2026-07-07 12:34:15'),
(28, '2026-07-06', 1, 'Cafe phone data', '915.00', 'Cash', 'Dialog', NULL, '', 6, '2026-07-07 12:34:55'),
(29, '2026-07-08', 3, 'Cooking cream', '3300.00', 'Bank Transfer', 'Global buydistributors', NULL, '', 6, '2026-07-08 12:23:54'),
(30, '2026-07-08', 5, 'Kottu ', '3200.00', 'Cash', 'Kotthu supplier', NULL, '10kg kotthu', 6, '2026-07-08 12:25:55'),
(31, '2026-07-07', 5, 'Ragy transport ', '1500.00', 'Cash', 'Uber', NULL, '', 6, '2026-07-08 17:13:07'),
(32, '2026-07-08', 5, 'Exhaust fan kitchen ', '15000.00', 'Cash', 'Hiru', NULL, '', 6, '2026-07-08 17:14:03'),
(33, '2026-07-10', 5, 'Kitchen sink fix ', '10100.00', 'Cash', 'Plumber ', NULL, '', 6, '2026-07-10 17:26:36'),
(34, '2026-07-10', 4, 'Ragy transport ', '1450.00', 'Cash', 'Uber', NULL, '', 6, '2026-07-10 17:28:15'),
(35, '2026-07-11', 4, 'Market supplier transport ', '1200.00', 'Cash', 'Tuck', NULL, '', 6, '2026-07-11 09:29:39'),
(36, '2026-07-11', 3, 'Grocery bill', '32343.42', 'Cash', '', NULL, '', 6, '2026-07-11 09:31:36'),
(37, '2026-07-11', 3, 'Chicken 10kg', '20000.00', 'Bank Transfer', 'Havelock chicken supplier', NULL, '', 6, '2026-07-11 09:32:22');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `active`) VALUES
(1, 'Utilities', 1),
(2, 'Rent', 1),
(3, 'Purchases', 1),
(4, 'Transport', 1),
(5, 'Maintenance', 1),
(6, 'Marketing', 1),
(7, 'Salaries', 1),
(8, 'Miscellaneous', 1);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`) VALUES
(1, 'Grains'),
(2, 'Proteins'),
(3, 'Oils'),
(4, 'Produce'),
(5, 'Dairy'),
(6, 'Beverages'),
(7, 'Utilities');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `unit` varchar(20) DEFAULT 'kg',
  `qty` decimal(10,3) DEFAULT 0.000,
  `min_qty` decimal(10,3) DEFAULT 0.000,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `category_id`, `name`, `unit`, `qty`, `min_qty`, `unit_cost`, `active`, `updated_at`) VALUES
(1, 1, 'Rice basmati ', 'kg', '25.000', '10.000', '700.00', 1, '2026-07-08 14:24:42'),
(2, 2, 'Chicken', 'g', '14400.000', '5000.000', '2.00', 1, '2026-07-11 17:54:39'),
(3, 2, 'Fish', 'kg', '8.000', '8.000', '300.00', 1, '2026-07-08 14:50:25'),
(4, 1, 'Flour', 'kg', '30.000', '15.000', '210.00', 1, '2026-06-23 03:48:33'),
(5, 3, 'Cooking Oil', 'L', '20.000', '5.000', '1000.00', 1, '2026-07-08 14:25:20'),
(6, 4, 'Capsicum ', 'g', '0.470', '100.000', '480.00', 1, '2026-07-08 17:05:01'),
(7, 6, 'Tea Packets', 'packs', '5.000', '5.000', '17.00', 1, '2026-07-08 14:18:07'),
(8, 7, 'Gas Cylinders', 'cylinders', '2.000', '1.000', '5200.00', 1, '2026-07-08 17:08:38'),
(9, 6, 'Fresh milk ', 'ml', '12000.000', '2000.000', '0.53', 1, '2026-07-08 14:10:13'),
(10, 6, 'Lavazza bean', 'g', '1000.000', '400.000', '8.00', 1, '2026-07-08 14:03:59'),
(11, 5, 'Ice cream vanilla', 'g', '4000.000', '500.000', '0.47', 1, '2026-07-08 13:58:46'),
(12, 6, 'Water melon ', 'g', '2850.000', '1.000', '0.16', 1, '2026-07-11 18:19:32'),
(13, 6, 'Passion fruit ', 'pieces', '14.000', '4.000', '60.00', 1, '2026-07-08 14:16:37'),
(14, 2, 'Cuttle fish', 'g', '5300.000', '1000.000', '1.80', 1, '2026-07-08 14:48:46'),
(15, 2, 'Prawns', 'g', '5000.000', '1000.000', '3.60', 1, '2026-07-08 14:51:14'),
(16, 4, 'Lettuce ', 'g', '0.130', '100.000', '1350.00', 1, '2026-07-08 17:08:00'),
(17, 4, 'Mango', 'g', '0.000', '0.200', '216.32', 1, '2026-07-11 18:19:32'),
(18, 6, 'Blueberry puree', 'ml', '1000.000', '100.000', '10.58', 1, '2026-07-08 14:03:05'),
(19, 6, 'Mornin peach frap', 'ml', '1000.000', '100.000', '8.02', 1, '2026-07-08 14:11:37'),
(20, 6, 'Mornin strawberry purée ', 'ml', '1000.000', '100.000', '7.90', 1, '2026-07-08 14:12:21'),
(21, 6, 'Mornin syrup mojito ', 'ml', '1000.000', '100.000', '5.84', 1, '2026-07-08 14:13:29'),
(22, 6, 'Mornin passion frappe ', 'ml', '1000.000', '100.000', '8.33', 1, '2026-07-08 14:10:53'),
(23, 6, 'Mornin syrup cucumber ', 'ml', '700.000', '100.000', '6.40', 1, '2026-07-08 14:02:11'),
(24, 4, 'Onion ', 'g', '1940.000', '500.000', '0.28', 1, '2026-07-11 17:54:39'),
(25, 4, 'Capsicum ', 'g', '0.000', '0.200', '483.00', 1, '2026-07-10 16:27:23'),
(26, 4, 'Carrot ', 'g', '1.280', '0.500', '490.00', 1, '2026-07-08 17:06:09'),
(27, 4, 'Spring onions ', 'g', '0.250', '0.100', '320.00', 1, '2026-07-08 17:02:53'),
(28, 5, 'Yogurt ', 'pieces', '6.000', '2.000', '80.00', 1, '2026-07-08 14:19:30'),
(29, 4, 'Shisha flavours strawberry ', 'g', '100.000', '40.000', '25.00', 1, '2026-07-08 14:57:24'),
(30, 4, 'Shisha flavours watermelon ', 'g', '130.000', '40.000', '25.00', 1, '2026-07-08 16:53:54'),
(31, 4, 'Shisha flavours  melon ', 'g', '100.000', '50.000', '12.50', 1, '2026-07-08 16:17:51'),
(32, 4, 'Shisha flavours peach', 'g', '100.000', '40.000', '25.00', 1, '2026-07-08 16:19:59'),
(34, 4, 'Shisha flavours mouth tip', 'pieces', '94.000', '20.000', '25.00', 1, '2026-07-11 18:19:32'),
(35, 4, 'Shisha flavours paan', 'g', '190.000', '40.000', '24.00', 1, '2026-07-11 18:19:32'),
(36, 4, 'Shisha flavours chacole', 'pieces', '44.000', '10.000', '20.00', 1, '2026-07-11 18:19:32'),
(37, 4, 'Shisha Flavours Grapemint', 'g', '50.000', '20.000', '25.00', 1, '2026-07-08 16:52:48'),
(38, 4, 'Shisha flavours Double Apple ', 'g', '300.000', '80.000', '25.00', 1, '2026-07-08 16:58:34'),
(39, 4, 'Shisha flavours Love696', 'g', '300.000', '100.000', '25.00', 1, '2026-07-08 16:52:17'),
(40, 6, 'Shisha flavours Mint', 'g', '83.000', '40.000', '12.50', 1, '2026-07-08 16:51:39'),
(41, 2, 'Whole chicken ', 'g', '4.900', '2.500', '1235.00', 1, '2026-07-08 17:10:51'),
(42, 4, 'Kottu', 'g', '10000.000', '1000.000', '0.32', 1, '2026-07-08 19:38:33');

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

CREATE TABLE `menu_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`id`, `name`, `sort_order`, `active`) VALUES
(1, 'Rice Meals', 1, 1),
(2, 'Kottu', 2, 1),
(3, 'Noodles', 3, 1),
(4, 'Beverages', 4, 1),
(5, 'Snacks', 5, 1),
(6, 'Desserts', 6, 1),
(7, 'ALL DAY BREAKFAST ', 0, 1),
(8, 'FRESH JUICE ', 0, 1),
(9, 'WAFFLE / PANECAKE', 0, 1),
(10, 'SALAD ', 0, 1),
(11, 'SOUP', 0, 1),
(12, 'PLATTER ', 0, 1),
(13, 'BURGER ', 0, 1),
(14, 'SUBMARINE ', 0, 1),
(15, 'SANDWICH ', 0, 1),
(16, 'Wraps', 0, 1),
(17, 'BITES', 0, 1),
(18, 'SIDE DISHES ', 0, 1),
(19, 'PASTA / SPEGGETTI / PENNE', 0, 1),
(20, 'WOOD FIRE PIZZA', 0, 1),
(21, 'SMOOTHIE ', 0, 1),
(22, 'Milkshake ', 0, 1),
(23, 'ICETEA', 0, 1),
(24, 'HOT TEA', 0, 1),
(25, 'Hot coffee / ice coffee', 0, 1),
(26, 'Water / SOFT DRINKS ', 0, 1),
(27, 'MOCKTAIL ', 0, 1),
(28, 'KAROKE ROOM', 0, 1),
(29, 'FAMILY COMBO', 0, 1),
(30, 'SHISHA', 0, 1),
(31, 'KIDS MEAL', 0, 1),
(32, 'OMLETTE', 0, 1);

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
  `image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `price`, `description`, `image`, `active`, `created_at`) VALUES
(5, 2, 'Beef Kottu', '1900.00', '', NULL, 1, '2026-06-23 03:48:31'),
(6, 2, 'Egg Kottu', '1500.00', '', NULL, 1, '2026-06-23 03:48:31'),
(7, 2, 'Chicken Kottu', '1850.00', '', NULL, 1, '2026-06-23 03:48:31'),
(8, 2, 'Chef specials cheese kottu', '2000.00', '', NULL, 1, '2026-06-23 03:48:31'),
(9, 2, 'Prawn Kottu', '1950.00', '', NULL, 1, '2026-06-23 03:48:31'),
(22, 7, 'Scramble Egg with Toast Bread', '950.00', '', NULL, 1, '2026-07-05 09:20:12'),
(23, 7, 'English breakfast ', '2000.00', '', NULL, 1, '2026-07-05 09:20:51'),
(24, 7, 'Avacado Toast', '1700.00', '', NULL, 1, '2026-07-05 09:22:35'),
(25, 7, 'Shakshuka', '1800.00', '', NULL, 1, '2026-07-05 09:22:53'),
(26, 7, 'Nutella French toast', '1650.00', '', NULL, 1, '2026-07-05 09:23:24'),
(27, 8, 'Orange', '1900.00', '', NULL, 1, '2026-07-05 09:35:36'),
(28, 8, 'Mango', '1000.00', '', NULL, 1, '2026-07-05 09:37:27'),
(29, 8, 'Watermelon ', '800.00', '', NULL, 1, '2026-07-05 09:37:55'),
(30, 8, 'Papaya', '850.00', '', NULL, 1, '2026-07-05 09:38:37'),
(31, 8, 'Pineapple ', '800.00', '', NULL, 1, '2026-07-05 09:38:49'),
(32, 8, 'Passion ', '1000.00', '', NULL, 1, '2026-07-05 09:39:03'),
(33, 8, 'Lime', '1000.00', '', NULL, 1, '2026-07-05 09:39:31'),
(34, 8, 'Mixed fruit ', '1100.00', '', NULL, 1, '2026-07-05 09:40:01'),
(35, 9, 'Waffle with mixed fruit ', '2100.00', '', NULL, 1, '2026-07-05 09:59:28'),
(36, 9, 'Waffle with strawberry and Nutella ', '2200.00', '', NULL, 1, '2026-07-05 09:59:54'),
(37, 9, 'Waffle with Chocolate sauce ', '1500.00', '', NULL, 1, '2026-07-05 10:00:33'),
(38, 9, 'Banana Chocolate Delight ', '1850.00', '', NULL, 1, '2026-07-05 10:01:06'),
(39, 9, 'Strawberry Choco cloud', '1750.00', '', NULL, 1, '2026-07-05 10:01:41'),
(40, 10, 'Chicken Caesar salad', '1950.00', '', NULL, 1, '2026-07-05 10:02:30'),
(41, 10, 'Egg salad ', '1800.00', '', NULL, 1, '2026-07-05 10:02:45'),
(42, 10, 'Greek Salad ', '1200.00', '', NULL, 1, '2026-07-05 10:03:01'),
(43, 10, 'Mixed Green Salad ', '1100.00', '', NULL, 1, '2026-07-05 10:03:23'),
(44, 11, 'Cream of pumpkin soup', '1400.00', '', NULL, 1, '2026-07-05 10:04:28'),
(45, 11, 'Cream of mushroom soup ', '1500.00', '', NULL, 1, '2026-07-05 10:04:49'),
(46, 11, 'Cream Of chicken Soup', '1600.00', '', NULL, 1, '2026-07-05 10:05:09'),
(47, 11, 'Cream of prawns Soup ', '1900.00', '', NULL, 1, '2026-07-05 10:05:28'),
(48, 11, 'Tom Yum Soup ', '1950.00', '', NULL, 1, '2026-07-05 10:05:46'),
(49, 12, 'Seafood platter ', '4500.00', '', NULL, 1, '2026-07-05 10:06:28'),
(50, 12, 'BBQ fish', '5500.00', '', NULL, 1, '2026-07-05 10:10:16'),
(51, 12, 'Mixed Grilled platter ', '7500.00', '', NULL, 1, '2026-07-05 10:10:49'),
(52, 12, 'Chicken Breast with pan gravy', '2800.00', '', NULL, 1, '2026-07-05 10:11:26'),
(53, 6, 'Brownie ', '650.00', '', NULL, 1, '2026-07-05 10:15:59'),
(54, 6, 'Browni with Ice Cream ', '1050.00', '', NULL, 1, '2026-07-05 10:16:35'),
(55, 6, 'Cheese cake classic ', '1100.00', '', NULL, 1, '2026-07-05 10:17:16'),
(56, 6, 'Cheesecake blueberry ', '1250.00', '', NULL, 1, '2026-07-05 10:18:10'),
(57, 6, 'Cheesecake strawberry ', '1250.00', '', NULL, 1, '2026-07-05 10:19:00'),
(58, 6, 'Muffin strawberry ', '400.00', '', NULL, 1, '2026-07-05 10:19:53'),
(59, 6, 'Muffin chocolate chip', '600.00', '', NULL, 1, '2026-07-05 10:20:13'),
(60, 6, 'Cookie chocolate chips ', '450.00', '', NULL, 1, '2026-07-05 10:20:31'),
(61, 13, 'Crispy Fish burger ', '1750.00', '', NULL, 1, '2026-07-05 10:21:13'),
(62, 13, 'Crispy Chicken Burger ', '1650.00', '', NULL, 1, '2026-07-05 10:24:05'),
(63, 13, 'Spicy beef burger ', '1950.00', '', NULL, 1, '2026-07-05 10:24:27'),
(64, 13, 'Devilled Chicken burger', '1850.00', '', NULL, 1, '2026-07-05 10:24:59'),
(65, 14, 'Crispy Tuna submarine ', '2300.00', '', NULL, 1, '2026-07-05 10:25:45'),
(66, 14, 'Crispy chicken submarine ', '2200.00', '', NULL, 1, '2026-07-05 10:26:11'),
(67, 14, 'Spicy Beef submarine ', '2600.00', '', NULL, 1, '2026-07-05 10:26:47'),
(68, 15, 'Tuna Mayo Sandwich ', '1300.00', '', NULL, 1, '2026-07-05 10:28:06'),
(69, 15, 'Cheesy sausage sandwich ', '1350.00', '', NULL, 1, '2026-07-05 10:28:29'),
(70, 15, 'Grilled Chicken sandwich ', '1400.00', '', NULL, 1, '2026-07-05 10:28:52'),
(71, 15, 'Cheese and Tomato sandwich', '1200.00', '', NULL, 1, '2026-07-05 10:29:20'),
(72, 15, 'Ham & Cheese Sandwich ', '1300.00', '', NULL, 1, '2026-07-05 10:29:47'),
(73, 15, 'Club sandwich', '1950.00', '', NULL, 1, '2026-07-05 10:30:28'),
(74, 16, 'Devilled chicken wrap', '1500.00', '', NULL, 1, '2026-07-05 10:31:26'),
(75, 16, 'Tandoori chicken wrap', '1750.00', '', NULL, 1, '2026-07-05 10:32:16'),
(76, 16, 'Spicy Beef wrap', '1950.00', '', NULL, 1, '2026-07-05 10:32:55'),
(77, 17, 'Devilled Chicken ', '1800.00', '', NULL, 1, '2026-07-05 10:33:39'),
(78, 17, 'Devilled Fish', '1650.00', '', NULL, 1, '2026-07-05 10:34:14'),
(79, 17, 'Hot butter chicken ', '2100.00', '', NULL, 1, '2026-07-05 10:34:34'),
(80, 17, 'Hot butter cuttlefish ', '2200.00', '', NULL, 1, '2026-07-05 10:34:55'),
(81, 17, 'Hot butter prawns ', '2200.00', '', NULL, 1, '2026-07-05 10:35:18'),
(82, 17, 'Hot butter mushrooms ', '1650.00', '', NULL, 1, '2026-07-05 10:36:29'),
(83, 17, 'Onion rings ', '1200.00', '', NULL, 1, '2026-07-05 10:37:07'),
(84, 18, 'French fries s', '950.00', '', NULL, 1, '2026-07-05 10:37:49'),
(85, 18, 'French fries L ', '1800.00', '', NULL, 1, '2026-07-05 10:39:21'),
(86, 18, 'Cheesy chicken Fries ', '1400.00', '', NULL, 1, '2026-07-05 10:41:49'),
(87, 18, 'Cheesy Beef fries', '1600.00', '', NULL, 1, '2026-07-05 10:42:14'),
(88, 18, 'Devilled wings', '1100.00', '', NULL, 1, '2026-07-05 10:42:38'),
(89, 18, 'Garlic bread', '950.00', '', NULL, 1, '2026-07-05 10:43:03'),
(90, 19, 'Creamy chicken & mushroom pasta', '1850.00', '', NULL, 1, '2026-07-05 10:48:53'),
(91, 19, 'Chicken tomato pasta ', '1950.00', '', NULL, 1, '2026-07-05 10:49:13'),
(92, 19, 'Prawn pasta Royal ', '2200.00', '', NULL, 1, '2026-07-05 10:49:37'),
(93, 19, 'Speggetti agliolio ', '2100.00', '', NULL, 1, '2026-07-05 10:50:20'),
(94, 19, 'Speggetti beef bolognese ', '2100.00', '', NULL, 1, '2026-07-05 10:50:47'),
(95, 19, 'Carbonara  Speggetti ', '1900.00', '', NULL, 1, '2026-07-05 10:51:12'),
(96, 20, 'Margarita', '2200.00', '', NULL, 1, '2026-07-05 10:52:04'),
(97, 20, 'Golden tropical hawain pizza', '2850.00', '', NULL, 1, '2026-07-05 10:52:49'),
(98, 20, 'Golden tropical Hawaii pizza', '3500.00', '', NULL, 1, '2026-07-05 10:53:17'),
(99, 20, 'Sea food fushion pizza', '3700.00', '', NULL, 1, '2026-07-05 10:54:50'),
(100, 20, 'Jumbo sausage Delight pizza', '2800.00', '', NULL, 1, '2026-07-05 10:55:21'),
(101, 20, 'All meat Royal pizza', '3800.00', '', NULL, 1, '2026-07-05 10:56:03'),
(102, 20, 'Veg Delights ', '2500.00', '', NULL, 1, '2026-07-05 10:56:31'),
(103, 20, 'Beef pepperoni pizza', '3500.00', '', NULL, 1, '2026-07-05 10:56:56'),
(104, 20, 'Tuna pizza ', '3000.00', '', NULL, 1, '2026-07-05 10:57:14'),
(105, 21, 'MANGO', '1300.00', '', NULL, 1, '2026-07-05 11:27:14'),
(106, 21, 'Strawberry ', '1400.00', '', NULL, 1, '2026-07-05 11:27:32'),
(107, 21, 'Blueberry ', '1600.00', '', NULL, 1, '2026-07-05 11:27:45'),
(108, 7, 'Strawberry ', '1400.00', '', NULL, 1, '2026-07-05 11:28:02'),
(109, 21, 'Banana ', '1200.00', '', NULL, 1, '2026-07-05 11:28:22'),
(110, 21, 'Passion fruit ', '1500.00', '', NULL, 1, '2026-07-05 11:28:40'),
(111, 7, 'Avacado ', '1500.00', '', NULL, 1, '2026-07-05 11:28:52'),
(112, 22, 'Vanilla ', '950.00', '', NULL, 1, '2026-07-05 11:29:16'),
(113, 22, 'Strawberry ', '1000.00', '', NULL, 1, '2026-07-05 11:29:49'),
(114, 22, 'Banana ', '950.00', '', NULL, 1, '2026-07-05 11:30:05'),
(115, 22, 'Nutella ', '1800.00', '', NULL, 1, '2026-07-05 11:30:20'),
(116, 22, 'Snickers ', '1800.00', '', NULL, 1, '2026-07-05 11:30:46'),
(117, 22, 'Oreo', '1800.00', '', NULL, 1, '2026-07-05 11:30:59'),
(118, 22, 'Matcha ', '1500.00', '', NULL, 1, '2026-07-05 11:31:13'),
(119, 27, 'Classic mojito', '1100.00', '', NULL, 1, '2026-07-05 11:32:13'),
(120, 27, 'Strawberry mojito ', '1100.00', '', NULL, 1, '2026-07-05 11:32:51'),
(121, 27, 'Passion mojito ', '1400.00', '', NULL, 1, '2026-07-05 11:33:20'),
(122, 27, 'Cucumber mojito ', '1300.00', '', NULL, 1, '2026-07-05 11:33:40'),
(123, 27, 'Green Apple', '1300.00', '', NULL, 1, '2026-07-05 11:33:55'),
(124, 27, 'Tropical Island blend ', '1150.00', '', NULL, 1, '2026-07-05 11:34:21'),
(125, 27, 'Red Bull mojito ', '1800.00', '', NULL, 1, '2026-07-05 11:34:41'),
(126, 27, 'Virgin pina colada', '1150.00', '', NULL, 1, '2026-07-05 11:35:56'),
(127, 27, 'Srilanka Rose', '1500.00', '', NULL, 1, '2026-07-05 11:36:15'),
(128, 27, 'Monkey business ', '1600.00', '', NULL, 1, '2026-07-05 11:36:30'),
(129, 27, 'Passion coconut breeze ', '1600.00', '', NULL, 1, '2026-07-05 11:39:16'),
(130, 23, 'Lime Iced Tea', '950.00', '', NULL, 1, '2026-07-05 11:39:53'),
(131, 23, 'Strawberry Ice Tea', '1000.00', '', NULL, 1, '2026-07-05 11:40:22'),
(132, 23, 'Peach Ice Tea', '950.00', '', NULL, 1, '2026-07-05 11:40:35'),
(133, 24, 'English Breakfast Tea', '350.00', '', NULL, 1, '2026-07-05 11:41:26'),
(134, 24, 'Early Grey Tea', '450.00', '', NULL, 1, '2026-07-05 11:41:46'),
(135, 24, 'Green Tea', '450.00', '', NULL, 1, '2026-07-05 11:42:02'),
(136, 24, 'Cammomile Tea', '450.00', '', NULL, 1, '2026-07-05 11:42:20'),
(137, 24, 'Black Tea', '300.00', '', NULL, 1, '2026-07-05 11:42:46'),
(138, 24, 'Milk Tea', '450.00', '', NULL, 1, '2026-07-05 11:43:12'),
(139, 24, 'Masala Tea', '650.00', '', NULL, 1, '2026-07-05 11:43:27'),
(140, 25, 'Espresso single ', '600.00', '', NULL, 1, '2026-07-05 11:44:11'),
(141, 25, 'Double Espresso ', '700.00', '', NULL, 1, '2026-07-05 11:44:32'),
(142, 25, 'Americano ', '950.00', '', NULL, 1, '2026-07-05 11:44:50'),
(143, 25, 'Caffe Latte', '850.00', '', NULL, 1, '2026-07-05 11:50:06'),
(144, 25, 'FLAT WHITE', '800.00', '', NULL, 1, '2026-07-05 11:52:18'),
(145, 25, 'Cuppachiono', '850.00', '', NULL, 1, '2026-07-05 11:52:40'),
(146, 25, 'Cafe mocha', '1400.00', '', NULL, 1, '2026-07-05 11:53:00'),
(147, 25, 'Hot chocolate ', '1300.00', '', NULL, 1, '2026-07-05 11:53:26'),
(148, 25, 'Ice Americano', '1000.00', '', NULL, 1, '2026-07-05 11:53:46'),
(149, 25, 'Ice Latte', '950.00', '', NULL, 1, '2026-07-05 11:54:00'),
(150, 25, 'Ice mocha', '1100.00', '', NULL, 1, '2026-07-05 11:54:16'),
(151, 25, 'Affogato ', '950.00', '', NULL, 1, '2026-07-05 11:54:37'),
(152, 26, 'Red bull', '1400.00', '', NULL, 1, '2026-07-05 11:55:50'),
(153, 26, 'Ginger beer 400ml', '400.00', '', NULL, 1, '2026-07-05 11:56:18'),
(154, 26, 'Sprite 400ml', '350.00', '', NULL, 1, '2026-07-05 11:56:43'),
(155, 26, 'Coke 400ml', '350.00', '', NULL, 1, '2026-07-05 11:57:06'),
(156, 26, 'Sprite 400ml', '350.00', '', NULL, 1, '2026-07-05 11:57:28'),
(157, 26, 'Water 500ml', '100.00', '', NULL, 1, '2026-07-05 11:57:48'),
(158, 26, 'Water 1lt', '120.00', '', NULL, 1, '2026-07-05 11:58:03'),
(159, 28, 'KAROKE ', '1500.00', '', NULL, 1, '2026-07-05 12:04:39'),
(160, 18, 'Devilled beef', '2200.00', '', NULL, 1, '2026-07-05 17:45:41'),
(161, 20, 'Tandoori pizza', '3500.00', '', NULL, 1, '2026-07-05 17:56:29'),
(162, 20, 'BBQ PIZZA', '3500.00', '', NULL, 1, '2026-07-05 17:56:53'),
(163, 29, 'Kottu 4 pcs', '4500.00', '', NULL, 1, '2026-07-05 18:00:49'),
(164, 28, 'KAROKE 30MIN', '750.00', '', NULL, 1, '2026-07-05 18:02:18'),
(165, 13, 'Crispy chicken burger', '1650.00', '', NULL, 1, '2026-07-06 16:55:20'),
(166, 22, 'Chocolate ', '1000.00', '', NULL, 1, '2026-07-06 16:58:41'),
(167, 30, 'GRAPE MINT', '2700.00', '', NULL, 1, '2026-07-07 11:32:23'),
(168, 30, 'MINT', '2700.00', '', NULL, 1, '2026-07-07 11:32:44'),
(169, 30, 'BLUEBERRY WITH MINT ', '2700.00', '', NULL, 1, '2026-07-07 11:33:40'),
(170, 30, 'TWO APPLE WITH MINT', '2700.00', '', NULL, 1, '2026-07-07 11:34:19'),
(171, 30, 'TWO APPLE', '2700.00', '', NULL, 1, '2026-07-07 11:34:30'),
(172, 30, 'STRAWBERRY ', '2700.00', '', NULL, 1, '2026-07-07 11:34:48'),
(173, 30, 'WATERMELON ', '2700.00', '', NULL, 1, '2026-07-07 11:35:19'),
(174, 30, 'LOVE 696', '3000.00', '', NULL, 1, '2026-07-07 11:35:41'),
(175, 30, 'PAAN', '3000.00', '', NULL, 1, '2026-07-07 11:35:57'),
(176, 30, 'PEACH', '3000.00', '', NULL, 1, '2026-07-07 11:36:23'),
(177, 30, 'CACHOLE', '100.00', '', NULL, 1, '2026-07-07 11:37:07'),
(178, 1, 'Egg fried Rice', '1400.00', '', NULL, 1, '2026-07-08 17:19:04'),
(179, 1, 'Creamy chicken with breast ', '2500.00', '', NULL, 1, '2026-07-08 17:20:09'),
(180, 1, 'Chicken Fried Rice', '1600.00', '', NULL, 1, '2026-07-08 17:20:44'),
(181, 1, 'Beef Fried Rice', '1950.00', '', NULL, 1, '2026-07-08 17:21:17'),
(182, 3, 'Veg Noodles ', '1200.00', '', NULL, 1, '2026-07-08 17:23:56'),
(183, 3, 'Egg Noodles ', '1300.00', '', NULL, 1, '2026-07-08 17:24:17'),
(184, 3, 'Chicken Noodles', '1500.00', '', NULL, 1, '2026-07-08 17:24:45'),
(185, 3, 'Fish Noodles ', '1500.00', '', NULL, 1, '2026-07-08 17:25:03'),
(186, 3, 'Beef Noodles ', '1650.00', '', NULL, 1, '2026-07-08 17:26:39'),
(187, 3, 'Smoked Noodles ', '1600.00', '', NULL, 1, '2026-07-08 17:26:57'),
(188, 31, 'Fish and chips ', '1800.00', '', NULL, 1, '2026-07-08 17:45:52'),
(189, 31, 'Crispy chicken strips ', '1800.00', '', NULL, 1, '2026-07-08 17:46:43'),
(190, 31, 'Chicken cheesy mini kives', '1700.00', '', NULL, 1, '2026-07-08 17:47:35'),
(191, 31, 'Fries and chicken nuggets ', '1650.00', '', NULL, 1, '2026-07-08 17:48:00'),
(192, 32, 'Classic omlette ', '1250.00', '', NULL, 1, '2026-07-08 17:49:29'),
(193, 32, 'Cheesy omlette', '1550.00', '', NULL, 1, '2026-07-08 17:50:00'),
(194, 7, 'Spicy Beef omelette ', '1650.00', '', NULL, 1, '2026-07-08 17:51:00'),
(195, 32, 'Cheesy omelette ', '1350.00', '', NULL, 1, '2026-07-08 17:51:28'),
(196, 1, 'Mixed fried rice', '2000.00', '', NULL, 1, '2026-07-08 17:54:33'),
(197, 21, 'Avacado ', '1500.00', '', NULL, 1, '2026-07-08 18:01:39'),
(198, 26, 'Soda', '350.00', '', NULL, 1, '2026-07-08 18:12:01'),
(199, 1, 'Chicken fried rice L', '2200.00', '', NULL, 1, '2026-07-08 19:22:24'),
(200, 22, 'Matcha Milkshake ', '1500.00', '', NULL, 1, '2026-07-10 16:23:41'),
(201, 32, 'Cheesy chicken omelette ', '1550.00', '', NULL, 1, '2026-07-10 16:32:49');

-- --------------------------------------------------------

--
-- Table structure for table `menu_item_recipes`
--

CREATE TABLE `menu_item_recipes` (
  `id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `qty_per_unit` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_item_recipes`
--

INSERT INTO `menu_item_recipes` (`id`, `menu_item_id`, `inventory_item_id`, `qty_per_unit`, `created_at`) VALUES
(3, 149, 9, '200.0000', '2026-07-05 18:05:42'),
(4, 149, 10, '30.0000', '2026-07-05 18:07:36'),
(5, 115, 9, '150.0000', '2026-07-07 06:00:07'),
(6, 115, 11, '150.0000', '2026-07-07 06:00:28'),
(7, 117, 9, '150.0000', '2026-07-07 06:00:47'),
(10, 29, 12, '150.0000', '2026-07-07 06:12:41'),
(11, 110, 13, '2.0000', '2026-07-07 06:18:27'),
(12, 28, 17, '150.0000', '2026-07-07 12:45:49'),
(13, 77, 2, '200.0000', '2026-07-07 17:11:19'),
(14, 77, 24, '20.0000', '2026-07-07 17:20:12'),
(15, 77, 25, '20.0000', '2026-07-07 17:20:39'),
(16, 151, 11, '100.0000', '2026-07-07 17:23:12'),
(17, 151, 10, '14.0000', '2026-07-07 17:23:47'),
(18, 142, 10, '28.0000', '2026-07-07 17:24:49'),
(19, 175, 35, '20.0000', '2026-07-08 13:32:19'),
(20, 175, 36, '2.0000', '2026-07-08 13:34:22'),
(21, 175, 34, '2.0000', '2026-07-08 13:36:01'),
(22, 176, 32, '20.0000', '2026-07-08 13:37:37'),
(23, 176, 36, '2.0000', '2026-07-08 13:42:51'),
(25, 176, 34, '2.0000', '2026-07-08 13:45:22'),
(26, 172, 29, '20.0000', '2026-07-08 13:46:08'),
(27, 172, 34, '2.0000', '2026-07-08 13:46:42'),
(28, 172, 36, '2.0000', '2026-07-08 13:47:25'),
(29, 173, 30, '20.0000', '2026-07-08 13:49:56'),
(30, 173, 34, '2.0000', '2026-07-08 13:50:55');

-- --------------------------------------------------------

--
-- Table structure for table `online_orders`
--

CREATE TABLE `online_orders` (
  `id` int(11) NOT NULL,
  `order_no` varchar(30) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_note` text DEFAULT NULL,
  `order_type` enum('takeaway','card','bank_transfer') DEFAULT 'takeaway',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('new','confirmed','preparing','ready','completed','cancelled') DEFAULT 'new',
  `seen` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `online_orders`
--

INSERT INTO `online_orders` (`id`, `order_no`, `customer_name`, `customer_phone`, `customer_note`, `order_type`, `subtotal`, `service_charge`, `tax`, `total`, `status`, `seen`, `created_at`, `updated_at`) VALUES
(14, 'ONL-20260625-7375', 'Aski', '0779930640', '', 'takeaway', '950.00', '95.00', '83.60', '1128.60', 'new', 0, '2026-06-25 17:34:07', '2026-06-25 17:34:07');

-- --------------------------------------------------------

--
-- Table structure for table `online_order_items`
--

CREATE TABLE `online_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `online_order_items`
--

INSERT INTO `online_order_items` (`id`, `order_id`, `menu_item_id`, `item_name`, `price`, `qty`, `line_total`) VALUES
(25, 14, 1, 'Chicken Rice', '450.00', 1, '450.00'),
(26, 14, 2, 'Fish Curry Rice', '500.00', 1, '500.00');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_month` date NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`id`, `employee_id`, `pay_month`, `basic_salary`, `allowances`, `overtime_pay`, `bonus`, `gross_salary`, `salary_advance`, `other_deductions`, `epf_employee`, `epf_employer`, `etf_employer`, `net_salary`, `status`, `paid_date`, `created_at`) VALUES
(1, 9, '2026-07-01', '30000.00', '5000.00', '3000.00', '0.00', '38000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '38000.00', 'draft', NULL, '2026-07-07 10:55:28');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `promo_type` enum('percent_off','fixed_off','buy_x_get_y') NOT NULL DEFAULT 'percent_off',
  `discount_value` decimal(10,2) DEFAULT 0.00,
  `buy_qty` int(11) DEFAULT 1,
  `get_qty` int(11) DEFAULT 1,
  `applies_to` enum('all','category','item') DEFAULT 'all',
  `applies_id` int(11) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `name`, `description`, `promo_type`, `discount_value`, `buy_qty`, `get_qty`, `applies_to`, `applies_id`, `min_order_amount`, `valid_from`, `valid_to`, `active`, `created_at`) VALUES
(1, '10% Off All Orders', 'Get 10% discount on your entire bill', 'percent_off', '10.00', 1, 1, 'all', NULL, '0.00', '2026-06-23', '2026-07-23', 1, '2026-06-23 03:48:32'),
(2, 'Happy Hour Rs.100 Off', 'Rs. 100 off on orders above Rs. 500', 'fixed_off', '100.00', 1, 1, 'all', NULL, '0.00', '2026-06-23', '2026-07-23', 1, '2026-06-23 03:48:32');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `res_date` date NOT NULL,
  `res_time` time NOT NULL,
  `res_end_time` time DEFAULT NULL,
  `pax` int(11) NOT NULL DEFAULT 1,
  `location` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Confirmed','Pending','Cancelled','Completed') DEFAULT 'Confirmed',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `customer_name`, `contact`, `res_date`, `res_time`, `res_end_time`, `pax`, `location`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 'Zainab Hassan', '0722178184', '2026-06-27', '17:00:00', '20:00:00', 11, 'Karaoke Room', '', 'Confirmed', 6, '2026-06-23 11:57:13', '2026-06-23 11:57:13'),
(4, 'SARA', '0719843221', '2026-06-27', '15:30:00', '16:30:00', 2, 'Karoke room', 'Karaoke Room Rules\r\n 1.  Rooms are charged by the hour. Advance booking recommended.\r\n 2.  Outside food not allowed, except birthday cakes + decorations.\r\n 3.  BYOB welcome. Shisha available. No cigarette/weed smoking.\r\n 4.  Please handle mics, remotes & screens with care.\r\n 5.  Any damage to equipment/furniture will be charged at replacement value.\r\n 6.  Respect other guests  keep volume at room level.\r\n 7.  Order from our à la carte menu. We’ll serve right to your karaoke room 😊', 'Confirmed', NULL, '2026-06-23 12:54:33', '2026-06-25 10:54:04'),
(10, 'DILASNI', '766604629', '2026-06-26', '12:00:00', '15:00:00', 4, 'KAROKE ROOM', 'Karaoke Room Rules  \r\n 1.  Rooms are charged by the hour. Advance booking recommended.  \r\n 2.  Outside food not allowed, except birthday cakes + decorations.  \r\n 3.  BYOB welcome. Shisha available. No cigarette/weed smoking.  \r\n 4.  Please handle mics, remotes & screens with care.  \r\n 5.  Any damage to equipment/furniture will be charged at replacement value.  \r\n 6.  Respect other guests  keep volume at room level.', 'Confirmed', 6, '2026-06-26 05:05:21', '2026-06-26 05:05:21'),
(11, 'AMANDA NUGARA', '0773882586', '2026-06-27', '20:15:00', '22:15:00', 7, 'KARAOKE ROOM', 'Karaoke Room Rules  \r\n 1.  Rooms are charged by the hour. Advance booking recommended.  \r\n 2.  Outside food not allowed, except birthday cakes + decorations.  \r\n 3.  BYOB welcome. Shisha available. No cigarette/weed smoking.  \r\n 4.  Please handle mics, remotes & screens with care.  \r\n 5.  Any damage to equipment/furniture will be charged at replacement value.  \r\n 6.  Respect other guests keep volume at room level.', 'Confirmed', 6, '2026-06-26 19:07:48', '2026-06-26 19:07:48'),
(12, 'DULMIN DHARMA', '0719956970', '2026-06-29', '13:00:00', '16:00:00', 4, 'KARAOKE ROOM', 'Karaoke Room Rules  \r\n 1.  Rooms are charged by the hour. Advance booking recommended.  \r\n 2.  Outside food not allowed, except birthday cakes + decorations.  \r\n 3.  BYOB welcome. Shisha available. No cigarette/weed smoking.  \r\n 4.  Please handle mics, remotes & screens with care.  \r\n 5.  Any damage to equipment/furniture will be charged at replacement value.  \r\n 6.  Respect other guests — keep volume at room level.', 'Confirmed', 6, '2026-06-26 19:16:28', '2026-06-26 19:29:29'),
(13, 'ALFAN', '0761000424', '2026-06-27', '10:00:00', '12:00:00', 3, 'KARAOKE ROOM', '', 'Confirmed', 6, '2026-06-26 19:19:02', '2026-06-26 19:19:02'),
(14, 'PRASANA', '0775833304', '2026-06-28', '14:00:00', '19:00:00', 15, 'BIG CABANA', 'Party liquor', 'Confirmed', 6, '2026-06-26 19:23:57', '2026-06-26 19:23:57'),
(15, 'YEHANSA', '0777910621', '2026-06-28', '14:00:00', '16:00:00', 6, 'KAROKE ROOM', '', 'Confirmed', 6, '2026-06-26 19:26:14', '2026-06-27 05:32:37'),
(16, 'Madu', '0707711019', '2026-06-28', '16:15:00', '07:15:00', 8, 'KAROKE ROOM', '', 'Confirmed', 6, '2026-06-27 05:23:09', '2026-06-27 05:23:09'),
(17, 'Haroon', '0760858732', '2026-06-27', '15:00:00', '17:00:00', 2, 'Private room', '2 hours free. Additional hours: 1500 LKR per hour.\r\n\r\nCafé Rules:\r\n 1.  Outside food isn’t allowed, except birthday cakes and decorations.\r\n 2.  BYOB permitted.\r\n 3.  Shisha allowed. No smoking (weed/cigarettes).\r\n 4.  Order from our à la carte menu.\r\n 5.  You’ll pay for any damaged items at full replacement cost.', 'Confirmed', 6, '2026-06-27 08:19:01', '2026-06-27 08:19:01'),
(19, 'RAIHAN', '0757383237', '2026-06-28', '16:00:00', '18:00:00', 2, 'Karaoke room', 'Karaoke Room Rules  \r\n 1.	 Rooms are charged by the hour. Advance booking recommended.  \r\n 2.	 Outside food not allowed, except birthday cakes + decorations.  \r\n 3.	 BYOB welcome. Shisha available. No cigarette/weed smoking.  \r\n 4.	 Please handle mics, remotes & screens with care.  \r\n 5.	 Any damage to equipment/furniture will be charged at replacement value.  \r\n 6.	 Respect other guests  keep volume at room level.', 'Confirmed', 6, '2026-06-27 08:59:21', '2026-06-27 08:59:21'),
(20, 'Raihan', '0757383237', '2026-06-28', '15:00:00', '16:00:00', 2, 'PRIVATE DINING ROOM', 'Notes:* 2 hours free. Additional hours: 1500 LKR per hour.\r\n\r\nCafé Rules:\r\n 1.  Outside food isn’t allowed, except birthday cakes and decorations.\r\n 2.  BYOB permitted.\r\n 3.  Shisha allowed. No smoking (weed/cigarettes).\r\n 4.  Order from our à la carte menu.\r\n 5.  You’ll pay for any damaged items at full replacement cost.', 'Confirmed', 6, '2026-06-27 09:02:22', '2026-06-27 09:03:47'),
(21, 'STELLA RACHEL', '0701430892', '2026-06-27', '17:30:00', '21:30:00', 6, 'Caban small', '', 'Confirmed', 6, '2026-06-27 10:08:03', '2026-06-27 10:48:46'),
(24, 'Nuzki', '0760888722', '2026-07-03', '19:00:00', '00:00:00', 12, 'Big cabana', '', 'Cancelled', 6, '2026-07-03 07:57:24', '2026-07-03 12:54:54'),
(26, 'SARA', '0719843221', '2026-07-04', '16:30:00', '17:30:00', 2, 'Karaoke', '', 'Confirmed', 6, '2026-07-04 11:10:12', '2026-07-04 11:10:12'),
(27, 'DHINOSHA', '0770119503', '2026-07-05', '14:00:00', '17:00:00', 5, 'CABANA SMALL', 'Birthday party', 'Confirmed', 6, '2026-07-05 08:38:25', '2026-07-05 08:38:25'),
(28, 'KUMUTH', '0701489030', '2026-07-05', '14:00:00', '16:00:00', 6, 'KAROKE ROOM', '', 'Confirmed', 6, '2026-07-05 08:40:06', '2026-07-05 08:40:06'),
(29, 'GIHAN', '0766201241', '2026-07-06', '16:00:00', '18:00:00', 2, 'Karaoke room', '', 'Confirmed', 5, '2026-07-06 17:24:18', '2026-07-06 17:24:18'),
(30, 'ANUSHI', '0761480843', '2026-07-06', '19:00:00', '21:30:00', 13, 'Cabana big', '', 'Confirmed', 5, '2026-07-06 17:27:54', '2026-07-06 17:27:54'),
(31, 'Hiru', '076751865', '2026-07-05', '16:00:00', '16:30:00', 2, 'Karaoke', '', 'Confirmed', 5, '2026-07-06 17:30:37', '2026-07-06 17:30:37'),
(32, 'Hiru', '076751865', '2026-07-05', '16:00:00', '16:30:00', 2, 'Karaoke', '', 'Confirmed', 6, '2026-07-07 10:58:22', '2026-07-07 10:58:22'),
(33, 'MINHAR AZEEZ', '0773166394', '2026-07-08', '21:30:00', '00:30:00', 4, 'Karaoke Room', '', 'Confirmed', 6, '2026-07-08 09:05:52', '2026-07-08 09:05:52');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'business_name', 'Cove Cafe & Lounge', '2026-06-23 08:19:18'),
(2, 'address', '34/4 Kirulopana Avenue, Kirulopana , Colombo 05 , Sri Lanka', '2026-06-26 19:40:10'),
(3, 'phone', '077 229 8545', '2026-06-26 19:37:25'),
(4, 'email', 'coveprivateltd@gmail.com', '2026-06-26 19:40:10'),
(5, 'service_charge_pct', '10', '2026-06-23 03:48:31'),
(6, 'tax_pct', '00', '2026-07-08 10:19:02'),
(7, 'ubereats_commission', '00', '2026-07-08 04:36:21'),
(8, 'pickme_commission', '00', '2026-07-08 04:36:21'),
(9, 'currency', 'Rs.', '2026-06-23 03:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `stock_purchases`
--

CREATE TABLE `stock_purchases` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `purchase_date` date NOT NULL,
  `qty` decimal(10,3) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `payment_method` enum('Cash','Card','Bank Transfer') DEFAULT 'Cash',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_usage`
--

CREATE TABLE `stock_usage` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `usage_date` date NOT NULL,
  `qty` decimal(10,3) NOT NULL,
  `source` enum('bill','manual') DEFAULT 'bill',
  `bill_id` int(11) DEFAULT NULL,
  `menu_item_name` varchar(150) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_usage`
--

INSERT INTO `stock_usage` (`id`, `item_id`, `usage_date`, `qty`, `source`, `bill_id`, `menu_item_name`, `notes`, `created_at`) VALUES
(1, 2, '2026-07-10', '200.000', 'bill', 19, 'Devilled Chicken ', NULL, '2026-07-10 16:27:23'),
(2, 24, '2026-07-10', '20.000', 'bill', 19, 'Devilled Chicken ', NULL, '2026-07-10 16:27:23'),
(3, 25, '2026-07-10', '20.000', 'bill', 19, 'Devilled Chicken ', NULL, '2026-07-10 16:27:23'),
(4, 2, '2026-07-10', '200.000', 'bill', 21, 'Devilled Chicken ', NULL, '2026-07-10 17:19:53'),
(5, 24, '2026-07-10', '20.000', 'bill', 21, 'Devilled Chicken ', NULL, '2026-07-10 17:19:53'),
(6, 25, '2026-07-10', '20.000', 'bill', 21, 'Devilled Chicken ', NULL, '2026-07-10 17:19:53'),
(7, 34, '2026-07-10', '4.000', 'bill', 24, 'PAAN', NULL, '2026-07-10 18:01:34'),
(8, 35, '2026-07-10', '40.000', 'bill', 24, 'PAAN', NULL, '2026-07-10 18:01:34'),
(9, 36, '2026-07-10', '4.000', 'bill', 24, 'PAAN', NULL, '2026-07-10 18:01:34'),
(10, 2, '2026-07-11', '200.000', 'bill', 28, 'Devilled Chicken ', NULL, '2026-07-11 17:54:39'),
(11, 24, '2026-07-11', '20.000', 'bill', 28, 'Devilled Chicken ', NULL, '2026-07-11 17:54:39'),
(12, 25, '2026-07-11', '20.000', 'bill', 28, 'Devilled Chicken ', NULL, '2026-07-11 17:54:39'),
(13, 34, '2026-07-11', '2.000', 'bill', 31, 'PAAN', NULL, '2026-07-11 18:19:32'),
(14, 35, '2026-07-11', '20.000', 'bill', 31, 'PAAN', NULL, '2026-07-11 18:19:32'),
(15, 36, '2026-07-11', '2.000', 'bill', 31, 'PAAN', NULL, '2026-07-11 18:19:32'),
(16, 12, '2026-07-11', '150.000', 'bill', 31, 'Watermelon ', NULL, '2026-07-11 18:19:32'),
(17, 17, '2026-07-11', '150.000', 'bill', 31, 'Mango', NULL, '2026-07-11 18:19:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'cashier',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `active`, `created_at`) VALUES
(1, 'Administrator', 'admin', 'admin123', 'admin', 1, '2026-06-23 03:48:30'),
(2, 'Manager', 'manager', 'manager123', 'manager', 1, '2026-06-23 03:48:30'),
(3, 'Cashier', 'cashier', 'cashier123', 'cashier', 1, '2026-06-23 03:48:30'),
(4, 'Kitchen Boy', 'kitchen', 'kitchen123', 'kitchen', 1, '2026-06-23 03:48:30'),
(5, 'Aski Ahamed', 'aski', 'aski123', 'admin', 1, '2026-06-23 08:46:13'),
(6, 'Ragee', 'Ragee', 'Ragee123', 'admin', 1, '2026-06-23 11:38:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `bill_no` (`bill_no`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `bill_promotions`
--
ALTER TABLE `bill_promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

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
  ADD KEY `category_id` (`category_id`);

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
-- Indexes for table `menu_item_recipes`
--
ALTER TABLE `menu_item_recipes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_menu_inv` (`menu_item_id`,`inventory_item_id`),
  ADD KEY `inventory_item_id` (`inventory_item_id`);

--
-- Indexes for table `online_orders`
--
ALTER TABLE `online_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`);

--
-- Indexes for table `online_order_items`
--
ALTER TABLE `online_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_emp_month` (`employee_id`,`pay_month`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `stock_usage`
--
ALTER TABLE `stock_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

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
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=230;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `bill_promotions`
--
ALTER TABLE `bill_promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `debtors`
--
ALTER TABLE `debtors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `debtor_payments`
--
ALTER TABLE `debtor_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT for table `menu_item_recipes`
--
ALTER TABLE `menu_item_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `online_orders`
--
ALTER TABLE `online_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `online_order_items`
--
ALTER TABLE `online_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `stock_purchases`
--
ALTER TABLE `stock_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_usage`
--
ALTER TABLE `stock_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bill_promotions`
--
ALTER TABLE `bill_promotions`
  ADD CONSTRAINT `bill_promotions_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `debtor_payments`
--
ALTER TABLE `debtor_payments`
  ADD CONSTRAINT `debtor_payments_ibfk_1` FOREIGN KEY (`debtor_id`) REFERENCES `debtors` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`);

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
-- Constraints for table `menu_item_recipes`
--
ALTER TABLE `menu_item_recipes`
  ADD CONSTRAINT `menu_item_recipes_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_item_recipes_ibfk_2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `online_order_items`
--
ALTER TABLE `online_order_items`
  ADD CONSTRAINT `online_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `online_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `stock_purchases`
--
ALTER TABLE `stock_purchases`
  ADD CONSTRAINT `stock_purchases_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`);

--
-- Constraints for table `stock_usage`
--
ALTER TABLE `stock_usage`
  ADD CONSTRAINT `stock_usage_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
