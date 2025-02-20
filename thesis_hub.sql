-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 18, 2025 at 04:39 PM
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
-- Database: `thesis_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `account_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','advisor','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`account_id`, `password`, `role`) VALUES
('65310000', '123', 'student'),
('65310001', '123', 'student'),
('65310002', '123', 'student'),
('65310609', '123', 'student'),
('65312345', '123', 'student'),
('Admin', '123', 'admin'),
('F05003', '123', 'advisor'),
('F05010', '123', 'advisor');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` varchar(50) NOT NULL,
  `admin_first_name` varchar(100) NOT NULL,
  `admin_last_name` varchar(100) NOT NULL,
  `admin_tel` varchar(15) NOT NULL,
  `admin_email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `admin_first_name`, `admin_last_name`, `admin_tel`, `admin_email`) VALUES
('Admin', 'Tharasuk', 'Chunkonghor', '055-963230', 'tharasukc@nu.ac.th');

-- --------------------------------------------------------

--
-- Table structure for table `advisor`
--

CREATE TABLE `advisor` (
  `advisor_id` varchar(50) NOT NULL,
  `advisor_first_name` varchar(100) NOT NULL,
  `advisor_last_name` varchar(100) NOT NULL,
  `advisor_tel` varchar(15) NOT NULL,
  `advisor_email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `advisor`
--

INSERT INTO `advisor` (`advisor_id`, `advisor_first_name`, `advisor_last_name`, `advisor_tel`, `advisor_email`) VALUES
('F05003', 'Chakkrit', 'Namahoot', '055-123-1234', 'chakkrits@nu.ac.th'),
('F05010', 'Janjira', 'Payakpate', '055-123-1234', ' janjirap@nu.ac.th');

-- --------------------------------------------------------

--
-- Table structure for table `advisor_profile`
--

CREATE TABLE `advisor_profile` (
  `advisor_profile_id` int(11) NOT NULL,
  `advisor_id` varchar(50) NOT NULL,
  `expertise` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`expertise`)),
  `advisor_interests` text NOT NULL,
  `img` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advisor_request`
--

CREATE TABLE `advisor_request` (
  `advisor_request_id` int(11) NOT NULL,
  `requester_id` varchar(50) NOT NULL,
  `student_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`student_id`)),
  `advisor_id` varchar(50) NOT NULL,
  `thesis_topic_thai` text NOT NULL,
  `thesis_topic_eng` text NOT NULL,
  `thesis_description` text NOT NULL,
  `is_even` tinyint(1) NOT NULL DEFAULT 0,
  `semester` tinyint(1) NOT NULL,
  `academic_year` smallint(4) NOT NULL,
  `is_advisor_approved` tinyint(1) NOT NULL DEFAULT 0,
  `is_admin_approved` tinyint(1) NOT NULL DEFAULT 0,
  `partner_accepted` tinyint(4) NOT NULL,
  `time_stamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` varchar(50) NOT NULL,
  `receiver_id` varchar(50) NOT NULL,
  `message_title` text NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `time_stamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` varchar(50) NOT NULL,
  `student_first_name` varchar(100) NOT NULL,
  `student_last_name` varchar(100) NOT NULL,
  `student_tel` varchar(15) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `student_department` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_first_name`, `student_last_name`, `student_tel`, `student_email`, `student_department`) VALUES
('65310000', 'John', 'Doe', '055-123-1234', 'JohnD65@nu.ac.th', 'Information Technology'),
('65310001', 'George', 'Brown', '055-123-1234', 'georgeb65@nu.ac.th', 'Computer Science'),
('65310002', 'Jane', 'Red', '066-123-1222', 'jane65@nu.ac.th', 'Information Technology'),
('65310609', 'Jakkrit', 'Umkhum', '055-123-1234', 'Jakkrit65@nu.ac.th', 'Computer Science'),
('65312345', 'Eric', 'Dickson', '055-120-4212', 'ericd65@nu.ac.th', 'Computer Science');

-- --------------------------------------------------------

--
-- Table structure for table `student_profile`
--

CREATE TABLE `student_profile` (
  `student_profile_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_interests` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thesis`
--

CREATE TABLE `thesis` (
  `thesis_id` int(11) NOT NULL,
  `advisor_id` varchar(50) NOT NULL,
  `thesis_title` text NOT NULL,
  `authors` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`keywords`)),
  `issue_date` date NOT NULL,
  `publisher` varchar(255) NOT NULL,
  `abstract` text NOT NULL,
  `thesis_file` longblob NOT NULL,
  `thesis_file_type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thesis_resource`
--

CREATE TABLE `thesis_resource` (
  `thesis_resource_id` int(11) NOT NULL,
  `advisor_request_id` int(50) NOT NULL,
  `uploader_id` varchar(50) NOT NULL,
  `thesis_resource_file_name` varchar(255) NOT NULL,
  `thesis_resource_file_data` longblob NOT NULL,
  `thesis_resource_file_type` varchar(255) NOT NULL,
  `time_stamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`admin_email`);

--
-- Indexes for table `advisor`
--
ALTER TABLE `advisor`
  ADD PRIMARY KEY (`advisor_id`),
  ADD UNIQUE KEY `email` (`advisor_email`);

--
-- Indexes for table `advisor_profile`
--
ALTER TABLE `advisor_profile`
  ADD PRIMARY KEY (`advisor_profile_id`),
  ADD UNIQUE KEY `advisor_id` (`advisor_id`);

--
-- Indexes for table `advisor_request`
--
ALTER TABLE `advisor_request`
  ADD PRIMARY KEY (`advisor_request_id`),
  ADD KEY `advisor_id` (`advisor_id`),
  ADD KEY `requester_id` (`requester_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `student_profile`
--
ALTER TABLE `student_profile`
  ADD PRIMARY KEY (`student_profile_id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `thesis`
--
ALTER TABLE `thesis`
  ADD PRIMARY KEY (`thesis_id`),
  ADD KEY `advisor_id` (`advisor_id`);

--
-- Indexes for table `thesis_resource`
--
ALTER TABLE `thesis_resource`
  ADD PRIMARY KEY (`thesis_resource_id`),
  ADD KEY `advisor_request_id` (`advisor_request_id`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advisor_profile`
--
ALTER TABLE `advisor_profile`
  MODIFY `advisor_profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `advisor_request`
--
ALTER TABLE `advisor_request`
  MODIFY `advisor_request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `student_profile`
--
ALTER TABLE `student_profile`
  MODIFY `student_profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `thesis`
--
ALTER TABLE `thesis`
  MODIFY `thesis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `thesis_resource`
--
ALTER TABLE `thesis_resource`
  MODIFY `thesis_resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `account` (`account_id`);

--
-- Constraints for table `advisor`
--
ALTER TABLE `advisor`
  ADD CONSTRAINT `advisor_ibfk_1` FOREIGN KEY (`advisor_id`) REFERENCES `account` (`account_id`);

--
-- Constraints for table `advisor_profile`
--
ALTER TABLE `advisor_profile`
  ADD CONSTRAINT `advisor_profile_ibfk_1` FOREIGN KEY (`advisor_id`) REFERENCES `advisor` (`advisor_id`);

--
-- Constraints for table `advisor_request`
--
ALTER TABLE `advisor_request`
  ADD CONSTRAINT `advisor_request_ibfk_2` FOREIGN KEY (`advisor_id`) REFERENCES `advisor` (`advisor_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `account` (`account_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `account` (`account_id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `account` (`account_id`);

--
-- Constraints for table `student_profile`
--
ALTER TABLE `student_profile`
  ADD CONSTRAINT `student_profile_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `thesis`
--
ALTER TABLE `thesis`
  ADD CONSTRAINT `thesis_ibfk_1` FOREIGN KEY (`advisor_id`) REFERENCES `advisor` (`advisor_id`);

--
-- Constraints for table `thesis_resource`
--
ALTER TABLE `thesis_resource`
  ADD CONSTRAINT `thesis_resource_ibfk_1` FOREIGN KEY (`advisor_request_id`) REFERENCES `advisor_request` (`advisor_request_id`),
  ADD CONSTRAINT `thesis_resource_ibfk_2` FOREIGN KEY (`uploader_id`) REFERENCES `account` (`account_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
