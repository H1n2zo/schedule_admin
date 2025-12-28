-- CRCY Dispatch Database Schema
-- Includes admin user management for security

-- Create database
CREATE DATABASE IF NOT EXISTS crcy_dispatch;
USE crcy_dispatch;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS support_requests;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS admin_users;

-- Create admin_users table (single admin)
CREATE TABLE admin_users (
  id int(11) NOT NULL AUTO_INCREMENT,
  password_hash varchar(255) NOT NULL,
  full_name varchar(255) NOT NULL DEFAULT 'CRCY Administrator',
  last_login timestamp NULL DEFAULT NULL,
  failed_login_attempts int(11) DEFAULT 0,
  locked_until timestamp NULL DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create support_requests table
CREATE TABLE support_requests (
  id int(11) NOT NULL AUTO_INCREMENT,
  event_name varchar(255) NOT NULL,
  organization varchar(255) NOT NULL,
  requester_email varchar(255) NOT NULL,
  requester_name varchar(255) NOT NULL,
  requester_position varchar(100) DEFAULT NULL,
  event_date date NOT NULL,
  event_time time NOT NULL,
  event_end_time time DEFAULT NULL,
  venue varchar(255) NOT NULL,
  expected_participants int(11) DEFAULT 0,
  volunteers_needed int(11) NOT NULL DEFAULT 1,
  volunteer_roles text DEFAULT NULL,
  event_description text DEFAULT NULL,
  special_requirements text DEFAULT NULL,
  contact_number varchar(20) DEFAULT NULL,
  status enum('pending','approved','declined','completed') DEFAULT 'pending',

  rejection_reason text DEFAULT NULL,
  submitted_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_event_date (event_date),
  KEY idx_status (status),
  KEY idx_organization (organization)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create attachments table
CREATE TABLE attachments (
  id int(11) NOT NULL AUTO_INCREMENT,
  request_id int(11) NOT NULL,
  file_name varchar(255) NOT NULL,
  file_path varchar(500) NOT NULL,
  file_type varchar(100) NOT NULL,
  file_size int(11) NOT NULL,
  uploaded_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY request_id (request_id),
  CONSTRAINT attachments_ibfk_1 FOREIGN KEY (request_id) REFERENCES support_requests (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Insert default admin user
-- Password: admin123 
INSERT INTO admin_users (password_hash, full_name) VALUES
('$2y$10$8K1p/a0dhrxSM4KRRL/VWOIhyT08Q/GvVzjuBkkUfRz6A0D3R.WrW', 'CRCY Administrator');

-- Verify setup
SELECT 'CRCY Dispatch Database Setup Complete!' as status;
SELECT 'Default Admin Password: admin123' as credentials;