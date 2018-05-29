-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 29, 2018 at 06:39 PM
-- Server version: 5.5.58-cll
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dataqvt_granary`
--

-- --------------------------------------------------------

--
-- Table structure for table `granary_sensors`
--

CREATE TABLE `granary_sensors` (
  `sensor_name` char(2) NOT NULL,
  `sensor_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sensor_value` float NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sensor_info`
--

CREATE TABLE `sensor_info` (
  `sensor_name` char(2) NOT NULL,
  `sensor_desc` varchar(64) NOT NULL,
  `sensor_units` varchar(16) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `granary_sensors`
--
ALTER TABLE `granary_sensors`
  ADD KEY `sensor_time` (`sensor_time`);

--
-- Indexes for table `sensor_info`
--
ALTER TABLE `sensor_info`
  ADD PRIMARY KEY (`sensor_name`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
