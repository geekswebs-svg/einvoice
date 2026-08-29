-- CI3 E-Invoice Integration Database Migration
-- Version: 1.0.0
-- Created: 2024-01-15
-- This script creates all necessary tables for the e-Invoice system

-- Invoices Table
CREATE TABLE IF NOT EXISTS `einvoice_invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Unique invoice identifier',
  `invoice_number` VARCHAR(50) NOT NULL COMMENT 'Invoice number for reference',
  `invoice_date` DATE NOT NULL COMMENT 'Date of invoice',
  `invoice_type` ENUM('invoice', 'credit_note', 'debit_note') DEFAULT 'invoice' COMMENT 'Type of invoice',
  `due_date` DATE COMMENT 'Payment due date',
  `currency` VARCHAR(3) DEFAULT 'INR' COMMENT 'Currency code',
  `document_status` ENUM('draft', 'issued', 'submitted', 'accepted', 'rejected', 'cancelled', 'archived') DEFAULT 'draft' COMMENT 'Current document status',
  `seller_id` VARCHAR(100) NOT NULL COMMENT 'Seller party ID',
  `buyer_id` VARCHAR(100) NOT NULL COMMENT 'Buyer party ID',
  `subtotal` DECIMAL(15,2) COMMENT 'Sum of line amounts',
  `tax_total` DECIMAL(15,2) COMMENT 'Total tax amount',
  `discount_amount` DECIMAL(15,2) DEFAULT 0 COMMENT 'Total discounts',
  `total_amount` DECIMAL(15,2) NOT NULL COMMENT 'Final amount due',
  `payment_terms` VARCHAR(255) COMMENT 'Payment terms description',
  `notes` TEXT COMMENT 'Additional notes',
  `digital_signature` TEXT COMMENT 'Digital signature for the invoice',
  `irn` VARCHAR(255) COMMENT 'Invoice Reference Number (for Indian e-Invoice)',
  `custom_fields` JSON COMMENT 'Custom application-specific fields',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_document_status` (`document_status`),
  INDEX `idx_invoice_date` (`invoice_date`),
  INDEX `idx_seller_id` (`seller_id`),
  INDEX `idx_buyer_id` (`buyer_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='E-Invoice documents';

-- Items Table
CREATE TABLE IF NOT EXISTS `einvoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL COMMENT 'Invoice ID (foreign key)',
  `item_id` VARCHAR(100) COMMENT 'Unique item identifier',
  `item_code` VARCHAR(50) COMMENT 'Product/service code',
  `item_name` VARCHAR(255) NOT NULL COMMENT 'Item name',
  `description` TEXT COMMENT 'Detailed description',
  `quantity` DECIMAL(10,2) NOT NULL COMMENT 'Item quantity',
  `unit_of_measure` VARCHAR(20) COMMENT 'Unit (pieces, kg, hours, etc)',
  `unit_price` DECIMAL(12,2) NOT NULL COMMENT 'Price per unit',
  `line_amount` DECIMAL(15,2) COMMENT 'Quantity × Unit Price',
  `tax_rate` DECIMAL(5,2) DEFAULT 0 COMMENT 'Tax percentage (0-100)',
  `tax_amount` DECIMAL(15,2) COMMENT 'Calculated tax',
  `total_amount` DECIMAL(15,2) COMMENT 'Line amount + tax',
  `additional_data` JSON COMMENT 'Custom item fields',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  FOREIGN KEY (`invoice_id`) REFERENCES `einvoice_invoices`(`id`) ON DELETE CASCADE,
  INDEX `idx_invoice_id` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Invoice line items';

-- Validations Table
CREATE TABLE IF NOT EXISTS `einvoice_validations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `validation_id` VARCHAR(100) UNIQUE COMMENT 'Unique validation ID',
  `invoice_id` VARCHAR(100) NOT NULL COMMENT 'Associated invoice ID',
  `validation_status` ENUM('valid', 'invalid', 'warning') DEFAULT 'invalid' COMMENT 'Validation result status',
  `validation_schema` VARCHAR(100) COMMENT 'Schema version used',
  `errors` JSON COMMENT 'Array of validation errors',
  `warnings` JSON COMMENT 'Array of warnings',
  `critical_errors` JSON COMMENT 'Array of critical errors',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Validation timestamp',
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_validation_status` (`validation_status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Invoice validation records';

-- Submissions Table
CREATE TABLE IF NOT EXISTS `einvoice_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `submission_id` VARCHAR(100) UNIQUE COMMENT 'Unique submission ID',
  `invoice_id` VARCHAR(100) NOT NULL COMMENT 'Associated invoice ID',
  `submission_status` ENUM('pending', 'submitted', 'acknowledged', 'rejected', 'failed') DEFAULT 'pending' COMMENT 'Submission workflow status',
  `submission_channel` ENUM('api', 'web_portal', 'email', 'batch') DEFAULT 'api' COMMENT 'Submission channel',
  `government_reference` VARCHAR(255) COMMENT 'Government reference number',
  `irn` VARCHAR(255) COMMENT 'Invoice Reference Number',
  `arn` VARCHAR(255) COMMENT 'Acknowledgement Reference Number',
  `qr_code_data` LONGTEXT COMMENT 'Encoded QR code data',
  `submission_response` JSON COMMENT 'Government API response',
  `error_details` JSON COMMENT 'Detailed error information',
  `retry_count` INT DEFAULT 0 COMMENT 'Number of retry attempts',
  `next_retry_time` TIMESTAMP NULL COMMENT 'Scheduled retry time',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Submission timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_submission_status` (`submission_status`),
  INDEX `idx_next_retry_time` (`next_retry_time`),
  UNIQUE KEY `unique_irn` (`irn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Invoice submissions to authorities';

-- Statuses Table
CREATE TABLE IF NOT EXISTS `einvoice_statuses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Associated invoice ID',
  `current_status` VARCHAR(50) COMMENT 'Current document status',
  `status_history` JSON COMMENT 'History of status changes',
  `last_status_update` TIMESTAMP NULL COMMENT 'Last status update time',
  `submission_status` VARCHAR(50) COMMENT 'Submission workflow status',
  `validation_status` VARCHAR(50) COMMENT 'Validation result status',
  `compliance_status` VARCHAR(50) COMMENT 'Compliance check status',
  `payment_status` ENUM('unpaid', 'partially_paid', 'paid', 'overdue') DEFAULT 'unpaid' COMMENT 'Payment status',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Invoice status tracking';

-- Parties Table (for caching party information)
CREATE TABLE IF NOT EXISTS `einvoice_parties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `party_id` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Unique party identifier',
  `party_name` VARCHAR(255) NOT NULL COMMENT 'Business name',
  `legal_name` VARCHAR(255) COMMENT 'Legal entity name',
  `party_type` ENUM('seller', 'buyer', 'intermediary') DEFAULT 'buyer' COMMENT 'Party type',
  `email` VARCHAR(255) COMMENT 'Email address',
  `phone` VARCHAR(20) COMMENT 'Phone number',
  `tax_number` VARCHAR(50) COMMENT 'Tax ID/VAT number',
  `registration_number` VARCHAR(50) COMMENT 'Business registration number',
  `address` TEXT COMMENT 'Full address',
  `country` VARCHAR(2) COMMENT 'Country code',
  `state` VARCHAR(100) COMMENT 'State/province',
  `city` VARCHAR(100) COMMENT 'City',
  `postal_code` VARCHAR(20) COMMENT 'Postal code',
  `bank_details` JSON COMMENT 'Banking information',
  `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Is party active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  INDEX `idx_party_id` (`party_id`),
  INDEX `idx_party_type` (`party_type`),
  INDEX `idx_tax_number` (`tax_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Party master data (buyers and sellers)';

-- Audit Log Table (for compliance and auditing)
CREATE TABLE IF NOT EXISTS `einvoice_audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` VARCHAR(100) COMMENT 'Associated invoice ID',
  `action` VARCHAR(100) NOT NULL COMMENT 'Action performed',
  `action_type` ENUM('create', 'update', 'delete', 'validate', 'submit', 'acknowledge', 'reject') COMMENT 'Type of action',
  `actor` VARCHAR(100) COMMENT 'User or system performing action',
  `old_value` JSON COMMENT 'Previous value',
  `new_value` JSON COMMENT 'New value',
  `details` TEXT COMMENT 'Additional details',
  `ip_address` VARCHAR(45) COMMENT 'IP address of the request',
  `user_agent` VARCHAR(255) COMMENT 'User agent string',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp',
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for all invoice operations';

-- Create indexes for common queries
CREATE INDEX idx_combined_status ON einvoice_invoices(document_status, created_at);
CREATE INDEX idx_combined_seller_status ON einvoice_invoices(seller_id, document_status);
CREATE INDEX idx_combined_buyer_status ON einvoice_invoices(buyer_id, document_status);
