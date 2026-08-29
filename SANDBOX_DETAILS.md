# Sandbox Environment Configuration
# For local development and testing only

## Quick Start
```bash
# 1. Install dependencies
composer install

# 2. Copy environment file
cp .env.sandbox .env

# 3. Create database
mysql -u root -p < application/migrations/001_create_einvoice_tables.sql

# 4. Start development server
php -S localhost:8000

# 5. Test API
curl http://localhost:8000/api/v1/health
```

## Environment Variables

```env
# Application
CI_ENV=development
APP_MODE=SANDBOX
DEBUG_MODE=TRUE
LOG_LEVEL=DEBUG
LOG_PATH=application/logs
CACHE_PATH=application/cache

# Database
DB_DRIVER=mysqli
DB_HOST=localhost
DB_PORT=3306
DB_USER=sandbox_user
DB_PASS=sandbox_password
DB_NAME=einvoice_sandbox
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_DEBUG=TRUE

# Cache
CACHE_DRIVER=file
CACHE_TTL=3600

# Session
SESSION_DRIVER=database
SESSION_TIMEOUT=86400
SESSION_COOKIE_SECURE=false

# Government API
GOV_API_MODE=mock
GOV_API_ENDPOINT=http://localhost:9000/mock-api/
GOV_API_TIMEOUT=30
GOV_API_DEBUG=TRUE

# Email
MAIL_DRIVER=log
MAIL_LOG_PATH=application/logs/emails
MAIL_FROM=dev@localhost

# Security
ENCRYPTION_KEY=development_key_not_secure
JWT_SECRET=sandbox_jwt_secret_123456
CORS_ALLOWED_ORIGINS=*

# Monitoring
SENTRY_DSN=  # Optional: Leave empty for local development
MONITORING_ENABLED=false
```

## Mock API Setup

### Start Mock Government API Server

```bash
# Terminal 1: Start mock API server
cd scripts
node mock-api-server.js

# Expected output:
# Mock e-Invoice API Server listening on http://localhost:9000
```

### Mock API Endpoints

```bash
# Get mock IRN
curl -X POST http://localhost:9000/mock-api/irn \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_number": "INV-001",
    "invoice_date": "2024-01-15"
  }'

# Response:
{
  "success": true,
  "irn": "DEV-1705323000-abc123def456",
  "qr_code": "base64_encoded_qr_data",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

## Database Setup

### Create Sandbox Database

```bash
mysql -u root -p << 'EOF'
CREATE DATABASE IF NOT EXISTS einvoice_sandbox 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'sandbox_user'@'localhost' 
  IDENTIFIED BY 'sandbox_password';

GRANT ALL PRIVILEGES ON einvoice_sandbox.* 
  TO 'sandbox_user'@'localhost';

FLUSH PRIVILEGES;
EOF
```

### Import Schema

```bash
mysql -u sandbox_user -p sandbox_password einvoice_sandbox \
  < application/migrations/001_create_einvoice_tables.sql
```

### Seed Test Data

```bash
mysql -u sandbox_user -p sandbox_password einvoice_sandbox << 'EOF'
-- Insert test sellers
INSERT INTO einvoice_parties (
  party_id, party_type, name, email, phone, address, city, state, zip, gstin
) VALUES (
  'SELLER-001', 'seller', 'Test Company Ltd', 'seller@test.com', '9876543210',
  '123 Test Street', 'Mumbai', 'MH', '400001', '27AAPCU1234H1Z0'
);

-- Insert test buyers
INSERT INTO einvoice_parties (
  party_id, party_type, name, email, phone, address, city, state, zip, gstin
) VALUES (
  'BUYER-001', 'buyer', 'Test Customer Inc', 'buyer@test.com', '8765432109',
  '456 Buyer Avenue', 'Delhi', 'DL', '110001', '07AABCT1234H1Z0'
);
EOF
```

## Testing Workflows

### 1. Create Invoice
```bash
curl -X POST http://localhost:8000/api/v1/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "seller_id": "SELLER-001",
    "buyer_id": "BUYER-001",
    "invoice_number": "INV-001",
    "invoice_date": "2024-01-15",
    "items": [
      {
        "item_code": "SKU-001",
        "description": "Test Product",
        "quantity": 10,
        "unit_price": 100,
        "tax_rate": 18,
        "tax_amount": 180,
        "line_amount": 1180
      }
    ],
    "notes": "Test invoice"
  }'
```

### 2. Validate Invoice
```bash
curl -X POST http://localhost:8000/api/v1/invoices/INV-001/validate \
  -H "Content-Type: application/json"
```

### 3. Submit Invoice
```bash
curl -X POST http://localhost:8000/api/v1/invoices/INV-001/submit \
  -H "Content-Type: application/json" \
  -d '{
    "push_to_portal": true,
    "send_email": false
  }'
```

### 4. Check Status
```bash
curl -X GET http://localhost:8000/api/v1/invoices/INV-001/status \
  -H "Authorization: Bearer sandbox_token"
```

### 5. List Invoices
```bash
curl -X GET "http://localhost:8000/api/v1/invoices?status=draft&limit=10" \
  -H "Authorization: Bearer sandbox_token"
```

## Debugging

### Enable Query Logging
```php
// In EInvoice_Service.php
$this->db->enable_profiler = TRUE;
// View profiler output at end of request
```

### Enable Full Debug Output
```env
DEBUG_MODE=TRUE
LOG_LEVEL=DEBUG
```

### Monitor Logs Real-time
```bash
tail -f application/logs/application.log
```

### Check Database Queries
```bash
mysql -u sandbox_user -p sandbox_password einvoice_sandbox
SHOW PROCESSLIST;  # See running queries
SELECT * FROM einvoice_audit_logs ORDER BY created_at DESC LIMIT 10;
```

## Performance Monitoring

### Check Database Performance
```bash
# Monitor slow queries
watch -n 1 'mysql -u sandbox_user -p sandbox_password einvoice_sandbox -e "SHOW PROCESSLIST;"'
```

### Monitor Memory Usage
```bash
# PHP process memory
ps aux | grep php | grep -v grep

# Disk usage
du -sh application/logs application/cache
```

### Monitor HTTP Requests
```bash
# Using Apache Bench (load testing)
ab -n 1000 -c 10 http://localhost:8000/api/v1/health

# Using WRK (concurrent load testing)
wrk -t12 -c400 -d30s http://localhost:8000/api/v1/health
```

## Common Tasks

### Reset Database
```bash
# Drop and recreate
mysql -u sandbox_user -p sandbox_password einvoice_sandbox \
  -e "DROP DATABASE einvoice_sandbox;"
mysql -u root -p < scripts/setup-sandbox.sh
```

### Clear Cache
```bash
rm -rf application/cache/*
```

### Clear Logs
```bash
rm -f application/logs/*
```

### Recreate Tables
```bash
mysql -u sandbox_user -p sandbox_password einvoice_sandbox \
  < application/migrations/001_create_einvoice_tables.sql
```

## Port Configuration

| Service | Default Port | Config |
|---------|------------|--------|
| PHP Dev Server | 8000 | N/A |
| MySQL | 3306 | localhost |
| Mock API | 9000 | scripts/mock-api-server.js |
| Redis | 6379 | (not used in sandbox) |

## File Locations

```
.
├── application/
│   ├── config/           # Configuration files
│   ├── controllers/      # API controllers
│   ├── libraries/        # Core business logic
│   ├── models/          # Database models
│   ├── migrations/      # Database migrations
│   ├── logs/            # Application logs
│   └── cache/           # File cache storage
├── scripts/
│   ├── mock-api-server.js
│   └── test-workflows.sh
├── public/              # Web root
└── .env.sandbox        # Environment variables
```

## Troubleshooting

### Port Already in Use
```bash
# Kill existing process
lsof -i :8000
kill -9 <PID>
```

### Permission Denied
```bash
# Fix permissions
chmod 755 application/logs
chmod 755 application/cache
chmod 644 application/logs/*
```

### Database Connection Failed
```bash
# Test connection
mysql -u sandbox_user -p sandbox_password -h localhost -e "SELECT 1"
```

### Mock API Not Responding
```bash
# Check if running
curl http://localhost:9000/mock-api/health
# Or start it
node scripts/mock-api-server.js
```

## Next Steps

1. Review API documentation in `E_INVOICE_API_DOCUMENTATION.md`
2. Run test workflows in `Testing Workflows` section
3. Check response in browser DevTools or Postman
4. Modify test data in `scripts/test-data.json`
5. Study error handling in `E_INVOICE_API.php`
6. Review database schema in `001_create_einvoice_tables.sql`
7. Practice debugging with logs and database queries
8. When ready: Deploy to production using `PRODUCTION_DEPLOYMENT.md`

---

**Last Updated:** 2024-01-15
**Version:** 1.0
