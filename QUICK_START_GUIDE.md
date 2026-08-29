# CI3 E-Invoice Integration - Quick Start Guide

## Installation & Setup

### Step 1: Copy Files to Application

Copy the following files to your CodeIgniter 3 installation:

```bash
# Libraries
cp application/libraries/EInvoice_API.php your-ci3-app/application/libraries/
cp application/libraries/EInvoice_Service.php your-ci3-app/application/libraries/

# Models
cp application/models/EInvoice_Model.php your-ci3-app/application/models/

# Controllers
cp application/controllers/EInvoice.php your-ci3-app/application/controllers/
```

### Step 2: Create Database Tables

Run the migration SQL file to create all required tables:

```bash
# Using MySQL
mysql -u username -p database_name < application/migrations/001_create_einvoice_tables.sql

# Or import through phpMyAdmin
# 1. Go to Databases > Your Database > Import
# 2. Select the migration file
# 3. Click Import
```

### Step 3: Configure Routes (Optional)

In `application/config/routes.php`:

```php
// E-Invoice API Routes
$route['api/einvoice/create'] = 'einvoice/create';
$route['api/einvoice/validate'] = 'einvoice/validate';
$route['api/einvoice/get/(:any)'] = 'einvoice/get/$1';
$route['api/einvoice/submit/(:any)'] = 'einvoice/submit/$1';
$route['api/einvoice/list'] = 'einvoice/list_invoices';
$route['api/einvoice/status/(:any)'] = 'einvoice/status/$1';
$route['api/einvoice/delete/(:any)'] = 'einvoice/delete/$1';
```

### Step 4: Test the Installation

Use cURL to test basic functionality:

```bash
# Create an invoice
curl -X POST http://localhost/api/einvoice/create \
  -H "Content-Type: application/json" \
  -d @test-invoice.json

# List invoices
curl -X GET http://localhost/api/einvoice/list

# Get invoice status
curl -X GET http://localhost/api/einvoice/status/INV-2024-00123456
```

---

## Complete Example Workflow

### 1. Create an Invoice

**Request:**
```bash
POST /einvoice/create
Content-Type: application/json

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
    "registration_number": "REG123456",
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
    "registration_number": "REG654321",
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
      "description": "High-quality aluminum widget",
      "quantity": 10,
      "unit_of_measure": "pieces",
      "unit_price": 100.00,
      "tax_rate": 18
    },
    {
      "item_id": "ITEM002",
      "item_code": "SKU-002",
      "item_name": "Service B",
      "description": "Professional consulting service",
      "quantity": 5,
      "unit_of_measure": "hours",
      "unit_price": 500.00,
      "tax_rate": 18
    }
  ],
  "payment_terms": "Net 30 days",
  "notes": "Thank you for your business. Payment should be made within 30 days."
}
```

**Response:**
```json
{
  "success": true,
  "code": "INVOICE_CREATED",
  "message": "Invoice created successfully",
  "data": {
    "invoice_id": "INV-2024-00123456",
    "db_id": 1,
    "status": "draft",
    "total_amount": 9900.00
  },
  "timestamp": "2024-01-15 10:30:45"
}
```

### 2. Validate the Invoice

**Request:**
```bash
POST /einvoice/validate
Content-Type: application/json

{
  "invoice_number": "INV-2024-001",
  "invoice_date": "2024-01-15",
  "due_date": "2024-02-15",
  ...
}
```

**Response:**
```json
{
  "success": true,
  "code": "VALID",
  "message": "Invoice is valid",
  "data": {
    "validation_status": "valid",
    "errors": [],
    "warnings": [],
    "critical_errors": [],
    "is_valid": true
  },
  "timestamp": "2024-01-15 10:31:00"
}
```

### 3. Submit to Authority

**Request:**
```bash
POST /einvoice/submit/INV-2024-00123456
Content-Type: application/json
```

**Response:**
```json
{
  "success": true,
  "code": "SUBMISSION_SUBMITTED",
  "message": "Invoice submitted successfully",
  "data": {
    "submission_id": "SUB-1705317645-7a9b2c1d",
    "submission_status": "submitted",
    "irn": "8ABD2D9A3C4D5E6F7A8B9C0D1E2F3A4B5C6D7E8F9A0B1C2D3E4F5A6B7C8D9",
    "government_reference": "REF-20240115104045-a1b2c3d4",
    "qr_code_data": "eyJpcm4iOiI4QUJEMkQ5QTNDNEQ1RTZGNzE4QjlDMEQxRTJGM0E0QjVDNkQ3RThGOUEwQjFDMkQzRTRGNUE2QjdDOEQ5Ii..."
  },
  "timestamp": "2024-01-15 10:40:45"
}
```

### 4. Check Invoice Status

**Request:**
```bash
GET /einvoice/status/INV-2024-00123456
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
    "submission_status": "submitted",
    "payment_status": "unpaid",
    "last_status_update": "2024-01-15 10:40:45"
  },
  "timestamp": "2024-01-15 10:45:00"
}
```

### 5. Get Invoice Details

**Request:**
```bash
GET /einvoice/get/INV-2024-00123456
```

**Response:**
```json
{
  "success": true,
  "code": "INVOICE_FOUND",
  "message": "Invoice retrieved successfully",
  "data": {
    "invoice": {
      "invoice_id": "INV-2024-00123456",
      "invoice_number": "INV-2024-001",
      "total_amount": 9900.00,
      "document_status": "submitted",
      "seller_id": "SELLER001",
      "buyer_id": "BUYER001",
      "created_at": "2024-01-15 10:30:45"
    },
    "items": [...],
    "validation": {...},
    "submission": {...}
  },
  "timestamp": "2024-01-15 10:35:00"
}
```

### 6. List Invoices with Pagination

**Request:**
```bash
GET /einvoice/list?page=1&per_page=20&status=submitted&from_date=2024-01-01
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
        "invoice_id": "INV-2024-00123456",
        "invoice_number": "INV-2024-001",
        "total_amount": 9900.00,
        "document_status": "submitted"
      }
    ],
    "pagination": {
      "total_records": 150,
      "total_pages": 8,
      "current_page": 1,
      "per_page": 20,
      "has_next": true,
      "has_previous": false
    }
  },
  "timestamp": "2024-01-15 10:45:00"
}
```

---

## PHP Usage Examples

### Create Invoice Programmatically

```php
<?php

class YourController extends CI_Controller {
    
    public function process_invoice() {
        // Load library
        $this->load->library('EInvoice_Service');
        $this->load->model('EInvoice_Model');
        
        $service = new EInvoice_Service();
        
        // Prepare invoice data
        $invoice_data = array(
            'invoice_number' => 'INV-2024-001',
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'INR',
            'seller' => array(
                'party_id' => 'SELLER001',
                'party_name' => 'My Company',
                'tax_number' => '29ABCDE1234F2Z5',
                'email' => 'seller@mycompany.com'
            ),
            'buyer' => array(
                'party_id' => 'BUYER001',
                'party_name' => 'Customer Company',
                'tax_number' => '29XYZGH5678I9J0',
                'email' => 'buyer@customer.com'
            ),
            'items' => array(
                array(
                    'item_name' => 'Product A',
                    'quantity' => 5,
                    'unit_price' => 100.00,
                    'tax_rate' => 18
                )
            )
        );
        
        // Create invoice
        $invoice = $service->create_invoice($invoice_data);
        
        // Validate
        $validation = $service->validate_invoice($invoice);
        
        if ($validation->is_valid()) {
            // Save to database
            $db_id = $this->einvoice_model->save_invoice($invoice);
            $this->einvoice_model->save_invoice_items($db_id, $invoice->items);
            
            // Submit to authority
            $submission = $service->submit_invoice($invoice, $validation);
            
            echo "Invoice submitted successfully!";
            echo "IRN: " . $submission->irn;
        } else {
            // Handle validation errors
            echo "Validation failed: ";
            print_r($validation->critical_errors);
        }
    }
}
?>
```

---

## Common Use Cases

### Bulk Invoice Creation

```php
// Load CSV file with invoice data
$invoices = array_map('str_getcsv', file('invoices.csv'));

foreach ($invoices as $invoice_data) {
    $invoice = $service->create_invoice($invoice_data);
    $validation = $service->validate_invoice($invoice);
    
    if ($validation->is_valid()) {
        $submission = $service->submit_invoice($invoice, $validation);
        // Log submission result
    }
}
```

### Retry Failed Submissions

```php
// Get pending submissions
$this->db->where('submission_status', 'failed');
$this->db->where('retry_count <', 3);
$failed_submissions = $this->db->get('einvoice_submissions')->result();

foreach ($failed_submissions as $submission) {
    if ($submission->next_retry_time <= date('Y-m-d H:i:s')) {
        // Retry submission
        $invoice = $this->einvoice_model->get_invoice($submission->invoice_id);
        // Process retry...
    }
}
```

### Generate Reports

```php
// Get invoices by status
$this->db->where('document_status', 'submitted');
$this->db->where('created_at >=', date('Y-m-d', strtotime('-30 days')));
$invoices = $this->db->get('einvoice_invoices')->result();

$total_amount = array_sum(array_column($invoices, 'total_amount'));
$total_tax = array_sum(array_column($invoices, 'tax_total'));

// Generate report
echo "Total Invoices: " . count($invoices);
echo "Total Amount: " . $total_amount;
echo "Total Tax: " . $total_tax;
```

---

## Troubleshooting

### Invoice Validation Fails

1. Check required fields (invoice_number, invoice_date, seller, buyer, items)
2. Validate date format (YYYY-MM-DD)
3. Ensure all items have quantity > 0 and valid unit_price
4. Check party information is complete

### Database Connection Error

1. Verify database credentials in `config/database.php`
2. Ensure all tables are created
3. Check user privileges for the database

### Submission Fails

1. Validate invoice before submission
2. Check government API connectivity
3. Verify IRN generation
4. Review error logs in audit table

---

## Support

For issues or questions:
- Check the complete documentation: `E_INVOICE_API_DOCUMENTATION.md`
- Review error messages in the response
- Check database audit logs for detailed action history
- Enable debug mode in CodeIgniter for detailed logging

---

**Version:** 1.0.0  
**Last Updated:** January 15, 2024
