-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2025 at 11:58 AM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.2.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mapping_network`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `secret_username` varchar(150) NOT NULL,
  `odp_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `address`, `secret_username`, `odp_id`, `latitude`, `longitude`, `status`, `created_at`, `description`) VALUES
(4, 'WIGUNO', 'SMKL', '1/1/3:69_WIGUNO-HARI_SMKL@denta.net', 5, '-8.30385932', '112.49622273', 'inactive', '2025-09-06 15:01:20', 'MASUK KE ODP PAKE KABEL CORE TO CORE1'),
(5, 'WIGUNO', 'SMKL SNAPSHOOT', '1/1/3:69_WIGUNO-HARI_SMKL@denta.net', 5, '-8.30112199', '112.49646684', 'inactive', '2025-09-07 17:34:19', 'Kabel DC 1 Core Merek Telkom'),
(6, 'OMAH KIDUL', 'OMAH KIDUL SMKL', '1/1/1:1_OMAHKIDUL@denta.net', 5, '-8.30472803', '112.49539725', 'inactive', '2025-09-07 17:50:01', 'OMAH KDUL');

-- --------------------------------------------------------

--
-- Table structure for table `odc`
--

CREATE TABLE `odc` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `status` enum('planned','installed','active','maintenance','down') DEFAULT 'installed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kapasitas_in` int(11) DEFAULT NULL,
  `kapasitas_out` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `odc`
--

INSERT INTO `odc` (`id`, `name`, `capacity`, `description`, `server_id`, `latitude`, `longitude`, `status`, `created_at`, `kapasitas_in`, `kapasitas_out`) VALUES
(1, 'COBA ODC 001', 16, '*Kabel Fig8 8 Core Input\r\n*Kabel DC 8 Core Out\r\n', 1, -8.300915516263753, 112.49650171554606, 'active', '2025-09-07 17:32:04', 8, 8);

-- --------------------------------------------------------

--
-- Table structure for table `odp`
--

CREATE TABLE `odp` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 0,
  `server_id` int(11) DEFAULT NULL,
  `odc_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `parent_server_id` int(11) DEFAULT NULL,
  `parent_odp_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `status` enum('planned','installed','active','degraded','down') DEFAULT 'installed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `odp`
--

INSERT INTO `odp` (`id`, `name`, `capacity`, `server_id`, `odc_id`, `latitude`, `longitude`, `parent_server_id`, `parent_odp_id`, `created_at`, `description`, `status`) VALUES
(5, 'ODP PGK-01/01-KTR-DSG', 8, 1, 1, '-8.30091088', '112.49655860', NULL, NULL, '2025-09-05 20:01:57', 'Menggunaka Kabel 8 Core Sebagai Input Warna Biru', 'installed');

-- --------------------------------------------------------

--
-- Table structure for table `servers`
--

CREATE TABLE `servers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `servers`
--

INSERT INTO `servers` (`id`, `name`, `description`, `latitude`, `longitude`, `created_at`) VALUES
(1, 'SERVER DSG SMKL', 'SERVER SMKL\r\nTANJUNGSARI 01', '-8.30089579', '112.49652350', '2025-09-04 18:03:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `odp_id` (`odp_id`);

--
-- Indexes for table `odc`
--
ALTER TABLE `odc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `odp`
--
ALTER TABLE `odp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_server_id` (`parent_server_id`),
  ADD KEY `idx_odp_odc_id` (`odc_id`),
  ADD KEY `idx_odp_parent` (`parent_odp_id`);

--
-- Indexes for table `servers`
--
ALTER TABLE `servers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `odc`
--
ALTER TABLE `odc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `odp`
--
ALTER TABLE `odp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `servers`
--
ALTER TABLE `servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`odp_id`) REFERENCES `odp` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `odp`
--
ALTER TABLE `odp`
  ADD CONSTRAINT `fk_odp_odc` FOREIGN KEY (`odc_id`) REFERENCES `odc` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `odp_ibfk_1` FOREIGN KEY (`parent_server_id`) REFERENCES `servers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `odp_ibfk_2` FOREIGN KEY (`parent_odp_id`) REFERENCES `odp` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
