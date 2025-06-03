-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 01, 2025 at 12:40 PM
-- Server version: 8.0.31
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `slotapp`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'IGT', 'International Game Technology', '2025-05-26 11:02:35', '2025-05-26 11:02:35'),
(2, 'Aristocrat', 'Aristocrat Leisure Limited', '2025-05-26 11:02:35', '2025-05-26 11:02:35'),
(8, 'EGT', 'Elite Gaming Technology', '2025-05-27 05:46:56', '2025-05-29 09:57:08'),
(9, 'Gambee', 'SUPERIOR GAMING EXPERIENCE', '2025-05-27 08:16:51', '2025-05-29 09:57:46'),
(10, 'MD', 'Magic Dreams', '2025-05-27 08:19:03', '2025-05-27 08:19:03'),
(5, 'Novomatic', 'Novomatic AG', '2025-05-26 11:02:35', '2025-05-26 11:02:35'),
(6, 'Bally', 'Bally Entertainment', '2025-05-26 11:33:15', '2025-05-29 09:56:44'),
(11, 'Gold Club', NULL, '2025-05-27 08:19:37', '2025-05-27 08:19:37');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
CREATE TABLE IF NOT EXISTS `logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'logout', 'User logged out', '::1', '2025-05-26 11:32:35'),
(2, 1, 'create_brand', 'Created brand: Bally', '::1', '2025-05-26 11:33:15'),
(3, 1, 'create_machine', 'Created machine: 359', '::1', '2025-05-26 11:42:23'),
(4, 1, 'delete_brand', 'Deleted brand: Konami', '::1', '2025-05-27 05:11:13'),
(5, 1, 'delete_brand', 'Deleted brand: Scientific Games', '::1', '2025-05-27 05:11:23'),
(6, 1, 'create_transaction', 'Created Handpay transaction for machine 359: $1,323.00', '::1', '2025-05-27 05:14:28'),
(7, 1, 'create_machine', 'Created machine: 61', '::1', '2025-05-27 05:20:09'),
(8, 1, 'delete_machine', 'Deleted machine: 61', '::1', '2025-05-27 05:43:46'),
(9, 1, 'create_machine', 'Created machine: aaa', '::1', '2025-05-27 05:44:03'),
(10, 1, 'delete_machine', 'Deleted machine: aaa', '::1', '2025-05-27 05:44:08'),
(11, 1, 'create_brand', 'Created brand: sss', '::1', '2025-05-27 05:44:18'),
(12, 1, 'update_brand', 'Updated brand: EGT', '::1', '2025-05-27 05:46:24'),
(13, 1, 'update_brand', 'Updated brand: Bally', '::1', '2025-05-27 05:46:31'),
(14, 1, 'delete_brand', 'Deleted brand: EGT', '::1', '2025-05-27 05:46:44'),
(15, 1, 'create_brand', 'Created brand: EGT', '::1', '2025-05-27 05:46:56'),
(16, 1, 'update_transaction', 'Updated transaction ID: 1', '::1', '2025-05-27 06:20:08'),
(17, 1, 'create_machine', 'Created machine: 327', '::1', '2025-05-27 08:15:22'),
(18, 1, 'create_machine', 'Created machine: 54', '::1', '2025-05-27 08:15:43'),
(19, 1, 'create_brand', 'Created brand: Gambee', '::1', '2025-05-27 08:16:51'),
(20, 1, 'update_brand', 'Updated brand: EGT', '::1', '2025-05-27 08:18:14'),
(21, 1, 'update_brand', 'Updated brand: Gambee', '::1', '2025-05-27 08:18:18'),
(22, 1, 'update_brand', 'Updated brand: Novomatic', '::1', '2025-05-27 08:18:28'),
(23, 1, 'create_brand', 'Created brand: MD', '::1', '2025-05-27 08:19:03'),
(24, 1, 'create_brand', 'Created brand: Gold Club', '::1', '2025-05-27 08:19:37'),
(25, 1, 'create_machine', 'Created machine: 312', '::1', '2025-05-27 08:20:36'),
(26, 1, 'create_transaction', 'Created Cash Drop transaction for machine 312: $1,388.00', '::1', '2025-05-27 08:21:09'),
(27, 1, 'create_transaction', 'Created Cash Drop transaction for machine 327: $188.00', '::1', '2025-05-27 08:21:32'),
(28, 1, 'create_transaction', 'Created Handpay transaction for machine 312: $1,900.00', '::1', '2025-05-27 08:22:35'),
(29, 1, 'delete_transaction', 'Deleted transaction ID: 4', '::1', '2025-05-27 08:24:04'),
(30, 1, 'create_transaction', 'Created Handpay transaction for machine 312: $1,999.00', '::1', '2025-05-27 08:24:23'),
(31, 1, 'create_transaction', 'Created Refill transaction for machine 54: $125.00', '::1', '2025-05-27 08:31:46'),
(32, 1, 'logout', 'User logged out', '::1', '2025-05-27 09:01:03'),
(33, 3, 'logout', 'User logged out', '::1', '2025-05-27 09:03:37'),
(34, 2, 'logout', 'User logged out', '::1', '2025-05-27 09:04:37'),
(35, 1, 'create_machine', 'Created machine: 353', '::1', '2025-05-27 09:18:51'),
(36, 1, 'update_machine', 'Updated machine: 353', '::1', '2025-05-27 09:18:55'),
(37, 1, 'update_machine', 'Updated machine: 353', '::1', '2025-05-27 09:18:59'),
(38, 1, 'update_machine', 'Updated machine: 353', '::1', '2025-05-27 09:19:03'),
(39, 1, 'create_transaction', 'Created Cash Drop transaction for machine 353: $660.00', '192.168.17.207', '2025-05-27 13:01:28'),
(40, 1, 'logout', 'User logged out', '::1', '2025-05-27 13:11:50'),
(41, 3, 'logout', 'User logged out', '::1', '2025-05-27 17:53:35'),
(42, 1, 'create_machine', 'Created machine: 61', '::1', '2025-05-27 17:54:35'),
(43, 1, 'create_transaction', 'Created Refill transaction for machine 61: $125.00', '::1', '2025-05-27 17:55:13'),
(44, 1, 'delete_transaction', 'Deleted transaction ID: 5', '::1', '2025-05-27 17:58:02'),
(45, 1, 'create_machine', 'Created machine: 77', '::1', '2025-05-28 09:19:10'),
(46, 1, 'create_transaction', 'Created Cash Drop transaction for machine 77: 1,200.00', '::1', '2025-05-28 09:19:32'),
(47, 1, 'create_transaction', 'Created Handpay transaction for machine 77: 15.00', '::1', '2025-05-28 09:19:48'),
(48, 1, 'create_transaction', 'Created Coins Drop transaction for machine 54: 266.00', '::1', '2025-05-28 09:22:46'),
(49, 1, 'update_machine', 'Updated machine: 353', '::1', '2025-05-28 09:23:30'),
(50, 1, 'create_machine', 'Created machine: 1', '::1', '2025-05-28 09:24:49'),
(51, 1, 'create_transaction', 'Created Cash Drop transaction for machine 1: 144.00', '::1', '2025-05-28 09:25:04'),
(52, 1, 'create_transaction', 'Created Ticket transaction for machine 1: 12.00', '::1', '2025-05-28 09:25:14'),
(53, 1, 'update_transaction', 'Updated transaction ID: 10', '::1', '2025-05-28 09:25:34'),
(54, 1, 'update_transaction', 'Updated transaction ID: 9', '::1', '2025-05-28 09:26:40'),
(55, 1, 'delete_transaction', 'Deleted transaction ID: 9', '::1', '2025-05-28 09:27:50'),
(56, 1, 'create_transaction', 'Created Cash Drop transaction for machine 77: 1,200.00', '::1', '2025-05-28 09:28:18'),
(57, 1, 'create_machine', 'Created machine: 122', '::1', '2025-05-28 09:42:27'),
(58, 1, 'create_transaction', 'Created Handpay transaction for machine 122: 10.00', '::1', '2025-05-28 09:42:43'),
(59, 1, 'delete_transaction', 'Deleted transaction ID: 10', '::1', '2025-05-28 09:43:11'),
(60, 1, 'delete_transaction', 'Deleted transaction ID: 14', '::1', '2025-05-28 09:43:20'),
(61, 1, 'create_transaction', 'Created Handpay transaction for machine 77: 10.00', '::1', '2025-05-28 09:43:35'),
(62, 1, 'create_transaction', 'Created Cash Drop transaction for machine 77: 100.00', '::1', '2025-05-28 09:43:43'),
(63, 1, 'update_machine', 'Updated machine: 770', '::1', '2025-05-28 09:44:21'),
(64, 1, 'update_machine', 'Updated machine: 770', '::1', '2025-05-28 09:44:50'),
(65, 1, 'create_machine', 'Created machine: 999', '::1', '2025-05-28 09:45:26'),
(66, 1, 'create_transaction', 'Created Handpay transaction for machine 999: 10.00', '::1', '2025-05-28 09:45:34'),
(67, 1, 'create_transaction', 'Created Cash Drop transaction for machine 999: 88.50', '::1', '2025-05-28 09:45:46'),
(68, 1, 'update_machine', 'Updated machine: 1', '::1', '2025-05-28 09:46:26'),
(69, 1, 'create_brand', 'Created brand: saleh', '::1', '2025-05-28 09:47:16'),
(70, 1, 'create_machine', 'Created machine: 8', '::1', '2025-05-28 09:47:36'),
(71, 1, 'create_transaction', 'Created Ticket transaction for machine 8: 120.00', '::1', '2025-05-28 09:47:49'),
(72, 1, 'create_transaction', 'Created Cash Drop transaction for machine 8: 500.00', '::1', '2025-05-28 09:47:59'),
(73, 1, 'create_machine', 'Created machine: 777', '::1', '2025-05-28 11:32:05'),
(74, 1, 'create_transaction', 'Created Cash Drop transaction for machine 777: 100.00', '::1', '2025-05-28 11:32:16'),
(75, 1, 'create_transaction', 'Created Ticket transaction for machine 777: 20.00', '::1', '2025-05-28 11:32:25'),
(76, 1, 'delete_machine', 'Deleted machine: 999', '::1', '2025-05-28 11:32:47'),
(77, 1, 'delete_machine', 'Deleted machine: 8', '::1', '2025-05-28 11:33:33'),
(78, 1, 'delete_machine', 'Deleted machine: 777', '::1', '2025-05-29 06:13:53'),
(79, 1, 'create_transaction', 'Created Cash Drop transaction for machine 770: 40.00', '::1', '2025-05-29 07:18:37'),
(80, 1, 'create_machine', 'Created machine: 97', '::1', '2025-05-29 07:36:53'),
(81, 1, 'create_transaction', 'Created Cash Drop transaction for machine 97: 200.00', '::1', '2025-05-29 07:37:08'),
(82, 1, 'create_transaction', 'Created Ticket transaction for machine 97: 190.00', '::1', '2025-05-29 07:37:21'),
(83, 1, 'delete_brand', 'Deleted brand: saleh', '::1', '2025-05-29 09:23:25'),
(84, 1, 'update_brand', 'Updated brand: Bally', '::1', '2025-05-29 09:56:45'),
(85, 1, 'update_brand', 'Updated brand: EGT', '::1', '2025-05-29 09:57:08'),
(86, 1, 'update_brand', 'Updated brand: Gambee', '::1', '2025-05-29 09:57:46'),
(87, 1, 'logout', 'User logged out', '::1', '2025-05-29 17:37:25'),
(88, 1, 'create_transaction', 'Created Cash Drop transaction for machine 122: 115.00', '::1', '2025-05-30 09:52:47'),
(89, 1, 'update_transaction', 'Updated transaction ID: 27', '::1', '2025-05-30 09:56:38'),
(90, 1, 'create_transaction', 'Created Cash Drop transaction for machine 359: 1,250.00', '::1', '2025-05-30 09:57:08'),
(91, 1, 'logout', 'User logged out', '::1', '2025-05-30 11:31:03'),
(92, 1, 'logout', 'User logged out', '::1', '2025-05-30 11:43:21'),
(93, 4, 'create_transaction', 'Created Coins Drop transaction for machine 61: 198.00', '::1', '2025-05-30 11:54:18'),
(94, 4, 'create_transaction', 'Created Refill transaction for machine 122: 125.00', '::1', '2025-05-30 17:16:11'),
(95, 4, 'create_transaction', 'Created Handpay transaction for machine 1: 200.00', '::1', '2025-05-30 17:16:36'),
(96, 4, 'logout', 'User logged out', '::1', '2025-05-31 08:44:55'),
(97, 3, 'logout', 'User logged out', '::1', '2025-05-31 08:45:19'),
(98, 2, 'logout', 'User logged out', '::1', '2025-05-31 08:45:36'),
(99, 3, 'logout', 'User logged out', '::1', '2025-05-31 08:54:13'),
(100, 4, 'logout', 'User logged out', '::1', '2025-05-31 08:55:20'),
(101, 5, 'create_transaction', 'Created Cash Drop transaction for machine 359: 144.00', '::1', '2025-05-31 09:03:35'),
(102, 5, 'create_transaction', 'Created Handpay transaction for machine 327: 122.00', '::1', '2025-05-31 09:03:44'),
(103, 5, 'create_transaction', 'Created Cash Drop transaction for machine 1: 1,000.00', '::1', '2025-05-31 12:09:01'),
(104, 5, 'update_transaction', 'Updated transaction ID: 32', '::1', '2025-05-31 12:09:10'),
(105, 5, 'create_transaction', 'Created Handpay transaction for machine 61: 5,000.00', '::1', '2025-05-31 12:13:50'),
(106, 5, 'update_transaction', 'Updated transaction ID: 35', '::1', '2025-05-31 12:14:07'),
(107, 5, 'delete_transaction', 'Deleted transaction ID: 35', '::1', '2025-05-31 12:14:15'),
(108, 5, 'update_transaction', 'Updated transaction ID: 33', '::1', '2025-05-31 12:14:34'),
(109, 5, 'update_transaction', 'Updated transaction ID: 29', '::1', '2025-05-31 12:14:43'),
(110, 5, 'update_transaction', 'Updated transaction ID: 30', '::1', '2025-05-31 12:14:52'),
(111, 5, 'update_transaction', 'Updated transaction ID: 27', '::1', '2025-05-31 12:15:00'),
(112, 5, 'update_transaction', 'Updated transaction ID: 6', '::1', '2025-05-31 12:15:08'),
(113, 5, 'update_transaction', 'Updated transaction ID: 28', '::1', '2025-05-31 12:15:15'),
(114, 5, 'update_transaction', 'Updated transaction ID: 8', '::1', '2025-05-31 12:15:23'),
(115, 5, 'update_transaction', 'Updated transaction ID: 7', '::1', '2025-05-31 12:15:32'),
(116, 5, 'update_transaction', 'Updated transaction ID: 15', '::1', '2025-05-31 12:15:41'),
(117, 5, 'update_transaction', 'Updated transaction ID: 13', '::1', '2025-05-31 12:15:46'),
(118, 5, 'update_transaction', 'Updated transaction ID: 12', '::1', '2025-05-31 12:15:55'),
(119, 5, 'update_transaction', 'Updated transaction ID: 16', '::1', '2025-05-31 12:16:04'),
(120, 5, 'update_transaction', 'Updated transaction ID: 11', '::1', '2025-05-31 12:16:10'),
(121, 5, 'update_transaction', 'Updated transaction ID: 17', '::1', '2025-05-31 12:16:16'),
(122, 5, 'update_transaction', 'Updated transaction ID: 26', '::1', '2025-05-31 12:16:26'),
(123, 5, 'update_transaction', 'Updated transaction ID: 31', '::1', '2025-05-31 12:16:35'),
(124, 5, 'update_transaction', 'Updated transaction ID: 24', '::1', '2025-05-31 12:16:45'),
(125, 5, 'update_transaction', 'Updated transaction ID: 25', '::1', '2025-05-31 12:16:56'),
(126, 5, 'update_transaction', 'Updated transaction ID: 25', '::1', '2025-05-31 12:40:17'),
(127, 1, 'create_transaction', 'Created Cash Drop transaction for machine 97: 100.00', '::1', '2025-06-01 08:56:02'),
(128, 1, 'create_transaction', 'Created Handpay transaction for machine 97: 50.00', '::1', '2025-06-01 08:56:10'),
(129, 1, 'create_machine', 'Created machine: 325', '::1', '2025-06-01 09:09:25'),
(130, 1, 'create_machine', 'Created machine: 354', '::1', '2025-06-01 09:10:04'),
(131, 1, 'update_machine', 'Updated machine: 105', '::1', '2025-06-01 12:25:47'),
(132, 1, 'update_machine', 'Updated machine: 122', '::1', '2025-06-01 12:26:04'),
(133, 1, 'create_machine', 'Created machine: 338', '::1', '2025-06-01 12:30:20'),
(134, 1, 'create_transaction', 'Created Cash Drop transaction for machine 338: 190.00', '::1', '2025-06-01 12:30:43'),
(135, 1, 'update_machine', 'Updated machine: 276', '::1', '2025-06-01 12:31:54'),
(136, 1, 'create_transaction', 'Created Ticket transaction for machine 105: 37.50', '::1', '2025-06-01 12:38:37'),
(137, 1, 'create_transaction', 'Created Refill transaction for machine 61: 125.00', '::1', '2025-06-01 12:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

DROP TABLE IF EXISTS `machines`;
CREATE TABLE IF NOT EXISTS `machines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `machine_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `brand_id` int DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('CASH','COINS','GAMBEE') COLLATE utf8mb4_general_ci NOT NULL,
  `credit_value` decimal(10,2) NOT NULL,
  `manufacturing_year` int DEFAULT NULL,
  `ip_address` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mac_address` varchar(17) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Active','Inactive','Maintenance','Reserved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `machine_number` (`machine_number`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `brand_id` (`brand_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `machines`
--

INSERT INTO `machines` (`id`, `machine_number`, `brand_id`, `model`, `type`, `credit_value`, `manufacturing_year`, `ip_address`, `mac_address`, `serial_number`, `status`, `created_at`, `updated_at`) VALUES
(1, '359', 6, 'Top', 'CASH', '0.01', 2019, NULL, NULL, NULL, 'Active', '2025-05-26 11:42:23', '2025-05-27 05:27:25'),
(4, '327', 8, 'Ambola', 'CASH', '0.01', 2001, NULL, NULL, NULL, 'Active', '2025-05-27 08:15:22', '2025-05-27 08:15:22'),
(5, '54', 1, 'Reels', 'COINS', '0.01', 1995, NULL, NULL, NULL, 'Active', '2025-05-27 08:15:43', '2025-05-27 08:15:43'),
(6, '312', 9, 'GP4 - GP Electronic Roulette', 'GAMBEE', '0.25', 2006, NULL, NULL, NULL, 'Active', '2025-05-27 08:20:36', '2025-05-27 08:20:36'),
(7, '353', 11, 'N/A', 'CASH', '0.01', 2006, NULL, NULL, NULL, 'Active', '2025-05-27 09:18:51', '2025-05-28 09:23:30'),
(8, '61', 1, 'Reels', 'COINS', '0.25', 1902, NULL, NULL, 'dsdf', 'Active', '2025-05-27 17:54:35', '2025-05-27 17:54:35'),
(9, '770', 6, 'Magic Touch', 'CASH', '0.25', 2001, NULL, NULL, NULL, 'Active', '2025-05-28 09:19:10', '2025-05-28 09:44:50'),
(10, '105', 10, 'Number 1', 'CASH', '0.25', 1910, NULL, NULL, NULL, 'Active', '2025-05-28 09:24:49', '2025-06-01 12:25:47'),
(11, '122', 2, 'Multi Game', 'CASH', '1.00', NULL, NULL, NULL, NULL, 'Active', '2025-05-28 09:42:27', '2025-06-01 12:26:04'),
(16, '325', 1, 'G23', 'CASH', '0.01', 2005, NULL, NULL, NULL, 'Maintenance', '2025-06-01 09:09:25', '2025-06-01 09:09:25'),
(15, '276', 10, 'Shamboury', 'CASH', '1.00', 1999, NULL, NULL, NULL, 'Active', '2025-05-29 07:36:53', '2025-06-01 12:31:54'),
(17, '354', 11, 'N/A', 'CASH', '0.05', 2009, NULL, NULL, NULL, 'Inactive', '2025-06-01 09:10:04', '2025-06-01 09:10:04'),
(18, '338', 5, 'Admiral', 'CASH', '0.01', 2015, NULL, NULL, NULL, 'Active', '2025-06-01 12:30:20', '2025-06-01 12:30:20');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `machine_id` int NOT NULL,
  `transaction_type_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  KEY `transaction_type_id` (`transaction_type_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `machine_id`, `transaction_type_id`, `amount`, `timestamp`, `user_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '1500.00', '2025-05-26 02:13:00', 1, NULL, '2025-05-27 05:14:28', '2025-05-27 06:20:08'),
(2, 6, 5, '1388.00', '2025-05-27 05:20:00', 1, NULL, '2025-05-27 08:21:09', '2025-05-27 08:21:09'),
(3, 4, 5, '188.00', '2025-05-27 05:21:00', 1, NULL, '2025-05-27 08:21:32', '2025-05-27 08:21:32'),
(16, 9, 1, '10.00', '2025-05-28 00:41:00', 1, NULL, '2025-05-28 09:43:35', '2025-05-31 12:16:04'),
(6, 5, 3, '125.00', '2025-05-09 09:48:00', 1, NULL, '2025-05-27 08:31:46', '2025-05-31 12:15:08'),
(7, 7, 5, '660.00', '2025-05-27 07:24:00', 1, NULL, '2025-05-27 13:01:28', '2025-05-31 12:15:32'),
(8, 8, 3, '125.00', '2025-05-27 18:06:00', 1, NULL, '2025-05-27 17:55:13', '2025-05-31 12:15:23'),
(17, 9, 5, '100.00', '2025-05-28 08:18:00', 1, NULL, '2025-05-28 09:43:43', '2025-05-31 12:16:16'),
(11, 5, 4, '266.00', '2025-05-28 05:22:00', 1, NULL, '2025-05-28 09:22:46', '2025-05-31 12:16:10'),
(12, 10, 5, '144.00', '2025-05-28 01:33:00', 1, NULL, '2025-05-28 09:25:04', '2025-05-31 12:15:55'),
(13, 10, 2, '12.00', '2025-05-28 19:30:00', 1, NULL, '2025-05-28 09:25:14', '2025-05-31 12:15:46'),
(15, 11, 1, '10.00', '2025-05-28 14:45:00', 1, NULL, '2025-05-28 09:42:43', '2025-05-31 12:15:41'),
(18, 12, 1, '10.00', '2025-05-27 21:00:00', 1, NULL, '2025-05-28 09:45:34', '2025-05-28 09:45:34'),
(19, 12, 5, '88.50', '2025-05-27 21:00:00', 1, NULL, '2025-05-28 09:45:46', '2025-05-28 09:45:46'),
(20, 13, 2, '120.00', '2025-05-27 21:00:00', 1, NULL, '2025-05-28 09:47:49', '2025-05-28 09:47:49'),
(21, 13, 5, '500.00', '2025-05-27 21:00:00', 1, NULL, '2025-05-28 09:47:59', '2025-05-28 09:47:59'),
(22, 14, 5, '100.00', '2025-05-27 21:00:00', 1, NULL, '2025-05-28 11:32:16', '2025-05-28 11:32:16'),
(23, 14, 2, '20.00', '2025-05-27 21:00:00', 1, NULL, '2025-05-28 11:32:25', '2025-05-28 11:32:25'),
(24, 9, 5, '40.00', '2025-05-29 11:59:00', 1, NULL, '2025-05-29 07:18:37', '2025-05-31 12:16:45'),
(25, 15, 5, '200.00', '2025-05-29 07:03:00', 1, NULL, '2025-05-29 07:37:08', '2025-05-31 12:40:17'),
(26, 15, 2, '190.00', '2025-05-29 18:35:00', 1, NULL, '2025-05-29 07:37:21', '2025-05-31 12:16:26'),
(27, 11, 5, '115.00', '2025-05-07 07:30:00', 1, NULL, '2025-05-30 09:52:47', '2025-05-31 12:15:00'),
(28, 1, 5, '1250.00', '2025-05-21 09:15:00', 1, NULL, '2025-05-30 09:57:08', '2025-05-31 12:15:15'),
(29, 8, 4, '198.00', '2025-05-28 18:56:00', 4, NULL, '2025-05-30 11:54:18', '2025-05-31 12:14:43'),
(30, 11, 3, '125.00', '2025-05-30 18:05:00', 4, NULL, '2025-05-30 17:16:11', '2025-05-31 12:14:52'),
(31, 10, 1, '200.00', '2025-05-30 20:23:00', 4, NULL, '2025-05-30 17:16:36', '2025-05-31 12:16:35'),
(32, 1, 5, '144.00', '2025-05-31 12:09:00', 5, NULL, '2025-05-31 09:03:35', '2025-05-31 12:09:10'),
(33, 4, 1, '122.00', '2025-05-27 15:49:00', 5, NULL, '2025-05-31 09:03:44', '2025-05-31 12:14:34'),
(34, 10, 5, '1000.00', '2025-05-31 12:08:00', 5, NULL, '2025-05-31 12:09:01', '2025-05-31 12:09:01'),
(36, 15, 5, '100.00', '2025-06-01 08:55:00', 1, NULL, '2025-06-01 08:56:02', '2025-06-01 08:56:02'),
(37, 15, 1, '50.00', '2025-06-01 08:56:00', 1, NULL, '2025-06-01 08:56:10', '2025-06-01 08:56:10'),
(38, 18, 5, '190.00', '2025-06-01 12:30:00', 1, NULL, '2025-06-01 12:30:43', '2025-06-01 12:30:43'),
(39, 10, 2, '37.50', '2025-06-01 12:38:00', 1, NULL, '2025-06-01 12:38:37', '2025-06-01 12:38:37'),
(40, 8, 3, '125.00', '2025-06-01 12:38:00', 1, NULL, '2025-06-01 12:38:56', '2025-06-01 12:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

DROP TABLE IF EXISTS `transaction_types`;
CREATE TABLE IF NOT EXISTS `transaction_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `category` enum('OUT','DROP') COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`id`, `name`, `category`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Handpay', 'OUT', 'Manual payment to player', '2025-05-26 11:02:35', '2025-05-26 11:02:35'),
(2, 'Ticket', 'OUT', 'Ticket out payment', '2025-05-26 11:02:35', '2025-05-26 11:02:35'),
(3, 'Refill', 'OUT', 'Machine refill', '2025-05-26 11:02:35', '2025-05-26 11:02:35'),
(4, 'Coins Drop', 'DROP', 'Coins inserted by players', '2025-05-26 11:02:35', '2025-05-26 11:02:35'),
(5, 'Cash Drop', 'DROP', 'Cash inserted by players', '2025-05-26 11:02:35', '2025-05-26 11:02:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','editor','viewer') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Active','Inactive','Maintenance','Reserved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$KZNC33ZjRG23TFqr/nsmYutvzZ1XPFJFWf/HmbtlD0x7CUQFcg2Wi', 'Administrator', 'admin@example.com', 'admin', 'Active', '2025-05-26 11:02:35', '2025-05-26 11:06:52'),
(2, 'editor', '$2y$10$5dgbQhs/f9VfqL03yaia/u27/EtTknrDXiH8LphE/tEX4naYiBTEO', 'Editor User', 'editor@example.com', 'editor', 'Active', '2025-05-26 11:02:35', '2025-05-30 11:42:57'),
(3, 'viewer', '$2y$10$XcXYUkLbFB2AScT7966wxOY76dqdq2q4TGS9NMHCYQRbdlHMuEbyO', 'Viewer User', 'viewer@example.com', 'viewer', 'Active', '2025-05-26 11:02:35', '2025-05-27 09:02:31'),
(4, 'Raf', '$2y$10$HXjXy157kPt8KiqpQ6EXX.YIEXGPZt7lacU4mPMBVLQP4q2p0nDPS', 'Rafik Semaan', 'rafiksemaan@gmail.com', 'admin', 'Active', '2025-05-30 11:43:19', '2025-05-30 11:43:19'),
(5, 'Allam', '$2y$10$N75DV/L2/2xpBF13hZZM8eD26CjueJnRGC2Gh4swyKxwq7RjY1UNO', 'Muhamad Allam', 'Allam@Allam.com', 'admin', 'Active', '2025-05-31 08:55:04', '2025-05-31 08:55:04');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
