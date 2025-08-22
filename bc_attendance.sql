-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2025 at 10:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bc_attendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@bcattendance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `beneficiary_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `mandal_id` int(11) NOT NULL,
  `tc_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `mandal_id`, `tc_id`, `name`, `code`, `description`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Batch-1', 'PAR_PAR_B1', 'Batch-1 for Parvathipuram Mandal', '2025-05-07', '2025-08-20', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(2, 1, 1, 'Batch-2', 'PAR_PAR_B2', 'Batch-2 for Parvathipuram Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(3, 2, 2, 'Batch-1', 'PAR_BAL_B1', 'Batch-1 for Balijipeta Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(4, 2, 2, 'Batch-2', 'PAR_BAL_B2', 'Batch-2 for Balijipeta Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(5, 3, 3, 'Batch-1', 'PAR_SEE_B1', 'Batch-1 for Seethanagaram Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(6, 4, 4, 'Batch-1', 'KUR_KUR_B1', 'Batch-1 for Kurupam Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(7, 4, 4, 'Batch-2', 'KUR_KUR_B2', 'Batch-2 for Kurupam Mandal', '2025-05-07', '2025-08-20', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(8, 5, 5, 'Batch-1', 'KUR_GLP_B1', 'Batch-1 for GL Puram Mandal', '2025-05-07', '2025-08-20', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(9, 5, 5, 'Batch-2', 'KUR_GLP_B2', 'Batch-2 for GL Puram Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(10, 6, 6, 'Batch-1', 'KUR_JIY_B1', 'Batch-1 for Jiyyammavalasa Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(11, 6, 6, 'Batch-2', 'KUR_JIY_B2', 'Batch-2 for Jiyyammavalasa Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(12, 7, 7, 'Batch-1', 'KUR_KOM_B1', 'Batch-1 for Komarada Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(13, 7, 7, 'Batch-2', 'KUR_KOM_B2', 'Batch-2 for Komarada Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(14, 8, 8, 'Batch-1', 'KUR_GAR_B1', 'Batch-1 for Garugubilli Mandal', '2025-06-16', '2025-09-30', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11'),
(15, 8, 8, 'Batch-2', 'KUR_GAR_B2', 'Batch-2 for Garugubilli Mandal', '2025-05-07', '2025-08-20', 'active', '2025-08-15 06:40:11', '2025-08-15 06:40:11');

-- --------------------------------------------------------

--
-- Table structure for table `beneficiaries`
--

CREATE TABLE `beneficiaries` (
  `id` int(11) NOT NULL,
  `constituency_id` int(11) NOT NULL,
  `mandal_id` int(11) NOT NULL,
  `tc_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `aadhar_number` varchar(12) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `batch_start_date` date DEFAULT NULL,
  `batch_end_date` date DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_upload_log`
--

CREATE TABLE `bulk_upload_log` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `total_records` int(11) NOT NULL,
  `successful_records` int(11) NOT NULL,
  `failed_records` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('completed','failed','partial') NOT NULL,
  `error_log` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `constituencies`
--

CREATE TABLE `constituencies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `constituencies`
--

INSERT INTO `constituencies` (`id`, `name`, `code`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PARVATHIPURAM', 'PAR', 'Parvathipuram Parliamentary Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(2, 'KURUPAM', 'KUR', 'Kurupam Parliamentary Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10');

-- --------------------------------------------------------

--
-- Table structure for table `mandals`
--

CREATE TABLE `mandals` (
  `id` int(11) NOT NULL,
  `constituency_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mandals`
--

INSERT INTO `mandals` (`id`, `constituency_id`, `name`, `code`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'PARVATHIPURAM', 'PAR_PAR', 'Parvathipuram Mandal in Parvathipuram Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(2, 1, 'BALIJIPETA', 'PAR_BAL', 'Balijipeta Mandal in Parvathipuram Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(3, 1, 'SEETHANAGARAM', 'PAR_SEE', 'Seethanagaram Mandal in Parvathipuram Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(4, 2, 'KURUPAM', 'KUR_KUR', 'Kurupam Mandal in Kurupam Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(5, 2, 'GL PURAM', 'KUR_GLP', 'GL Puram Mandal in Kurupam Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(6, 2, 'JIYYAMMAVALASA', 'KUR_JIY', 'Jiyyammavalasa Mandal in Kurupam Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(7, 2, 'KOMARADA', 'KUR_KOM', 'Komarada Mandal in Kurupam Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(8, 2, 'GARUGUBILLI', 'KUR_GAR', 'Garugubilli Mandal in Kurupam Constituency', 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10');

-- --------------------------------------------------------

--
-- Table structure for table `training_centers`
--

CREATE TABLE `training_centers` (
  `id` int(11) NOT NULL,
  `mandal_id` int(11) NOT NULL,
  `tc_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_centers`
--

INSERT INTO `training_centers` (`id`, `mandal_id`, `tc_id`, `name`, `address`, `contact_person`, `phone_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'TTC7430317', 'Parvathipuram Training Center', 'Parvathipuram Mandal Office', NULL, NULL, 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(2, 2, 'TTC7430652', 'Balijipeta Training Center', 'Balijipeta Mandal Office', NULL, NULL, 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(3, 3, 'TTC7430654', 'Seethanagaram Training Center', 'Seethanagaram Mandal Office', NULL, NULL, 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(4, 4, 'TTC7430664', 'Kurupam Training Center', 'Kurupam Mandal Office', NULL, NULL, 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(5, 5, 'TTC7430536', 'GL Puram Training Center', 'GL Puram Mandal Office', NULL, NULL, 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(6, 6, 'TTC7430529', 'Jiyyammavalasa Training Center', 'Jiyyammavalasa Mandal Office', NULL, NULL, 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(7, 7, 'TTC7430543', 'Komarada Training Center', 'Komarada Mandal Office', NULL, NULL, 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10'),
(8, 8, 'TTC7430653', 'Garugubilli Training Center', 'Garugubilli Mandal Office', NULL, NULL, 'active', '2025-08-15 06:40:10', '2025-08-15 06:40:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`beneficiary_id`,`attendance_date`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `mandal_id` (`mandal_id`),
  ADD KEY `tc_id` (`tc_id`);

--
-- Indexes for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aadhar_number` (`aadhar_number`),
  ADD KEY `constituency_id` (`constituency_id`),
  ADD KEY `mandal_id` (`mandal_id`),
  ADD KEY `tc_id` (`tc_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `bulk_upload_log`
--
ALTER TABLE `bulk_upload_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `constituencies`
--
ALTER TABLE `constituencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `mandals`
--
ALTER TABLE `mandals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `constituency_id` (`constituency_id`);

--
-- Indexes for table `training_centers`
--
ALTER TABLE `training_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tc_id` (`tc_id`),
  ADD KEY `mandal_id` (`mandal_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bulk_upload_log`
--
ALTER TABLE `bulk_upload_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `constituencies`
--
ALTER TABLE `constituencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mandals`
--
ALTER TABLE `mandals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `training_centers`
--
ALTER TABLE `training_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`marked_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_ibfk_1` FOREIGN KEY (`mandal_id`) REFERENCES `mandals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `batches_ibfk_2` FOREIGN KEY (`tc_id`) REFERENCES `training_centers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `beneficiaries_ibfk_2` FOREIGN KEY (`mandal_id`) REFERENCES `mandals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `beneficiaries_ibfk_3` FOREIGN KEY (`tc_id`) REFERENCES `training_centers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `beneficiaries_ibfk_4` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bulk_upload_log`
--
ALTER TABLE `bulk_upload_log`
  ADD CONSTRAINT `bulk_upload_log_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `admin_users` (`id`);

--
-- Constraints for table `mandals`
--
ALTER TABLE `mandals`
  ADD CONSTRAINT `mandals_ibfk_1` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_centers`
--
ALTER TABLE `training_centers`
  ADD CONSTRAINT `training_centers_ibfk_1` FOREIGN KEY (`mandal_id`) REFERENCES `mandals` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
