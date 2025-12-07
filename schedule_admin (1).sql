-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 11:51 AM
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
  `action` enum('submitted','approved','disapproved','notification_sent') NOT NULL,
  `notes` text DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `request_id`, `admin_id`, `action`, `notes`, `email_sent`, `created_at`) VALUES
(7, 45, 3, 'approved', NULL, 0, '2025-12-07 10:49:02'),
(8, 45, 3, 'notification_sent', '✓ Event Request APPROVED - Women Empowerment Forum', 1, '2025-12-07 10:49:06'),
(9, 44, 3, 'approved', NULL, 0, '2025-12-07 10:49:15'),
(10, 29, 3, 'disapproved', NULL, 0, '2025-12-07 10:49:55');

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
  `status` enum('pending','approved','disapproved','pending_notification') DEFAULT 'pending',
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
(29, 'Christmas Charity Drive', 'EVSU Student Council', 'maria.santos@evsu.edu.ph', 'Maria Santos', '2024-12-15', '09:00:00', 15, 'Annual Christmas charity event to collect donations and distribute gifts to underprivileged children in the community. We will need volunteers to help with sorting donations, wrapping gifts, and distribution.', 'pending_notification', 3, '2025-12-07 10:49:55', NULL, '2024-11-20 02:30:00', '2025-12-07 10:49:55'),
(30, 'STEM Fair 2024', 'College of Engineering', 'john.reyes@evsu.edu.ph', 'John Reyes', '2024-12-18', '08:00:00', 20, 'Annual STEM Fair showcasing student projects and innovations. Volunteers needed for registration desk, guiding visitors, and assisting exhibitors with their displays.', 'pending', NULL, NULL, NULL, '2024-11-15 06:20:00', '2025-12-07 10:48:48'),
(31, 'Year-End Sports Festival', 'PE Department', 'carlos.martinez@evsu.edu.ph', 'Carlos Martinez', '2024-12-20', '07:00:00', 25, 'Inter-departmental sports competition featuring basketball, volleyball, and track and field events. Volunteers will assist with event coordination, timekeeping, and crowd management.', 'pending', NULL, NULL, NULL, '2024-11-28 01:15:00', '2025-12-07 10:48:48'),
(32, 'Career Orientation Seminar', 'Guidance Office', 'ana.delacruz@evsu.edu.ph', 'Ana Dela Cruz', '2024-12-22', '13:00:00', 10, 'Career guidance seminar for graduating students. Volunteers needed to help with registration, distribute materials, and assist with breakout sessions.', 'pending', NULL, NULL, NULL, '2024-11-22 03:45:00', '2025-12-07 10:48:48'),
(33, 'Environmental Cleanup Drive', 'Green Earth Organization', 'roberto.garcia@evsu.edu.ph', 'Roberto Garcia', '2024-12-28', '06:00:00', 30, 'Beach and mangrove cleanup activity to promote environmental awareness. Volunteers will participate in collecting trash, sorting recyclables, and documenting the activity.', 'pending', NULL, NULL, NULL, '2024-11-18 08:30:00', '2025-12-07 10:48:48'),
(34, 'New Year Community Outreach', 'EVSU Community Extension', 'lisa.mendoza@evsu.edu.ph', 'Lisa Mendoza', '2025-01-05', '08:00:00', 18, 'Community outreach program to bring educational activities and basic health services to nearby barangays. Volunteers will assist with activity facilitation and logistics.', 'pending', NULL, NULL, NULL, '2024-12-01 02:00:00', '2025-12-07 10:48:48'),
(35, 'Research Symposium', 'Graduate School', 'ferdinand.cruz@evsu.edu.ph', 'Dr. Ferdinand Cruz', '2025-01-18', '09:00:00', 12, 'Annual research symposium featuring thesis presentations and academic discussions. Volunteers needed for technical support, registration, and documentation.', 'pending', NULL, NULL, NULL, '2024-12-02 05:20:00', '2025-12-07 10:48:48'),
(36, 'Blood Donation Drive', 'Red Cross Youth Council', 'patricia.ramos@evsu.edu.ph', 'Patricia Ramos', '2025-01-22', '08:00:00', 15, 'Quarterly blood donation drive in partnership with the Philippine Red Cross. Volunteers will help with donor registration, refreshment distribution, and crowd control.', 'pending', NULL, NULL, NULL, '2024-12-03 01:30:00', '2025-12-07 10:48:48'),
(37, 'Cultural Night Festival', 'Arts and Culture Committee', 'miguel.torres@evsu.edu.ph', 'Miguel Torres', '2025-01-25', '18:00:00', 22, 'Celebration of Filipino culture through dance, music, and art performances. Volunteers needed for stage management, ushering, and backstage coordination.', 'pending', NULL, NULL, NULL, '2024-12-04 07:45:00', '2025-12-07 10:48:48'),
(38, 'Leadership Training Workshop', 'Student Leadership Council', 'sarah.villanueva@evsu.edu.ph', 'Sarah Villanueva', '2025-01-15', '13:00:00', 8, 'Leadership development workshop for student organization officers. Volunteers will assist with room setup, materials distribution, and activity facilitation.', 'pending', NULL, NULL, NULL, '2024-12-05 03:00:00', '2025-12-07 10:48:48'),
(39, 'Health and Wellness Fair', 'Medical Services Office', 'maria.gonzales@evsu.edu.ph', 'Dr. Maria Gonzales', '2025-01-30', '09:00:00', 18, 'Campus health fair offering free medical checkups, health screenings, and wellness consultations. Volunteers will assist with registration and crowd flow.', 'pending', NULL, NULL, NULL, '2024-12-04 02:00:00', '2025-12-07 10:48:48'),
(40, 'Valentine Blood Drive', 'Nursing Society', 'jenny.aquino@evsu.edu.ph', 'Nurse Jenny Aquino', '2025-02-14', '09:00:00', 12, 'Valentine-themed blood donation drive. Share the love, donate blood. Volunteers will assist with pre-screening, registration, and donor care.', 'pending', NULL, NULL, NULL, '2024-12-06 02:30:00', '2025-12-07 10:48:48'),
(41, 'EVSU Job Fair 2025', 'Career Development Office', 'mark.santiago@evsu.edu.ph', 'Mark Santiago', '2025-02-20', '08:00:00', 25, 'Annual job fair featuring 50+ companies looking to hire EVSU graduates. Volunteers needed for company liaison, registration, and crowd management.', 'pending', NULL, NULL, NULL, '2024-12-07 06:15:00', '2025-12-07 10:48:48'),
(42, 'Science Quiz Bowl', 'Science Club', 'linda.reyes@evsu.edu.ph', 'Prof. Linda Reyes', '2025-02-10', '13:00:00', 10, 'Inter-college science quiz competition. Volunteers will help with score tabulation, timekeeping, and technical support.', 'pending', NULL, NULL, NULL, '2024-11-25 01:00:00', '2025-12-07 10:48:48'),
(43, 'Earthquake Drill', 'Safety and Security Office', 'ramon.bautista@evsu.edu.ph', 'Security Chief Ramon Bautista', '2025-02-28', '10:00:00', 20, 'Campus-wide earthquake preparedness drill. Volunteers will serve as marshals, evacuation guides, and first aid responders.', 'pending', NULL, NULL, NULL, '2024-12-01 00:30:00', '2025-12-07 10:48:48'),
(44, 'Book Fair 2025', 'Library Services', 'susan.reyes@evsu.edu.ph', 'Librarian Susan Reyes', '2025-02-05', '08:00:00', 12, 'Annual book fair featuring discounted books, author talks, and reading activities. Volunteers needed for setup, cashier assistance, and customer service.', 'pending_notification', 3, '2025-12-07 10:49:15', NULL, '2024-12-05 01:30:00', '2025-12-07 10:49:15'),
(45, 'Women Empowerment Forum', 'Gender and Development Office', 'grace.santos@evsu.edu.ph', 'Dr. Grace Santos', '2025-03-08', '13:00:00', 15, 'International Women\'s Day celebration with panel discussions and workshops on women\'s rights and empowerment. Volunteers needed for registration and logistics.', 'approved', 3, '2025-12-07 10:49:02', NULL, '2024-12-05 03:20:00', '2025-12-07 10:49:06'),
(46, 'Tree Planting Activity', 'Environmental Science Club', 'eduardo.flores@evsu.edu.ph', 'Eduardo Flores', '2025-03-15', '06:00:00', 35, 'Tree planting initiative at Mt. Pangasugan. Volunteers will participate in planting 500 native tree species and trail maintenance.', 'pending', NULL, NULL, NULL, '2024-12-06 08:00:00', '2025-12-07 10:48:48'),
(47, 'University Week Opening', 'University Events Committee', 'melissa.tan@evsu.edu.ph', 'Director Melissa Tan', '2025-03-24', '08:00:00', 40, 'Grand opening ceremony for University Week featuring parade, cultural presentations, and sports competitions. Large volunteer contingent needed for various roles.', 'pending', NULL, NULL, NULL, '2024-11-30 02:00:00', '2025-12-07 10:48:48'),
(48, 'Mathematics Olympiad', 'Math Department', 'antonio.cruz@evsu.edu.ph', 'Prof. Antonio Cruz', '2025-03-12', '08:00:00', 8, 'Regional mathematics competition for high school students. Volunteers will assist with registration, room assignment, and examination monitoring.', 'pending', NULL, NULL, NULL, '2024-12-02 05:45:00', '2025-12-07 10:48:48'),
(49, 'Student Art Exhibition', 'Fine Arts Department', 'angela.villanueva@evsu.edu.ph', 'Prof. Angela Villanueva', '2025-03-20', '14:00:00', 10, 'Showcase of student artworks including paintings, sculptures, and digital art. Volunteers needed for gallery setup, visitor assistance, and artwork handling.', 'pending', NULL, NULL, NULL, '2024-12-03 02:15:00', '2025-12-07 10:48:48');

-- --------------------------------------------------------

--
-- Table structure for table `pending_actions`
--

CREATE TABLE `pending_actions` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `action_type` enum('approve','disapprove') NOT NULL,
  `admin_id` int(11) NOT NULL,
  `email_subject` varchar(500) DEFAULT NULL,
  `email_body` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_actions`
--

INSERT INTO `pending_actions` (`id`, `request_id`, `action_type`, `admin_id`, `email_subject`, `email_body`, `created_at`) VALUES
(6, 44, 'approve', 3, NULL, NULL, '2025-12-07 10:49:15'),
(7, 29, 'disapprove', 3, NULL, NULL, '2025-12-07 10:49:55');

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
(3, 'hansmichael.gabor@evsu.edu.ph', '$2y$10$BtuZlGyadnpgpFdx1rxq7udSqebDLwEvWldvTT/TTmfvIkKD57Hg2', 'Hans Michael Gabor', 'admin', '2025-12-07 07:06:13', '2025-12-07 07:07:01');

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
-- Indexes for table `pending_actions`
--
ALTER TABLE `pending_actions`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `event_requests`
--
ALTER TABLE `event_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `pending_actions`
--
ALTER TABLE `pending_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `pending_actions`
--
ALTER TABLE `pending_actions`
  ADD CONSTRAINT `pending_actions_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `event_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pending_actions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
