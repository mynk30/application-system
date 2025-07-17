-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2025 at 12:16 PM
-- Server version: 8.0.36
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_form1`
--

CREATE TABLE `contact_form1` (
  `id` int NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `number` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_form1`
--

INSERT INTO `contact_form1` (`id`, `name`, `email`, `number`, `subject`, `message`) VALUES
(1, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'asd', 'asdf'),
(2, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'nm', 'nm');

-- --------------------------------------------------------

--
-- Table structure for table `contact_form2`
--

CREATE TABLE `contact_form2` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `bname` varchar(100) NOT NULL,
  `service` varchar(100) NOT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_form2`
--

INSERT INTO `contact_form2` (`id`, `name`, `email`, `mobile`, `bname`, `service`, `message`, `created_at`) VALUES
(1, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'ab', 'Company-Registration', 'asd', '2025-07-09 12:04:54'),
(2, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'ad', 'LLP-Registration', 'as', '2025-07-09 12:05:31'),
(3, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'mdsnfklkdsjfd', 'gst-return', 'ASD', '2025-07-09 12:44:36'),
(4, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'ccd', 'gst-return', 'ASD', '2025-07-09 12:58:07'),
(5, 'anshul', 'sharma@gmail.com', '07770958768', '12', 'composition', 'wer23', '2025-07-09 13:01:01'),
(6, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'asq23456', 'gst-cancellation', '23456789', '2025-07-09 13:08:33'),
(7, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'qw', 'gst-return', '', '2025-07-09 13:16:51'),
(8, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'qw', 'gst-return', '', '2025-07-09 13:17:52'),
(9, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', '123', 'gst-return', 'w', '2025-07-09 13:22:39'),
(10, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'qwed', 'gst-return', 'qw', '2025-07-09 13:23:19'),
(11, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'bnm', 'composition', 'nkkl', '2025-07-09 13:48:16'),
(12, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'nm', 'gst-return', 'nm', '2025-07-09 13:49:43'),
(13, 'Anjali Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'asdfgh12', 'composition', 'wertg', '2025-07-10 06:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `contact_form3`
--

CREATE TABLE `contact_form3` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_form3`
--

INSERT INTO `contact_form3` (`id`, `name`, `lastname`, `email`, `mobile`, `subject`, `message`, `created_at`) VALUES
(1, 'Anjali', 'Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'support', 'asdd', '2025-07-09 12:05:46'),
(2, 'priyank ', 'rawat', 'kushwahanjali89@gmail.com', '07770958768', 'other', 'bnm', '2025-07-09 12:10:55'),
(3, 'priyank ', 'rawat', 'kushwahanjali89@gmail.com', '07770958768', 'other', 'bnm', '2025-07-09 12:18:22'),
(4, 'Anjali', 'Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'general', 'wsdfgvbfdscasdcvs', '2025-07-09 12:18:33'),
(5, 'Anjali', 'Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'support', 'asd', '2025-07-09 12:43:56'),
(6, 'Anjali', 'Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'support', 'asd', '2025-07-09 12:44:10'),
(7, 'Anjali', 'Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'support', 'qwerfgh2345ty345y', '2025-07-10 06:11:13'),
(8, 'Anjali', 'Kushwah', 'kushwahanjali89@gmail.com', '07770958768', 'billing', 'dfghjkl34567890', '2025-07-10 06:19:51');

-- --------------------------------------------------------

--
-- Table structure for table `form1`
--

CREATE TABLE `form1` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `number` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_form1`
--
ALTER TABLE `contact_form1`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_form2`
--
ALTER TABLE `contact_form2`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_form3`
--
ALTER TABLE `contact_form3`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `form1`
--
ALTER TABLE `form1`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_form1`
--
ALTER TABLE `contact_form1`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_form2`
--
ALTER TABLE `contact_form2`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `contact_form3`
--
ALTER TABLE `contact_form3`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `form1`
--
ALTER TABLE `form1`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
