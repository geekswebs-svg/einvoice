# CI3 e-Invoice API - Production Deployment Guide

## Production Environment Overview

### Purpose
- Live invoice processing
- Real government authority integration
- Production data storage
- Customer-facing service

### System Requirements

**Recommended Production Server Specs:**
```
CPU:                 8+ cores
RAM:                 8-16 GB
Storage:             100+ GB SSD
Network:             Dedicated bandwidth (10 Mbps+)
Redundancy:          Primary + Standby
PHP Version:         7.4 or 8.0+
MySQL Version:       5.7+ or 8.0+
CodeIgniter:         3.1+
```

**Operating System:**
- Ubuntu 18.04 LTS+ or CentOS 7+
- Kernel: 4.15+
- Firewall: UFW or firewalld enabled

**Software Stack - Production:**
```
Web Server:          Nginx 1.19+ (recommended for production)
PHP-FPM:             7.4 or 8.0+ with OpCache
Database:            MySQL 8.0+ with replication
Redis:               6.0+ (for caching)
SSL/TLS:             Let's Encrypt or commercial certificate
Monitoring:          Prometheus + Grafana
Logging:             ELK Stack (Elasticsearch, Logstash, Kibana)
```

---

## Pre-Production Checklist

### Security Audit
- [ ] SSL/TLS certificate installed
- [ ] Firewall rules configured
- [ ] API authentication implemented
- [ ] Rate limiting enabled
- [ ] Input validation reviewed
- [ ] SQL injection prevention verified
- [ ] XSS protection enabled
- [ ] CORS properly configured
- [ ] Security headers set
- [ ] Sensitive data not logged

### Performance Testing
- [ ] Load testing completed (1000+ requests/sec)
- [ ] Stress testing completed
- [ ] Database query optimization verified
- [ ] Caching strategy implemented
- [ ] CDN configured (if applicable)
- [ ] API response time < 200ms
- [ ] Database backup tested
- [ ] Recovery procedures tested

### Compliance & Legal
- [ ] Data privacy policy reviewed
- [ ] GDPR compliance verified
- [ ] Local regulations checked
- [ ] Audit logging configured
- [ ] Terms of Service prepared
- [ ] SLA agreements drafted

---

## Production Installation

### Step 1: Server Setup

#### Ubuntu Server Setup

```bash
# Update system
sudo apt-get update
sudo apt-get upgrade -y

# Install required packages
sudo apt-get install -y \
    nginx \
    php-fpm \
    php-mysql \
    php-mbstring \
    php-xml \
    php-json \
    php-curl \
    mysql-server \
    redis-server \
    git \
    curl \
    wget \
    htop \
    composer

# Configure firewall
sudo ufw enable
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
```

### Step 2: SSL/TLS Setup

#### Install Certbot for Let's Encrypt

```bash
sudo apt-get install -y certbot python3-certbot-nginx

# Generate certificate
sudo certbot certonly --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### Step 3: Nginx Configuration

**File: `/etc/nginx/sites-available/einvoice-prod`**

```nginx
# HTTP redirect to HTTPS
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }
    
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS Production Server
server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    # SSL configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Project root
    root /var/www/einvoice/public;
    index index.php;
    
    # Logging
    access_log /var/log/nginx/einvoice-access.log;
    error_log /var/log/nginx/einvoice-error.log warn;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript 
               application/json application/javascript application/xml+rss 
               application/rss+xml font/truetype font/opentype 
               application/vnd.ms-fontobject image/svg+xml;
    
    # PHP-FPM configuration
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param CI_ENV production;
        include fastcgi_params;
        
        # PHP-FPM timeout
        fastcgi_read_timeout 300s;
        fastcgi_connect_timeout 75s;
    }
    
    # CodeIgniter routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/einvoice-prod /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 4: PHP-FPM Configuration

**File: `/etc/php/8.0/fpm/pool.d/einvoice.conf`**

```ini
[einvoice]
user = www-data
group = www-data
listen = /var/run/php/php8.0-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Process management
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
pm.process_idle_timeout = 10s

; Performance
pm.status_path = /status
slowlog = /var/log/php-fpm/einvoice-slow.log
request_slowlog_timeout = 5s
```

### Step 5: Database Setup

#### Create Production Database

```bash
# Connect to MySQL
mysql -u root -p

# Create production database
CREATE DATABASE einvoice_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create production user with limited privileges
CREATE USER 'einvoice_prod'@'localhost' IDENTIFIED BY 'strong-production-password';

# Grant privileges
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER 
  ON einvoice_prod.* TO 'einvoice_prod'@'localhost';

# For read replicas
CREATE USER 'einvoice_read'@'%' IDENTIFIED BY 'read-replica-password';
GRANT SELECT ON einvoice_prod.* TO 'einvoice_read'@'%';

# Apply privileges
FLUSH PRIVILEGES;
```

#### Import Production Schema

```bash
mysql -u einvoice_prod -p einvoice_prod < application/migrations/001_create_einvoice_tables.sql
```

#### Database Optimization

```bash
# Enable binary logging for replication
mysql -u root -p -e "SET GLOBAL binlog_format = 'ROW';"

# Optimize tables
mysql -u root -p einvoice_prod -e "OPTIMIZE TABLE einvoice_invoices, einvoice_items, einvoice_validations, einvoice_submissions;"
```

### Step 6: Application Configuration

**File: `application/config/config.php` (Production)**

```php
<?php
// Production Configuration

$config['base_url'] = 'https://yourdomain.com/';
$config['index_page'] = '';
$config['uri_protocol'] = 'REQUEST_URI';

// Encryption key (generate with: php -r "echo base64_encode(random_bytes(32));")
$config['encryption_key'] = 'your-production-encryption-key';

// Session configuration
$config['sess_driver'] = 'database';
$config['sess_table_name'] = 'ci_sessions';
$config['sess_match_ip'] = TRUE; // Stricter in production
$config['sess_time_to_update'] = 300;
$config['sess_save_path'] = 'ci_sessions';
$config['sess_secure_cookie'] = TRUE; // HTTPS only
$config['sess_httponly'] = TRUE; // No JavaScript access
$config['sess_samesite'] = 'Lax'; // CSRF protection

// Logging
$config['log_threshold'] = 1; // Errors only in production
$config['log_path'] = 'application/logs/';
$config['log_file_extension'] = 'log';

// Error handling
$config['error_views_path'] = '';
ini_set('display_errors', 0); // Never show errors to users
```

**File: `application/config/database.php` (Production)**

```php
$db['default'] = array(
    'dsn'      => '',
    'hostname' => 'db-prod.yourdomain.com', // RDS or managed database
    'username' => 'einvoice_prod',
    'password' => 'strong-production-password',
    'database' => 'einvoice_prod',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => TRUE, // Persistent connections in production
    'db_debug' => FALSE, // Never debug in production
    'cache_on' => TRUE,
    'cachedir' => 'application/cache/',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt'  => TRUE, // SSL connection to database
    'compress' => TRUE,
    'stricton' => TRUE,
    'pool_size' => 20,
    'failover' => array(
        array(
            'hostname' => 'db-failover.yourdomain.com',
            'username' => 'einvoice_prod',
            'password' => 'strong-production-password',
            'database' => 'einvoice_prod',
        ),
    ),
    'save_queries' => FALSE, // Don't log in production
);
```

### Step 7: Production Configuration

**File: `application/config/production.php`**

```php
<?php
/**
 * Production Configuration
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// Government API endpoints (LIVE)
$config['prod_mode'] = TRUE;
$config['gov_api_endpoint'] = 'https://einvoicing.gov.in/api/'; // Live endpoint
$config['gov_api_timeout'] = 60;
$config['gov_api_key'] = 'your-live-api-key';
$config['gov_api_secret'] = 'your-live-api-secret';

// Email configuration (SMTP)
$config['mail_driver'] = 'smtp';
$config['mail_protocol'] = 'tls';
$config['mail_smtp_host'] = 'smtp.yourdomain.com';
$config['mail_smtp_port'] = 587;
$config['mail_smtp_user'] = 'noreply@yourdomain.com';
$config['mail_smtp_pass'] = 'email-password';
$config['mail_from_email'] = 'noreply@yourdomain.com';
$config['mail_from_name'] = 'e-Invoice System';

// Debug settings
$config['debug_mode'] = FALSE;
$config['debug_log_sql'] = FALSE;
$config['debug_log_api_calls'] = FALSE;

// Security
$config['enable_rate_limiting'] = TRUE;
$config['rate_limit_requests'] = 1000;
$config['rate_limit_period'] = 3600; // per hour

// Caching
$config['cache_driver'] = 'redis';
$config['redis_host'] = 'localhost';
$config['redis_port'] = 6379;
$config['redis_password'] = '';
$config['cache_expiration'] = 3600;

// Backup settings
$config['backup_enabled'] = TRUE;
$config['backup_interval'] = 86400; // Daily
$config['backup_retention'] = 30; // Days

// Retry settings
$config['max_retries'] = 5;
$config['retry_delay'] = 60; // seconds, uses exponential backoff

/* End of file production.php */
```

### Step 8: Backup Configuration

**Create backup script: `/usr/local/bin/backup-einvoice.sh`**

```bash
#!/bin/bash

BACKUP_DIR="/backups/einvoice"
DATE=$(date +%Y%m%d_%H%M%S)
DB_USER="einvoice_prod"
DB_NAME="einvoice_prod"
DB_PASSWORD="strong-production-password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Application backup
tar -czf $BACKUP_DIR/app_$DATE.tar.gz /var/www/einvoice --exclude=logs --exclude=cache

# Upload to S3 (optional)
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://your-backup-bucket/

# Delete old backups (keep 30 days)
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE" >> /var/log/backup.log
```

Add to crontab:

```bash
# Backup every day at 2 AM
0 2 * * * /usr/local/bin/backup-einvoice.sh
```

### Step 9: Monitoring Setup

**Install Prometheus Node Exporter:**

```bash
# Download and install
wget https://github.com/prometheus/node_exporter/releases/download/v1.3.1/node_exporter-1.3.1.linux-amd64.tar.gz
tar xvfz node_exporter-1.3.1.linux-amd64.tar.gz
sudo mv node_exporter-1.3.1.linux-amd64/node_exporter /usr/local/bin/

# Create service
sudo tee /etc/systemd/system/node_exporter.service > /dev/null <<EOF
[Unit]
Description=Prometheus Node Exporter
After=network.target

[Service]
Type=simple
User=prometheus
ExecStart=/usr/local/bin/node_exporter

[Install]
WantedBy=multi-user.target
EOF

# Enable and start
sudo systemctl daemon-reload
sudo systemctl enable node_exporter
sudo systemctl start node_exporter
```

### Step 10: Health Check Endpoint

Create `application/controllers/Health.php`:

```php
<?php
class Health extends CI_Controller {
    public function check() {
        $this->output->set_content_type('application/json');
        
        $status = array(
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'checks' => array(
                'database' => $this->check_database(),
                'cache' => $this->check_cache(),
                'disk_space' => $this->check_disk_space(),
                'php_version' => phpversion(),
            )
        );
        
        $this->output->set_output(json_encode($status));
    }
    
    private function check_database() {
        try {
            $this->db->query("SELECT 1");
            return array('status' => 'ok', 'message' => 'Database connected');
        } catch (Exception $e) {
            return array('status' => 'error', 'message' => $e->getMessage());
        }
    }
    
    private function check_cache() {
        // Implement based on your cache driver
        return array('status' => 'ok', 'message' => 'Cache operational');
    }
    
    private function check_disk_space() {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $percent = ($free / $total) * 100;
        
        return array(
            'status' => $percent > 10 ? 'ok' : 'warning',
            'free_gb' => round($free / (1024 ** 3), 2),
            'total_gb' => round($total / (1024 ** 3), 2),
            'free_percent' => round($percent, 2)
        );
    }
}
```

---

## Production Monitoring

### Key Metrics to Monitor

```
• API Response Time (< 200ms target)
• Database Query Time (< 100ms target)
• Error Rate (< 0.1% target)
• CPU Usage (< 80% target)
• Memory Usage (< 80% target)
• Disk Usage (< 80% target)
• Request Throughput
• Successful Submissions %
• Failed Submissions Count
• Retry Attempts
```

### Alert Thresholds

```
CRITICAL:
  • API Response Time > 1000ms
  • Error Rate > 1%
  • Database Down
  • Disk Usage > 90%

WARNING:
  • API Response Time > 500ms
  • Error Rate > 0.5%
  • CPU Usage > 85%
  • Memory Usage > 85%
```

---

## Maintenance Schedule

### Daily
- [ ] Check error logs
- [ ] Monitor API performance
- [ ] Verify database replication
- [ ] Check backup completion

### Weekly
- [ ] Review security logs
- [ ] Analyze performance metrics
- [ ] Check disk usage
- [ ] Review failed submissions

### Monthly
- [ ] Full security audit
- [ ] Performance analysis
- [ ] Database optimization
- [ ] Disaster recovery drill
- [ ] Certificate renewal check

### Quarterly
- [ ] Full system audit
- [ ] Load testing
- [ ] Security penetration testing
- [ ] Capacity planning
- [ ] Compliance review

---

## Disaster Recovery Plan

### Recovery Time Objective (RTO)
- **Target:** 1 hour for full recovery
- **Current:** Database failover < 30 seconds

### Recovery Point Objective (RPO)
- **Target:** 1 hour data loss acceptable
- **Current:** Hourly backups

### Backup Locations
```
Primary:   /backups/einvoice/
Secondary: S3 bucket (your-backup-bucket)
Tertiary:  Offsite storage (monthly)
```

### Restore Procedure

```bash
# 1. Stop application
sudo systemctl stop php-fpm
sudo systemctl stop nginx

# 2. Restore database
mysql -u root -p < /backups/einvoice/db_YYYYMMDD_HHMMSS.sql.gz

# 3. Restore application files
tar -xzf /backups/einvoice/app_YYYYMMDD_HHMMSS.tar.gz

# 4. Verify integrity
php -r "include 'index.php'; echo 'System OK';"

# 5. Start services
sudo systemctl start php-fpm
sudo systemctl start nginx

# 6. Verify health
curl https://yourdomain.com/api/v1/health
```

---

## Production Deployment Checklist

### Pre-Deployment (48 hours before)
- [ ] Code review completed
- [ ] All tests passing
- [ ] Security audit completed
- [ ] Performance testing done
- [ ] Backups verified
- [ ] Rollback plan documented
- [ ] Team notified
- [ ] Maintenance window scheduled

### Deployment Day
- [ ] Database backups taken
- [ ] Application backed up
- [ ] Deploy to staging first
- [ ] Smoke tests on staging
- [ ] Deploy to production
- [ ] Verify all endpoints
- [ ] Monitor error logs
- [ ] Check API response times
- [ ] Verify database integrity

### Post-Deployment (24 hours after)
- [ ] Monitor performance metrics
- [ ] Check error rates
- [ ] Verify all features working
- [ ] Monitor API usage
- [ ] Verify backups completed
- [ ] Update documentation
- [ ] Prepare rollback plan
- [ ] Notify stakeholders

---

## Security Hardening

### File Permissions

```bash
# Set correct permissions
chmod 755 /var/www/einvoice
chmod 644 /var/www/einvoice/index.php
chmod 755 /var/www/einvoice/application
chmod 755 /var/www/einvoice/application/logs
chmod 755 /var/www/einvoice/application/cache
chmod 644 /var/www/einvoice/application/config/config.php

# Restrict sensitive files
chmod 600 /var/www/einvoice/application/config/database.php
chmod 600 /var/www/einvoice/application/config/production.php
```

### Firewall Rules

```bash
# Allow only specific ports
sudo ufw allow from any to any port 22 proto tcp  # SSH
sudo ufw allow from any to any port 80 proto tcp  # HTTP
sudo ufw allow from any to any port 443 proto tcp # HTTPS

# Deny all other incoming traffic
sudo ufw default deny incoming
sudo ufw default allow outgoing
```

### Environment Variables

Create `.env` file (never commit to version control):

```bash
CI_ENV=production
ENCRYPTION_KEY=your-production-encryption-key
DB_HOST=db-prod.yourdomain.com
DB_USER=einvoice_prod
DB_PASS=strong-password
DB_NAME=einvoice_prod
API_KEY=your-live-api-key
API_SECRET=your-live-api-secret
```

Load in `index.php`:

```php
// Load environment variables
if (file_exists('.env')) {
    $env = parse_ini_file('.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
```

---

## Support & Documentation

- **Production Runbook:** Keep updated with deployment procedures
- **SLA Documentation:** Define uptime and support guarantees
- **Incident Response Plan:** Documented procedures for outages
- **On-Call Rotation:** 24/7 support team schedule

---

**Last Updated:** August 29, 2026  
**Version:** 1.0.0
