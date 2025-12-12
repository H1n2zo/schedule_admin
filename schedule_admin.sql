-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 12, 2025 at 01:44 AM
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
-- Database: `schedule_admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` enum('submitted','approved','declined','notification_sent') NOT NULL,
  `notes` text DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `request_id`, `admin_id`, `action`, `notes`, `email_sent`, `created_at`) VALUES
(1, 74, 3, 'approved', '✓ Event Request APPROVED - Tech Innovation Summit 2026', 1, '2025-12-12 00:42:23');

-- --------------------------------------------------------

--
-- Table structure for table `event_requests`
--

CREATE TABLE `event_requests` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `organization` varchar(255) NOT NULL,
  `requester_email` varchar(255) NOT NULL,
  `requester_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `volunteers_needed` int(11) DEFAULT 0,
  `description` text NOT NULL,
  `status` enum('pending','approved','declined','pending_notification') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_requests`
--

INSERT INTO `event_requests` (`id`, `event_name`, `organization`, `requester_email`, `requester_name`, `event_date`, `event_time`, `volunteers_needed`, `description`, `status`, `reviewed_by`, `reviewed_at`, `notes`, `created_at`, `updated_at`) VALUES
(74, 'Tech Innovation Summit 2026', 'Computer Science Society', 'mark.santos@evsu.edu.ph', 'Mark Santos', '2026-01-10', '09:00:00', 12, 'A full-day summit featuring talks from tech innovators and workshop sessions.', 'approved', 3, '2025-12-12 00:42:23', NULL, '2025-12-19 19:00:00', '2025-12-12 00:42:23'),
(75, 'Mental Health Awareness Day', 'Psychology Club', 'sarah.reyes@evsu.edu.ph', 'Sarah Reyes', '2026-01-12', '13:00:00', 8, 'Mental health awareness campaign with counseling sessions and mindfulness workshops.', 'pending', NULL, NULL, NULL, '2025-12-21 20:30:00', '2025-12-21 20:30:00'),
(76, 'Christmas Movie Marathon', 'Film Society', 'juan.deleon@evsu.edu.ph', 'Juan Dela Leon', '2026-01-14', '18:00:00', 6, 'Holiday movie night with popcorn, hot chocolate, and volunteer support for setup.', 'pending', NULL, NULL, NULL, '2025-12-24 18:15:00', '2025-12-24 18:15:00'),
(77, 'Robotics Competition', 'Engineering Club', 'anna.torres@evsu.edu.ph', 'Anna Torres', '2026-01-16', '08:00:00', 15, 'Inter-school robotics competition with timekeeping and arena maintenance volunteers.', 'pending', NULL, NULL, NULL, '2025-12-17 21:45:00', '2025-12-17 21:45:00'),
(78, 'New Year Resolution Workshop', 'Student Development Office', 'maria.garcia@evsu.edu.ph', 'Maria Garcia', '2026-02-08', '14:00:00', 10, 'Goal-setting workshop with vision boarding and accountability partner matching.', 'pending', NULL, NULL, NULL, '2026-01-04 23:00:00', '2026-01-04 23:00:00'),
(79, 'Disaster Preparedness Training', 'Red Cross Youth', 'carlo.mendoza@evsu.edu.ph', 'Carlo Mendoza', '2026-02-12', '09:00:00', 20, 'Comprehensive disaster preparedness and first-aid training.', 'pending', NULL, NULL, NULL, '2026-01-01 19:30:00', '2026-01-01 19:30:00'),
(80, 'Photography Walk & Workshop', 'Photography Club', 'lisa.francisco@evsu.edu.ph', 'Lisa Francisco', '2026-02-20', '06:00:00', 12, 'Sunrise photography walk and editing workshop.', 'pending', NULL, NULL, NULL, '2026-01-06 00:15:00', '2026-01-06 00:15:00'),
(81, 'Alumni Networking Night', 'Alumni Relations Office', 'robert.cruz@evsu.edu.ph', 'Robert Cruz', '2026-02-27', '18:00:00', 15, 'Networking night connecting students with alumni mentors.', 'pending', NULL, NULL, NULL, '2026-01-02 20:00:00', '2026-01-02 20:00:00'),
(82, 'Valentine Blood and Bone Marrow Drive', 'Medical Technology Society', 'jenny.santos@evsu.edu.ph', 'Jenny Santos', '2026-03-13', '08:00:00', 18, 'Blood donation and bone marrow registry drive.', 'pending', NULL, NULL, NULL, '2026-02-03 22:30:00', '2026-02-03 22:30:00'),
(83, 'Entrepreneurship Fair', 'Business Administration Dept', 'michael.reyes@evsu.edu.ph', 'Dr. Michael Reyes', '2026-03-18', '09:00:00', 20, 'Student entrepreneurship showcase with business pitches and workshops.', 'pending', NULL, NULL, NULL, '2026-02-06 18:45:00', '2026-02-06 18:45:00'),
(84, 'Language Festival', 'Foreign Language Department', 'sofia.martinez@evsu.edu.ph', 'Prof. Sofia Martinez', '2026-03-22', '13:00:00', 14, 'Celebration of languages with performances and cultural displays.', 'pending', NULL, NULL, NULL, '2026-02-04 21:20:00', '2026-02-04 21:20:00'),
(85, 'Coastal Cleanup and Marine Life Documentation', 'Marine Biology Society', 'eduardo.ramos@evsu.edu.ph', 'Eduardo Ramos', '2026-03-25', '06:00:00', 30, 'Beach cleanup and marine biodiversity documentation activity.', 'pending', NULL, NULL, NULL, '2026-02-05 23:00:00', '2026-02-05 23:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `notification_history`
--

CREATE TABLE `notification_history` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `action_type` enum('approve','disapprove','decline') NOT NULL,
  `admin_id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `attachments_sent` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_history`
--

INSERT INTO `notification_history` (`id`, `request_id`, `action_type`, `admin_id`, `recipient_email`, `subject`, `body`, `attachments_sent`, `sent_at`) VALUES
(1, 74, 'approve', 3, 'mark.santos@evsu.edu.ph', '✓ Event Request APPROVED - Tech Innovation Summit 2026', 'Dear Mark Santos,\r\n\r\nGreat news! Your event request has been APPROVED.\r\n\r\nEvent Details:\r\n- Event Name: Tech Innovation Summit 2026\r\n- Organization: Computer Science Society\r\n- Date: January 10, 2026\r\n- Time: 9:00 AM\r\n- Volunteers Needed: 12\r\n\r\nYou can now proceed with your event preparations. Our team will contact you soon regarding volunteer assignments.\r\n\r\nBest regards,\r\nEVSU Admin Council', 0, '2025-12-12 00:42:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('admin','coordinator','council_member') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `role`, `created_at`, `updated_at`) VALUES
(3, 'hansmichael.gabor@evsu.edu.ph', '$2y$10$BtuZlGyadnpgpFdx1rxq7udSqebDLwEvWldvTT/TTmfvIkKD57Hg2', 'Hans Michael Gabor', 'admin', '2025-12-07 07:06:13', '2025-12-07 07:07:01'),
(4, 'admin@evsu.edu.ph', '$2y$10$52MqTHfaYzQz1aBzZJqoBeeyQ60SWkvKg.jJjI9fiekeiQfPLRkga', 'AdminTG', 'coordinator', '2025-12-09 13:47:17', '2025-12-09 13:47:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `event_requests`
--
ALTER TABLE `event_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `notification_history`
--
ALTER TABLE `notification_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_requests`
--
ALTER TABLE `event_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `notification_history`
--
ALTER TABLE `notification_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `event_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `event_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `audit_log_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_requests`
--
ALTER TABLE `event_requests`
  ADD CONSTRAINT `event_requests_ibfk_1` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_history`
--
ALTER TABLE `notification_history`
  ADD CONSTRAINT `notification_history_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `event_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_history_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
