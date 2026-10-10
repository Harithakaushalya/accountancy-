-- ============================================================
-- ApexFinance - Smart Loan & Customer Management System Schema
-- High Security Relational Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS `finance_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `finance_db`;

-- --------------------------------------------------------
-- Table: users
-- Roles: admin, officer, customer
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `account_number` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'officer', 'customer') NOT NULL DEFAULT 'customer',
  `phone` VARCHAR(20) DEFAULT NULL,
  `national_id` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `monthly_income` DECIMAL(12,2) DEFAULT 0.00,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: loan_packages
-- Types of loans supported by the system
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan_packages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `min_amount` DECIMAL(12,2) NOT NULL DEFAULT 500.00,
  `max_amount` DECIMAL(12,2) NOT NULL DEFAULT 100000.00,
  `interest_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00, -- Annual percentage rate (%)
  `min_term_months` INT NOT NULL DEFAULT 6,
  `max_term_months` INT NOT NULL DEFAULT 60,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: loans
-- Core loan application and lifecycle records
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `loan_number` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `package_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `interest_rate` DECIMAL(5,2) NOT NULL,
  `term_months` INT NOT NULL,
  `monthly_installment` DECIMAL(12,2) NOT NULL,
  `total_payable` DECIMAL(12,2) NOT NULL,
  `total_interest` DECIMAL(12,2) NOT NULL,
  `outstanding_balance` DECIMAL(12,2) NOT NULL,
  `purpose` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'disbursed', 'completed') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT UNSIGNED DEFAULT NULL,
  `decision_notes` TEXT DEFAULT NULL,
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME DEFAULT NULL,
  `disbursed_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`package_id`) REFERENCES `loan_packages` (`id`),
  FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_user_status` (`user_id`, `status`),
  INDEX `idx_loan_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: installments
-- Payment schedule per loan
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `installments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT UNSIGNED NOT NULL,
  `installment_number` INT NOT NULL,
  `due_date` DATE NOT NULL,
  `amount_due` DECIMAL(12,2) NOT NULL,
  `principal_amount` DECIMAL(12,2) NOT NULL,
  `interest_amount` DECIMAL(12,2) NOT NULL,
  `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'partial', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  INDEX `idx_due_status` (`due_date`, `status`),
  INDEX `idx_loan_inst` (`loan_id`, `installment_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payments
-- Payment receipts and records
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `receipt_number` VARCHAR(30) NOT NULL UNIQUE,
  `loan_id` INT UNSIGNED NOT NULL,
  `installment_id` INT UNSIGNED DEFAULT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('cash', 'bank_transfer', 'card', 'online') NOT NULL DEFAULT 'cash',
  `transaction_reference` VARCHAR(100) DEFAULT NULL,
  `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recorded_by` INT UNSIGNED NOT NULL,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`installment_id`) REFERENCES `installments` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
  INDEX `idx_payment_date` (`payment_date`),
  INDEX `idx_payment_loan` (`loan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: audit_logs
-- Security logging table for tracking sensitive system actions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data: Loan Packages
-- --------------------------------------------------------
INSERT INTO `loan_packages` (`name`, `code`, `description`, `min_amount`, `max_amount`, `interest_rate`, `min_term_months`, `max_term_months`, `status`) VALUES
('Personal Micro Loan', 'PKG-PERS-01', 'Quick personal financial assistance with minimal documentation.', 500.00, 10000.00, 12.00, 6, 24, 'active'),
('Small Business Capital', 'PKG-BIZ-02', 'Working capital financing for small & medium enterprises.', 5000.00, 50000.00, 9.50, 12, 48, 'active'),
('Auto & Vehicle Loan', 'PKG-AUTO-03', 'Flexible auto financing for personal or commercial vehicles.', 3000.00, 35000.00, 8.75, 12, 60, 'active'),
('Emergency Cash Express', 'PKG-EMG-04', 'Short-term emergency cash advance with fast-track approval.', 200.00, 2500.00, 15.00, 3, 12, 'active');

-- Note: User password hashes below correspond to bcrypt of:
-- Admin: Admin@123
-- Officer: Officer@123
-- Customer: Customer@123
-- (Generated securely using password_hash with BCRYPT)

INSERT INTO `users` (`account_number`, `name`, `email`, `password_hash`, `role`, `phone`, `national_id`, `address`, `status`) VALUES
('ACC-ADM-001', 'System Administrator', 'admin@apexfinance.com', '$2y$10$EdFjwiVVquFw.hO8/unbwON4Y0KK.qUSO14qdXsSEkY62LrcvcV5m', 'admin', '+1 (555) 019-2831', 'NAT-99887766', 'Headquarters Central Tower, Suite 400', 'active'),
('ACC-OFF-002', 'Sarah Jenkins (Loan Officer)', 'officer@apexfinance.com', '$2y$10$ebcArkrarm2sG7.2jQCavOKVOh2NwN/wxwRuTw7IrPmer.GLC3/ZC', 'officer', '+1 (555) 014-9982', 'NAT-55443322', 'Branch Office 12, Finance Row', 'active'),
('ACC-CUST-003', 'Alexander Wright', 'customer@apexfinance.com', '$2y$10$EbJOUZojIxQVZADOynnGcuXRXcAZAsM4P8coJvYlpi98wKYBPC0D6', 'customer', '+1 (555) 017-8822', 'NAT-11223344', '742 Evergreen Terrace, Springfield', 'active');
