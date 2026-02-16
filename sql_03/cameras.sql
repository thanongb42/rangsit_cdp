-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 16, 2026 at 03:03 AM
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
-- Database: `rangsit_doocam`
--

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE `cameras` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lat` decimal(10,8) NOT NULL,
  `lng` decimal(11,8) NOT NULL,
  `stream_url` varchar(512) NOT NULL COMMENT 'URL for the camera stream, used by proxy',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cameras`
--

INSERT INTO `cameras` (`id`, `name`, `lat`, `lng`, `stream_url`, `is_active`, `created_at`) VALUES
(1, 'กล้องตรวจวัดระดับน้ำ สะพานแดง', 13.98605405, 100.62576681, 'http://user7:rangsit1029@118.174.138.142:1029/stw-cgi/video.cgi?msubmenu=stream&action=view&Profile=1', 1, '2025-11-03 05:22:45'),
(2, 'หน้าหมู่บ้าน รัตนโกสินทร์ 200 ปี', 13.98705348, 100.60629934, 'http://user7:rangsit1025@118.174.138.142:1025/stw-cgi/video.cgi?msubmenu=stream&action=view&Profile=1', 1, '2025-11-03 05:24:41'),
(3, 'โค้งห้างฟิวเจอร์พาร์ค รพ.เปาโล', 13.98526803, 100.61866701, 'http://user7:rangsit1033@118.174.138.142:1033/stw-cgi/video.cgi?msubmenu=stream&action=view&Profile=1', 1, '2025-11-03 06:57:50'),
(4, 'สะพานแดง', 13.98646527, 100.62641323, 'http://user7:rangsit1031@118.174.138.142:1031/stw-cgi/video.cgi?msubmenu=stream&action=view&Profile=1', 1, '2025-11-03 07:11:41'),
(5, 'เมืองปทุม', 14.02283238, 100.53555608, 'http://101.109.253.60:8999/', 1, '2025-11-05 09:17:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cameras`
--
ALTER TABLE `cameras`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
