# CI3 E-Invoice Integration API

A complete, production-ready e-Invoice integration system for CodeIgniter 3. This comprehensive solution provides full RESTful API functionality for invoice lifecycle management including creation, validation, submission to government authorities, and detailed tracking.

## 📋 Overview

The CI3 e-Invoice Integration API implements a complete object-oriented system with type definitions, service layer, data models, and RESTful controllers. It's designed to handle complex e-Invoice workflows while maintaining data integrity and providing comprehensive audit trails.

**Key Features:**
- ✅ Complete invoice lifecycle management (draft → submitted → accepted)
- ✅ Comprehensive validation engine with error categorization
- ✅ Government authority integration ready (Indian e-Invoice format)
- ✅ Automatic IRN and QR code generation
- ✅ Submission retry logic with exponential backoff
- ✅ Paginated list views with advanced filtering
- ✅ Full audit trail logging
- ✅ JSON request/response format with standardized error handling
- ✅ Database persistence with optimized queries
- ✅ Party master data management
- ✅ Multi-item invoice support with automatic totals
- ✅ Custom fields for application-specific data

## 📁 Project Structure

```
application/
├── libraries/
│   ├── EInvoice_API.php          # Type definitions (8 classes)
│   └── EInvoice_Service.php      # Business logic service
├── models/
│   └── EInvoice_Model.php        # Database operations
├── controllers/
│   └── EInvoice.php              # REST API endpoints (7 endpoints)
└── migrations/
    └── 001_create_einvoice_tables.sql  # Database schema

Root files:
├── E_INVOICE_API_DOCUMENTATION.md    # Complete API documentation
├── QUICK_START_GUIDE.md              # Setup and integration guide
├── example-invoice.json              # Sample invoice payload
└── README.md                         # This file
```

## 🚀 Quick Start

### Installation

1. **Copy files to your CI3 application:**
   ```bash
   cp application/libraries/EInvoice_*.php YOUR_APP/application/libraries/
   cp application/models/EInvoice_Model.php YOUR_APP/application/models/
   cp application/controllers/EInvoice.php YOUR_APP/application/controllers/
   ```

2. **Create database tables:**
   ```bash
   mysql -u username -p database < application/migrations/001_create_einvoice_tables.sql
   ```

3. **Test the API:**
   ```bash
   curl -X POST http://localhost/einvoice/create \
     -H "Content-Type: application/json" \
     -d @example-invoice.json
   ```

## 📚 API Types

### 1. **EInvoice_Document**
Main invoice object with seller, buyer, items, and financial totals.
- Properties: invoice_id, invoice_number, invoice_date, seller, buyer, items, totals
- Methods: add_item(), calculate_totals(), to_array()

### 2. **EInvoice_Item**
Line item representation with tax calculation.
- Properties: item_id, item_name, quantity, unit_price, tax_rate, line_amount, tax_amount
- Methods: calculate_totals(), to_array()

### 3. **EInvoice_Party**
Party information for buyer/seller with complete address and tax details.
- Properties: party_id, party_name, tax_number, email, phone, address components, bank_details
- Methods: to_array()

### 4. **EInvoice_Validation**
Validation results with error categorization.
- Properties: validation_id, validation_status, errors, warnings, critical_errors
- Methods: add_error(), add_warning(), add_critical_error(), is_valid()

### 5. **EInvoice_Submission**
Submission tracking with retry logic.
- Properties: submission_id, submission_status, irn, arn, government_reference, error_details, retry_count
- Methods: add_error(), can_retry(), increment_retry()

### 6. **EInvoice_Status**
Status lifecycle tracking.
- Properties: current_status, status_history, submission_status, validation_status, payment_status
- Methods: update_status(), to_array()

### 7. **EInvoice_API_Response**
Standard response wrapper for all API responses.
- Properties: success, code, message, data, errors, timestamp
- Methods: to_array(), to_json()

### 8. **EInvoice_API_Error**
Standardized error response format.
- Properties: error_code, error_message, error_type, details, timestamp, request_id
- Methods: to_array()

## 🔌 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/einvoice/create` | Create new invoice |
| POST | `/einvoice/validate` | Validate invoice without creating |
| GET | `/einvoice/get/{id}` | Get invoice details |
| POST | `/einvoice/submit/{id}` | Submit to authority |
| GET | `/einvoice/list` | List invoices (paginated) |
| GET | `/einvoice/status/{id}` | Get invoice status |
| DELETE | `/einvoice/delete/{id}` | Delete invoice |

### Example Request

```json
POST /einvoice/create
Content-Type: application/json

{
  "invoice_number": "INV-2024-001",
  "invoice_date": "2024-01-15",
  "due_date": "2024-02-15",
  "seller": {
    "party_id": "SELLER001",
    "party_name": "ABC Company",
    "tax_number": "29ABCDE1234F2Z5",
    "email": "seller@abc.com"
  },
  "buyer": {
    "party_id": "BUYER001",
    "party_name": "XYZ Traders",
    "tax_number": "29XYZGH5678I9J0",
    "email": "buyer@xyz.com"
  },
  "items": [
    {
      "item_name": "Widget A",
      "quantity": 10,
      "unit_price": 100.00,
      "tax_rate": 18
    }
  ]
}
```

### Example Response

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

## 🗄️ Database Schema

The system creates 8 tables:

1. **einvoice_invoices** - Main invoice documents
2. **einvoice_items** - Line items with tax calculations
3. **einvoice_validations** - Validation results
4. **einvoice_submissions** - Submission tracking
5. **einvoice_statuses** - Status lifecycle
6. **einvoice_parties** - Party master data (cached)
7. **einvoice_audit_logs** - Complete audit trail
8. Plus composite indexes for common queries

See `E_INVOICE_API_DOCUMENTATION.md` for detailed schema.

## ✔️ Validation Features

The validation engine checks:
- Required fields (invoice number, date, parties, items)
- Date format validation
- Party information completeness
- Item data integrity (quantity, price, tax rate)
- Total amount calculations
- Email format validation
- Tax rate ranges (0-100)

Error types:
- **Critical Errors** - Prevent submission
- **Errors** - Must be corrected
- **Warnings** - Non-blocking recommendations

## 🔄 Invoice Lifecycle

```
DRAFT → ISSUED → SUBMITTED → ACCEPTED/REJECTED → CANCELLED/ARCHIVED
  ↓
VALIDATION
  ├─ Valid
  └─ Invalid (Validation Errors)

SUBMISSION
  ├─ Submitted
  │  ├─ Acknowledged
  │  └─ Rejected (with retry logic)
  └─ Failed (retry with exponential backoff)
```

## 💾 Data Persistence

All invoice data is persisted to MySQL with:
- Automatic timestamp tracking (created_at, updated_at)
- Foreign key constraints for data integrity
- Composite indexes for query optimization
- JSON field support for flexible data storage
- Audit logging for compliance

## 🔐 Security Features

- Input validation for all API endpoints
- JSON type validation
- Date format enforcement
- Email validation
- Tax number format checking
- Request ID tracking for troubleshooting
- Comprehensive audit trail

## 📊 Advanced Features

### Pagination
```bash
GET /einvoice/list?page=1&per_page=20&status=submitted&from_date=2024-01-01
```

### Filtering
- By document status
- By seller/buyer ID
- By date range
- Sortable results

### Retry Logic
```php
// Automatic retry with exponential backoff
// Retry 1: +2 minutes
// Retry 2: +4 minutes
// Retry 3: +8 minutes
```

### Audit Trail
Every action is logged:
- Action type (create, update, delete, validate, submit)
- User/system performing action
- Old and new values
- Timestamp
- IP address and user agent

## 📖 Documentation

- **E_INVOICE_API_DOCUMENTATION.md** - Complete API reference with all endpoint details, request/response examples, error codes, and data models
- **QUICK_START_GUIDE.md** - Step-by-step installation, configuration, and integration guide with code examples
- **example-invoice.json** - Ready-to-use invoice payload for testing

## 💡 Usage Examples

### Create and Submit Invoice

```php
$this->load->library('EInvoice_Service');
$this->load->model('EInvoice_Model');

$service = new EInvoice_Service();
$invoice = $service->create_invoice($invoice_data);
$validation = $service->validate_invoice($invoice);

if ($validation->is_valid()) {
    $this->einvoice_model->save_invoice($invoice);
    $submission = $service->submit_invoice($invoice);
    echo "IRN: " . $submission->irn;
}
```

### Get Invoice List with Filters

```bash
curl -X GET "http://localhost/einvoice/list?page=1&per_page=20&status=submitted&from_date=2024-01-01"
```

### Check Submission Status

```bash
curl -X GET http://localhost/einvoice/status/INV-2024-00123456
```

## 🛠️ Configuration

### Optional Route Configuration

Add to `application/config/routes.php`:

```php
$route['api/einvoice/create'] = 'einvoice/create';
$route['api/einvoice/validate'] = 'einvoice/validate';
$route['api/einvoice/get/(:any)'] = 'einvoice/get/$1';
// ... more routes
```

### Enable Caching (Optional)

Implement caching for invoice lookups to improve performance:

```php
$invoice = $this->cache->get('invoice_' . $invoice_id);
if (!$invoice) {
    $invoice = $this->einvoice_model->get_invoice($invoice_id);
    $this->cache->save('invoice_' . $invoice_id, $invoice, 3600);
}
```

## 📝 Error Handling

All errors follow a standard format with:
- **error_code** - Machine-readable error identifier
- **error_message** - Human-readable description
- **error_type** - Category (validation, authentication, not_found, server_error)
- **request_id** - Unique ID for tracking
- **timestamp** - When error occurred

Common error codes:
- `VALIDATION_FAILED` - Invoice data validation failed
- `NOT_FOUND` - Invoice not found
- `INVALID_JSON` - Malformed JSON request
- `METHOD_NOT_ALLOWED` - Wrong HTTP method
- `SERVER_ERROR` - Internal server error

## 🔄 Integration Workflow

1. **Prepare Invoice Data** - Gather seller, buyer, and item information
2. **Validate** - Check data integrity
3. **Create** - Save invoice to database in draft status
4. **Submit** - Send to government e-Invoice portal
5. **Track** - Monitor submission status and handle retries
6. **Archive** - Store for compliance and auditing

## 📈 Performance Optimization

- Composite indexes on frequently queried columns
- Pagination for large result sets
- JSON field support for flexible data without schema changes
- Efficient SQL queries with WHERE clauses and JOINs
- Exponential backoff for retries to reduce server load

## 🧪 Testing

Use the provided `example-invoice.json`:

```bash
# Create invoice
curl -X POST http://localhost/einvoice/create \
  -H "Content-Type: application/json" \
  -d @example-invoice.json

# Validate without creating
curl -X POST http://localhost/einvoice/validate \
  -H "Content-Type: application/json" \
  -d @example-invoice.json

# Get invoice
curl -X GET http://localhost/einvoice/get/INV-2024-00123456

# Submit
curl -X POST http://localhost/einvoice/submit/INV-2024-00123456

# List
curl -X GET http://localhost/einvoice/list?page=1&per_page=20

# Delete
curl -X DELETE http://localhost/einvoice/delete/INV-2024-00123456
```

## 📋 Checklist

- [ ] Copy library files
- [ ] Copy model and controller files
- [ ] Create database tables
- [ ] Test invoice creation
- [ ] Test validation
- [ ] Test submission workflow
- [ ] Configure routes (optional)
- [ ] Set up logging
- [ ] Configure authentication (if needed)
- [ ] Deploy to production

## 📄 License

MIT License - See LICENSE file for details

## 👥 Author

GeekSwebs - Professional Web Development

## 📞 Support

For documentation and examples, see:
- `E_INVOICE_API_DOCUMENTATION.md` - Complete API reference
- `QUICK_START_GUIDE.md` - Integration guide
- `example-invoice.json` - Sample payload

## 🔄 Changelog

### Version 1.0.0 (2024-01-15)
- Initial release
- Complete API type system
- RESTful endpoints
- Database persistence
- Validation engine
- Submission workflow
- Audit logging
- Comprehensive documentation

---

**Version:** 1.0.0  
**Last Updated:** January 15, 2024  
**Status:** Production Ready
