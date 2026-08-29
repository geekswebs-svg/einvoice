# CI3 E-Invoice Integration API Documentation

## Overview

The CI3 e-Invoice Integration API is a comprehensive RESTful API system for managing e-Invoices in CodeIgniter 3. It provides complete functionality for invoice creation, validation, submission to government authorities, and status tracking.

**Version:** 1.0.0  
**License:** MIT  
**Author:** GeekSwebs  

## Table of Contents

1. [API Types](#api-types)
2. [API Endpoints](#api-endpoints)
3. [Data Models](#data-models)
4. [Usage Examples](#usage-examples)
5. [Database Schema](#database-schema)
6. [Error Handling](#error-handling)
7. [Authentication](#authentication)
8. [Rate Limiting](#rate-limiting)

---

## API Types

### 1. EInvoice_Document
The main invoice document type representing a complete e-Invoice.

**Properties:**
- `invoice_id` (string): Unique invoice identifier
- `invoice_number` (string): Invoice number for reference
- `invoice_date` (string): Date of invoice (YYYY-MM-DD)
- `invoice_type` (string): Type of invoice ('invoice', 'credit_note', 'debit_note')
- `due_date` (string): Payment due date (YYYY-MM-DD)
- `currency` (string): Currency code (e.g., 'INR', 'USD')
- `document_status` (string): Current status ('draft', 'issued', 'submitted', 'accepted', 'rejected')
- `seller` (EInvoice_Party): Seller information
- `buyer` (EInvoice_Party): Buyer information
- `items` (array[EInvoice_Item]): Invoice line items
- `subtotal` (float): Sum of line amounts
- `tax_total` (float): Total tax amount
- `discount_amount` (float): Total discounts
- `total_amount` (float): Final amount due
- `payment_terms` (string): Payment terms description
- `notes` (string): Additional notes
- `digital_signature` (string): Digital signature for the invoice
- `irn` (string): Invoice Reference Number (for Indian e-Invoice)
- `qr_code` (string): QR code data
- `custom_fields` (object): Custom application-specific fields

**Methods:**
- `add_item(item)`: Add line item to invoice
- `calculate_totals()`: Recalculate total amounts
- `to_array()`: Convert to array for JSON serialization

### 2. EInvoice_Item
Represents a line item in an invoice.

**Properties:**
- `item_id` (string): Unique item identifier
- `item_code` (string): Product/service code
- `item_name` (string): Item name
- `description` (string): Detailed description
- `quantity` (float): Item quantity
- `unit_of_measure` (string): Unit (pieces, kg, hours, etc.)
- `unit_price` (float): Price per unit
- `line_amount` (float): Quantity × Unit Price
- `tax_rate` (float): Tax percentage (0-100)
- `tax_amount` (float): Calculated tax
- `total_amount` (float): Line amount + tax
- `additional_data` (object): Custom item fields

**Methods:**
- `calculate_totals()`: Recalculate item totals
- `to_array()`: Convert to array format

### 3. EInvoice_Party
Represents a party (Buyer/Seller).

**Properties:**
- `party_id` (string): Unique party identifier
- `party_name` (string): Business name
- `legal_name` (string): Legal entity name
- `email` (string): Email address
- `phone` (string): Phone number
- `tax_number` (string): Tax ID/VAT number
- `registration_number` (string): Business registration number
- `party_type` (string): 'seller', 'buyer', or 'intermediary'
- `address` (string): Full address
- `country` (string): Country code
- `state` (string): State/province
- `city` (string): City
- `postal_code` (string): Postal code
- `bank_details` (object): Banking information

### 4. EInvoice_Validation
Represents validation results for an invoice.

**Properties:**
- `validation_id` (string): Unique validation ID
- `invoice_id` (string): Associated invoice ID
- `validation_status` (string): 'valid', 'invalid', 'warning'
- `validation_timestamp` (string): When validation was performed
- `validation_schema` (string): Schema version used
- `errors` (array): Validation errors
- `warnings` (array): Non-critical warnings
- `critical_errors` (array): Critical errors preventing submission

**Methods:**
- `add_error(code, message, field)`: Add error
- `add_warning(code, message, field)`: Add warning
- `add_critical_error(code, message, field)`: Add critical error
- `is_valid()`: Check if invoice passes validation

### 5. EInvoice_Submission
Tracks invoice submission to authorities.

**Properties:**
- `submission_id` (string): Unique submission ID
- `invoice_id` (string): Associated invoice ID
- `submission_date` (string): Submission timestamp
- `submission_status` (string): 'pending', 'submitted', 'acknowledged', 'rejected', 'failed'
- `submission_channel` (string): 'api', 'web_portal', 'email', 'batch'
- `government_reference` (string): Government's reference number
- `irn` (string): Invoice Reference Number
- `arn` (string): Acknowledgement Reference Number
- `qr_code_data` (string): Encoded QR code
- `error_details` (array): Detailed error information
- `retry_count` (int): Number of retry attempts
- `max_retries` (int): Maximum retry attempts allowed
- `next_retry_time` (string): Scheduled retry time

**Methods:**
- `add_error(code, message, details)`: Log submission error
- `can_retry()`: Check if retry is possible
- `increment_retry()`: Increment retry counter with exponential backoff

### 6. EInvoice_Status
Represents the current status lifecycle of an invoice.

**Properties:**
- `invoice_id` (string): Invoice identifier
- `current_status` (string): Current document status
- `status_history` (array): History of status changes
- `last_status_update` (string): Last update timestamp
- `submission_status` (string): Submission workflow status
- `validation_status` (string): Validation result status
- `compliance_status` (string): Compliance check status
- `payment_status` (string): 'unpaid', 'partially_paid', 'paid', 'overdue'

**Methods:**
- `update_status(new_status, reason, metadata)`: Update status with history
- `to_array()`: Convert to array format

### 7. EInvoice_API_Response
Standard API response wrapper.

**Properties:**
- `success` (bool): Operation success indicator
- `code` (string): Response code
- `message` (string): Human-readable message
- `data` (mixed): Response data payload
- `errors` (array): Array of errors
- `timestamp` (string): Response timestamp

**Methods:**
- `to_array()`: Convert to array
- `to_json()`: Convert to JSON string

### 8. EInvoice_API_Error
Standardized error response format.

**Properties:**
- `error_code` (string): Error code
- `error_message` (string): Error description
- `error_type` (string): Type of error
- `details` (mixed): Additional error details
- `timestamp` (string): Error timestamp
- `request_id` (string): Request identifier for tracking
- `documentation_url` (string): Link to error documentation

---

## API Endpoints

### 1. Create Invoice
Creates a new draft invoice.

**Endpoint:**
```
POST /einvoice/create
```

**Request Body:**
```json
{
  "invoice_number": "INV-2024-001",
  "invoice_date": "2024-01-15",
  "due_date": "2024-02-15",
  "invoice_type": "invoice",
  "currency": "INR",
  "seller": {
    "party_id": "SELLER001",
    "party_name": "ABC Company",
    "legal_name": "ABC Company Pvt Ltd",
    "tax_number": "29ABCDE1234F2Z5",
    "email": "seller@abc.com",
    "phone": "+91-9876543210",
    "address": "123 Business Street",
    "city": "Mumbai",
    "state": "Maharashtra",
    "country": "IN",
    "postal_code": "400001"
  },
  "buyer": {
    "party_id": "BUYER001",
    "party_name": "XYZ Traders",
    "legal_name": "XYZ Traders Ltd",
    "tax_number": "29XYZGH5678I9J0",
    "email": "buyer@xyz.com",
    "phone": "+91-8765432109",
    "address": "456 Trade Road",
    "city": "Pune",
    "state": "Maharashtra",
    "country": "IN",
    "postal_code": "411001"
  },
  "items": [
    {
      "item_id": "ITEM001",
      "item_code": "SKU-001",
      "item_name": "Widget A",
      "description": "High-quality widget",
      "quantity": 10,
      "unit_of_measure": "pieces",
      "unit_price": 100.00,
      "tax_rate": 18
    },
    {
      "item_id": "ITEM002",
      "item_code": "SKU-002",
      "item_name": "Service B",
      "description": "Professional service",
      "quantity": 5,
      "unit_of_measure": "hours",
      "unit_price": 500.00,
      "tax_rate": 18
    }
  ],
  "payment_terms": "Net 30",
  "notes": "Thank you for your business"
}
```

**Response (Success):**
```json
{
  "success": true,
  "code": "INVOICE_CREATED",
  "message": "Invoice created successfully",
  "data": {
    "invoice_id": "INV-2024-00123456",
    "db_id": 42,
    "status": "draft",
    "total_amount": 9900.00
  },
  "timestamp": "2024-01-15 10:30:45"
}
```

**Response (Validation Error):**
```json
{
  "success": false,
  "code": "VALIDATION_FAILED",
  "message": "Invoice validation failed",
  "data": {
    "invoice_id": "INV-2024-00123456",
    "validation": {
      "validation_id": null,
      "invoice_id": "INV-2024-00123456",
      "validation_status": "invalid",
      "validation_timestamp": "2024-01-15 10:30:45",
      "errors": [],
      "warnings": [
        {
          "code": "MISSING_CURRENCY",
          "message": "Currency not specified, defaulting to INR",
          "field": "currency"
        }
      ],
      "critical_errors": [
        {
          "code": "MISSING_INVOICE_DATE",
          "message": "Invoice date is required",
          "field": "invoice_date"
        }
      ],
      "is_valid": false
    }
  },
  "timestamp": "2024-01-15 10:30:45"
}
```

---

### 2. Validate Invoice
Validates an invoice without creating it.

**Endpoint:**
```
POST /einvoice/validate
```

**Request Body:**
Same as Create Invoice

**Response:**
```json
{
  "success": true,
  "code": "VALID",
  "message": "Invoice is valid",
  "data": {
    "validation_id": null,
    "invoice_id": "INV-2024-00123456",
    "validation_status": "valid",
    "validation_timestamp": "2024-01-15 10:31:00",
    "errors": [],
    "warnings": [],
    "critical_errors": [],
    "is_valid": true
  },
  "timestamp": "2024-01-15 10:31:00"
}
```

---

### 3. Get Invoice
Retrieves invoice details with all related information.

**Endpoint:**
```
GET /einvoice/get/{invoice_id}
```

**Response:**
```json
{
  "success": true,
  "code": "INVOICE_FOUND",
  "message": "Invoice retrieved successfully",
  "data": {
    "invoice": {
      "id": 42,
      "invoice_id": "INV-2024-00123456",
      "invoice_number": "INV-2024-001",
      "invoice_date": "2024-01-15",
      "invoice_type": "invoice",
      "due_date": "2024-02-15",
      "currency": "INR",
      "document_status": "draft",
      "seller_id": "SELLER001",
      "buyer_id": "BUYER001",
      "subtotal": 8000.00,
      "tax_total": 1440.00,
      "discount_amount": 0.00,
      "total_amount": 9440.00,
      "created_at": "2024-01-15 10:30:45",
      "updated_at": "2024-01-15 10:30:45"
    },
    "items": [
      {
        "item_id": "ITEM001",
        "item_code": "SKU-001",
        "item_name": "Widget A",
        "quantity": 10,
        "unit_price": 100.00,
        "line_amount": 1000.00,
        "tax_rate": 18,
        "tax_amount": 180.00,
        "total_amount": 1180.00
      }
    ],
    "validation": null,
    "submission": null
  },
  "timestamp": "2024-01-15 10:35:00"
}
```

---

### 4. Submit Invoice
Submits validated invoice to government e-Invoice portal.

**Endpoint:**
```
POST /einvoice/submit/{invoice_id}
```

**Response (Success):**
```json
{
  "success": true,
  "code": "SUBMISSION_SUBMITTED",
  "message": "Invoice submitted successfully",
  "data": {
    "submission_id": "SUB-1705317645-7a9b2c1d",
    "invoice_id": "INV-2024-00123456",
    "submission_date": "2024-01-15 10:40:45",
    "submission_status": "submitted",
    "submission_channel": "api",
    "government_reference": "REF-20240115104045-a1b2c3d4",
    "irn": "8ABD2D9A3C4D5E6F7A8B9C0D1E2F3A4B5C6D7E8F9A0B1C2D3E4F5A6B7C8D9",
    "arn": null,
    "qr_code_data": "eyJpcm4iOiI4QUJEMkQ5QTNDNEQFRTZGNzE4QjlDMEQxRTJGM0E0QjVDNkQ3RThGOUEwQjFDMkQzRTRGNUE2QjdDOEQ5IiwiZ2VuZXJhdGVkX2F0IjoiMjAyNC0wMS0xNSAxMDo0MDo0NSIsInZlcnNpb24iOiIxLjAifQ==",
    "error_details": [],
    "retry_count": 0,
    "max_retries": 3,
    "next_retry_time": null,
    "can_retry": false
  },
  "timestamp": "2024-01-15 10:40:45"
}
```

---

### 5. Get Invoice List
Retrieves paginated list of invoices with filtering.

**Endpoint:**
```
GET /einvoice/list
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Records per page (default: 20)
- `status` (string): Filter by document status
- `seller_id` (string): Filter by seller
- `buyer_id` (string): Filter by buyer
- `from_date` (string): Filter from date (YYYY-MM-DD)
- `to_date` (string): Filter to date (YYYY-MM-DD)

**Example:**
```
GET /einvoice/list?page=2&per_page=15&status=submitted&from_date=2024-01-01
```

**Response:**
```json
{
  "success": true,
  "code": "INVOICES_FOUND",
  "message": "Invoices retrieved successfully",
  "data": {
    "data": [
      {
        "id": 42,
        "invoice_id": "INV-2024-00123456",
        "invoice_number": "INV-2024-001",
        "invoice_date": "2024-01-15",
        "total_amount": 9440.00,
        "document_status": "submitted"
      }
    ],
    "pagination": {
      "total_records": 150,
      "total_pages": 10,
      "current_page": 2,
      "per_page": 15,
      "has_next": true,
      "has_previous": true
    }
  },
  "timestamp": "2024-01-15 10:45:00"
}
```

---

### 6. Get Invoice Status
Retrieves current status and history of an invoice.

**Endpoint:**
```
GET /einvoice/status/{invoice_id}
```

**Response:**
```json
{
  "success": true,
  "code": "STATUS_FOUND",
  "message": "Invoice status retrieved successfully",
  "data": {
    "invoice_id": "INV-2024-00123456",
    "current_status": "submitted",
    "status_history": [],
    "last_status_update": "2024-01-15 10:30:45",
    "submission_status": "submitted",
    "validation_status": null,
    "compliance_status": null,
    "payment_status": "unpaid"
  },
  "timestamp": "2024-01-15 10:50:00"
}
```

---

### 7. Delete Invoice
Deletes an invoice and all associated data.

**Endpoint:**
```
DELETE /einvoice/delete/{invoice_id}
```

**Response:**
```json
{
  "success": true,
  "code": "INVOICE_DELETED",
  "message": "Invoice deleted successfully",
  "data": {
    "invoice_id": "INV-2024-00123456"
  },
  "timestamp": "2024-01-15 10:55:00"
}
```

---

## Database Schema

### Create Database Tables

```sql
-- Invoices Table
CREATE TABLE `einvoice_invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` VARCHAR(100) UNIQUE NOT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `invoice_type` ENUM('invoice', 'credit_note', 'debit_note') DEFAULT 'invoice',
  `due_date` DATE,
  `currency` VARCHAR(3) DEFAULT 'INR',
  `document_status` ENUM('draft', 'issued', 'submitted', 'accepted', 'rejected', 'cancelled', 'archived') DEFAULT 'draft',
  `seller_id` VARCHAR(100) NOT NULL,
  `buyer_id` VARCHAR(100) NOT NULL,
  `subtotal` DECIMAL(15,2),
  `tax_total` DECIMAL(15,2),
  `discount_amount` DECIMAL(15,2) DEFAULT 0,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `payment_terms` VARCHAR(255),
  `notes` TEXT,
  `digital_signature` TEXT,
  `irn` VARCHAR(255),
  `custom_fields` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_document_status` (`document_status`),
  INDEX `idx_invoice_date` (`invoice_date`),
  INDEX `idx_seller_id` (`seller_id`),
  INDEX `idx_buyer_id` (`buyer_id`)
);

-- Items Table
CREATE TABLE `einvoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `item_id` VARCHAR(100),
  `item_code` VARCHAR(50),
  `item_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_of_measure` VARCHAR(20),
  `unit_price` DECIMAL(12,2) NOT NULL,
  `line_amount` DECIMAL(15,2),
  `tax_rate` DECIMAL(5,2) DEFAULT 0,
  `tax_amount` DECIMAL(15,2),
  `total_amount` DECIMAL(15,2),
  `additional_data` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`invoice_id`) REFERENCES `einvoice_invoices`(`id`) ON DELETE CASCADE,
  INDEX `idx_invoice_id` (`invoice_id`)
);

-- Validations Table
CREATE TABLE `einvoice_validations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `validation_id` VARCHAR(100) UNIQUE,
  `invoice_id` VARCHAR(100) NOT NULL,
  `validation_status` ENUM('valid', 'invalid', 'warning') DEFAULT 'invalid',
  `validation_schema` VARCHAR(100),
  `errors` JSON,
  `warnings` JSON,
  `critical_errors` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_validation_status` (`validation_status`)
);

-- Submissions Table
CREATE TABLE `einvoice_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `submission_id` VARCHAR(100) UNIQUE,
  `invoice_id` VARCHAR(100) NOT NULL,
  `submission_status` ENUM('pending', 'submitted', 'acknowledged', 'rejected', 'failed') DEFAULT 'pending',
  `submission_channel` ENUM('api', 'web_portal', 'email', 'batch') DEFAULT 'api',
  `government_reference` VARCHAR(255),
  `irn` VARCHAR(255),
  `arn` VARCHAR(255),
  `qr_code_data` LONGTEXT,
  `submission_response` JSON,
  `error_details` JSON,
  `retry_count` INT DEFAULT 0,
  `next_retry_time` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_submission_status` (`submission_status`),
  UNIQUE KEY `unique_irn` (`irn`)
);

-- Statuses Table
CREATE TABLE `einvoice_statuses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` VARCHAR(100) UNIQUE NOT NULL,
  `current_status` VARCHAR(50),
  `status_history` JSON,
  `last_status_update` TIMESTAMP,
  `submission_status` VARCHAR(50),
  `validation_status` VARCHAR(50),
  `compliance_status` VARCHAR(50),
  `payment_status` ENUM('unpaid', 'partially_paid', 'paid', 'overdue') DEFAULT 'unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_invoice_id` (`invoice_id`),
  INDEX `idx_payment_status` (`payment_status`)
);
```

---

## Error Handling

### Error Codes

| Code | Type | Status | Description |
|------|------|--------|-------------|
| MISSING_INVOICE_NUMBER | validation | 400 | Invoice number is required |
| MISSING_INVOICE_DATE | validation | 400 | Invoice date is required |
| INVALID_INVOICE_DATE | validation | 400 | Invoice date format is invalid |
| MISSING_SELLER | validation | 400 | Seller information is required |
| MISSING_BUYER | validation | 400 | Buyer information is required |
| NO_ITEMS | validation | 400 | Invoice must contain at least one item |
| INVALID_AMOUNT | validation | 400 | Total amount must be greater than zero |
| NOT_FOUND | not_found | 404 | Invoice not found |
| INVALID_JSON | validation | 400 | Invalid JSON in request body |
| METHOD_NOT_ALLOWED | error | 405 | Invalid HTTP method |
| SERVER_ERROR | server_error | 500 | Internal server error |

### Error Response Format

```json
{
  "error_code": "NOT_FOUND",
  "error_message": "Invoice not found",
  "error_type": "not_found",
  "details": null,
  "timestamp": "2024-01-15 11:00:00",
  "request_id": "REQ_65a77a8c14e7a3.12345678",
  "documentation_url": null
}
```

---

## Usage Examples

### PHP Example

```php
<?php
$this->load->library('EInvoice_Service');
$service = new EInvoice_Service();

// Create invoice
$invoice_data = array(
    'invoice_number' => 'INV-2024-001',
    'invoice_date' => '2024-01-15',
    'due_date' => '2024-02-15',
    'currency' => 'INR',
    'seller' => array(
        'party_id' => 'SELLER001',
        'party_name' => 'ABC Company',
        'tax_number' => '29ABCDE1234F2Z5',
        'email' => 'seller@abc.com'
    ),
    'buyer' => array(
        'party_id' => 'BUYER001',
        'party_name' => 'XYZ Traders',
        'tax_number' => '29XYZGH5678I9J0',
        'email' => 'buyer@xyz.com'
    ),
    'items' => array(
        array(
            'item_id' => 'ITEM001',
            'item_name' => 'Widget A',
            'quantity' => 10,
            'unit_price' => 100.00,
            'tax_rate' => 18
        )
    )
);

// Create invoice object
$invoice = $service->create_invoice($invoice_data);

// Validate
$validation = $service->validate_invoice($invoice);

if ($validation->is_valid()) {
    echo "Invoice is valid!";
    // Submit to authority
    $submission = $service->submit_invoice($invoice, $validation);
    echo "IRN: " . $submission->irn;
} else {
    echo "Validation errors: ";
    print_r($validation->critical_errors);
}
?>
```

### cURL Example

```bash
# Create invoice
curl -X POST http://localhost/einvoice/create \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_number": "INV-2024-001",
    "invoice_date": "2024-01-15",
    "seller": {"party_id": "SELLER001", "party_name": "ABC Company"},
    "buyer": {"party_id": "BUYER001", "party_name": "XYZ Traders"},
    "items": [{"item_id": "ITEM001", "item_name": "Widget A", "quantity": 10, "unit_price": 100, "tax_rate": 18}]
  }'

# Get invoice
curl -X GET http://localhost/einvoice/get/INV-2024-00123456 \
  -H "Accept: application/json"

# Submit invoice
curl -X POST http://localhost/einvoice/submit/INV-2024-00123456 \
  -H "Content-Type: application/json"

# Get list
curl -X GET "http://localhost/einvoice/list?page=1&per_page=20&status=submitted" \
  -H "Accept: application/json"
```

---

## Integration Checklist

- [ ] Create database tables using provided schema
- [ ] Copy library files to `/application/libraries/`
- [ ] Copy model files to `/application/models/`
- [ ] Copy controller files to `/application/controllers/`
- [ ] Configure routes if necessary
- [ ] Set up authentication middleware
- [ ] Test invoice creation with sample data
- [ ] Test validation rules
- [ ] Test invoice submission workflow
- [ ] Configure retry policies
- [ ] Set up error logging
- [ ] Configure rate limiting
- [ ] Deploy to production

---

## License

MIT License - See LICENSE file for details

---

**Last Updated:** January 15, 2024  
**Version:** 1.0.0
