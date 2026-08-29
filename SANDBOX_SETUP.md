# CI3 e-Invoice API - Sandbox & Production Environment Guide

## Overview

This guide provides complete setup and configuration instructions for both sandbox (testing/development) and production environments for the CI3 e-Invoice Integration API.

---

## Table of Contents

1. [Sandbox Environment](#sandbox-environment)
2. [Production Environment](#production-environment)
3. [Environment Configuration](#environment-configuration)
4. [Database Setup](#database-setup)
5. [Security Configuration](#security-configuration)
6. [Deployment Checklist](#deployment-checklist)
7. [Monitoring & Logging](#monitoring--logging)
8. [Troubleshooting](#troubleshooting)

---

## Sandbox Environment

### Purpose
- Development and testing
- Integration testing
- API testing with mock government endpoints
- Performance testing
- Training and documentation

### System Requirements

**Sandbox Server Specs:**
```
CPU:                 2-4 cores
RAM:                 2-4 GB
Storage:             20 GB SSD
PHP Version:         7.2+
MySQL Version:       5.7+
CodeIgniter:         3.1+
```

**Software Stack:**
```
Web Server:          Apache 2.4+ or Nginx 1.14+
PHP:                 7.2, 7.3, 7.4, 8.0+
Database:            MySQL 5.7+ or MariaDB 10.3+
Version Control:     Git 2.0+
```

### Installation

#### Step 1: Clone Repository

```bash
# Clone the repository
git clone https://github.com/geekswebs-svg/einvoice.git einvoice-sandbox

# Navigate to project
cd einvoice-sandbox

# Checkout sandbox branch
git checkout geekswebs-svg-ci3-einvoice-api-types
```

#### Step 2: Install PHP Dependencies

```bash
# Install Composer packages
composer install

# Optional: Update dependencies
composer update
```

#### Step 3: Configure CodeIgniter

**File: `application/config/config.php`**

```php
// Base URL
$config['base_url'] = 'http://localhost:8000/'; // For development

// Index file
$config['index_page'] = '';

// URI protocol
$config['uri_protocol'] = 'REQUEST_URI';

// Encryption key
$config['encryption_key'] = 'your-sandbox-encryption-key-here';

// Session configuration
$config['sess_driver'] = 'database';
$config['sess_table_name'] = 'ci_sessions';
$config['sess_match_ip'] = FALSE; // Allow testing from multiple IPs
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;

// Enable profiler in sandbox (optional)
// $config['enable_profiler'] = TRUE;
```

**File: `application/config/database.php`**

```php
$db['default'] = array(
    'dsn'      => '',
    'hostname' => 'localhost',
    'username' => 'sandbox_user',
    'password' => 'sandbox_password',
    'database' => 'einvoice_sandbox',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => TRUE, // Enable debugging in sandbox
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE, // Log all queries
);
```

#### Step 4: Create Sandbox Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE einvoice_sandbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create user
mysql -u root -p -e "CREATE USER 'sandbox_user'@'localhost' IDENTIFIED BY 'sandbox_password';"

# Grant privileges
mysql -u root -p -e "GRANT ALL PRIVILEGES ON einvoice_sandbox.* TO 'sandbox_user'@'localhost';"

# Refresh privileges
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Import schema
mysql -u sandbox_user -p einvoice_sandbox < application/migrations/001_create_einvoice_tables.sql
```

#### Step 5: Configure Routes

**File: `application/config/routes.php`**

```php
// Sandbox routes with api prefix
$route['api/v1/einvoice/create'] = 'einvoice/create';
$route['api/v1/einvoice/validate'] = 'einvoice/validate';
$route['api/v1/einvoice/get/(:any)'] = 'einvoice/get/$1';
$route['api/v1/einvoice/submit/(:any)'] = 'einvoice/submit/$1';
$route['api/v1/einvoice/list'] = 'einvoice/list_invoices';
$route['api/v1/einvoice/status/(:any)'] = 'einvoice/status/$1';
$route['api/v1/einvoice/delete/(:any)'] = 'einvoice/delete/$1';

// Health check endpoint (useful for monitoring)
$route['api/v1/health'] = 'health/check';
```

#### Step 6: Enable Error Logging

**File: `application/config/config.php`**

```php
// Logging
$config['log_threshold'] = 4; // Log everything in sandbox
$config['log_path'] = 'application/logs/';
$config['log_file_extension'] = 'log';
```

#### Step 7: Test Installation

```bash
# Navigate to project directory
cd /path/to/einvoice-sandbox

# Start PHP server
php -S localhost:8000

# In another terminal, test the API
curl -X POST http://localhost:8000/api/v1/einvoice/validate \
  -H "Content-Type: application/json" \
  -d @example-invoice.json
```

### Sandbox Configuration File

Create `application/config/sandbox.php`:

```php
<?php
/**
 * Sandbox Configuration
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// Government API endpoints (MOCK)
$config['sandbox_mode'] = TRUE;
$config['gov_api_endpoint'] = 'http://localhost:9000/mock-api/'; // Mock API
$config['gov_api_timeout'] = 30;

// Mock response settings
$config['mock_irn_prefix'] = 'SANDBOX-';
$config['mock_response_delay'] = 0; // milliseconds

// Email configuration (use localhost in sandbox)
$config['mail_driver'] = 'log';
$config['mail_log_path'] = 'application/logs/emails/';

// Debug settings
$config['debug_mode'] = TRUE;
$config['debug_log_sql'] = TRUE;
$config['debug_log_api_calls'] = TRUE;

// Test data
$config['test_seller_id'] = 'SELLER-SANDBOX-001';
$config['test_buyer_id'] = 'BUYER-SANDBOX-001';

// Retry settings for testing
$config['max_retries'] = 3;
$config['retry_delay'] = 5; // seconds

/* End of file sandbox.php */
