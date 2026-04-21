-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 21, 2026 at 06:08 PM
-- Server version: 12.0.2-MariaDB-log
-- PHP Version: 8.5.0RC4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jszczepa_monetki`
--

-- --------------------------------------------------------

--
-- Table structure for table `dane`
--

CREATE TABLE `dane` (
  `id` int(11) NOT NULL,
  `flaga_id` int(11) DEFAULT NULL,
  `nominal` varchar(100) DEFAULT NULL,
  `nr_kat` varchar(100) DEFAULT NULL,
  `stop_id` int(11) DEFAULT NULL,
  `rok` varchar(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dane`
--

INSERT INTO `dane` (`id`, `flaga_id`, `nominal`, `nr_kat`, `stop_id`, `rok`) VALUES
(1, 1, 'ddds', 'dads', 5, 'sdds'),
(3, 9, 'sfsdf', 'dfsfs', 4, 'sss'),
(4, 1, '', '', 1, '1322'),
(5, 5, 'fdsfs', 'fdsf', 5, '5435');

-- --------------------------------------------------------

--
-- Table structure for table `flagi`
--

CREATE TABLE `flagi` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `flagi`
--

INSERT INTO `flagi` (`id`, `nazwa`) VALUES
(1, 'Albania.jpg'),
(2, 'Algieria.jpg'),
(3, 'Australia.jpg'),
(4, 'Barbados.jpg'),
(5, 'Belgia.jpg'),
(6, 'Belize.jpg'),
(7, 'Bermudy.jpg'),
(8, 'Bhutan.jpg'),
(9, 'Boliwia.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `materialy`
--

CREATE TABLE `materialy` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materialy`
--

INSERT INTO `materialy` (`id`, `nazwa`) VALUES
(1, 'aluminum'),
(2, 'aluminum-bronze'),
(3, 'bronze'),
(4, 'copper plated zinc'),
(5, 'copper-nickel'),
(6, 'gold'),
(7, 'nickel bonded steel'),
(8, 'nickel clad steel'),
(9, 'silver'),
(10, 'stainless steel'),
(11, 'zinc');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dane`
--
ALTER TABLE `dane`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flaga_id` (`flaga_id`),
  ADD KEY `stop_id` (`stop_id`);

--
-- Indexes for table `flagi`
--
ALTER TABLE `flagi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materialy`
--
ALTER TABLE `materialy`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dane`
--
ALTER TABLE `dane`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `flagi`
--
ALTER TABLE `flagi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `materialy`
--
ALTER TABLE `materialy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dane`
--
ALTER TABLE `dane`
  ADD CONSTRAINT `dane_ibfk_1` FOREIGN KEY (`flaga_id`) REFERENCES `flagi` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dane_ibfk_2` FOREIGN KEY (`stop_id`) REFERENCES `materialy` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
