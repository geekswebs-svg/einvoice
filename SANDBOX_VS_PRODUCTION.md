# Sandbox vs Production - Environment Comparison

## Quick Reference Table

| Aspect | Sandbox | Production |
|--------|---------|------------|
| **Purpose** | Testing & Development | Live Operations |
| **Data** | Test/Sample Data | Real Customer Data |
| **Government API** | Mock Endpoints | Live Endpoints |
| **SSL/TLS** | Self-signed (optional) | Let's Encrypt/Commercial |
| **Database** | Local/Dev MySQL | RDS/Managed DB |
| **Backups** | Hourly | Every 6 hours + Real-time replication |
| **Monitoring** | Basic | Comprehensive (Prometheus, Grafana) |
| **Scaling** | Single Server | Load Balanced (Multiple Servers) |
| **Uptime SLA** | N/A | 99.9% |
| **Response Time** | < 500ms | < 200ms |
| **Users** | Developers/QA | All End Users |
| **Data Retention** | 7 days | 7 years |
| **Audit Logging** | Basic | Comprehensive |
| **Cost** | Low (~$10-50/month) | High (~$500-2000/month) |

---

## Environment Specifications

### Sandbox Specifications

**Hardware:**
```
CPU Cores:           2-4
RAM:                 2-4 GB
Storage:             20-30 GB SSD
Network:             Standard ISP
Redundancy:          None required
```

**Software:**
```
OS:                  Ubuntu 18.04+, CentOS 7+, or Windows
Web Server:          Apache 2.4+ OR Nginx 1.14+
PHP:                 7.2, 7.3, 7.4, 8.0+
Database:            MySQL 5.7+ or MariaDB 10.3+
Caching:             File-based or APCu
```

**Access:**
```
URL:                 http://localhost:8000
                     http://sandbox.yourdomain.local
Authentication:      Basic or Keycloak
SSL:                 Not required (Optional self-signed)
```

**Data Volume:**
```
Expected Records:    < 10,000 invoices
Database Size:       < 500 MB
Daily Traffic:       < 1,000 requests
Log Retention:       7 days
```

---

### Production Specifications

**Hardware:**
```
CPU Cores:           8+ cores
RAM:                 8-16 GB
Storage:             100-500 GB SSD
Network:             Dedicated 10+ Mbps
Redundancy:          Primary + Failover
Content Delivery:    CDN optional
```

**Software:**
```
OS:                  Ubuntu 18.04 LTS+ or CentOS 7+
Web Server:          Nginx 1.19+ (recommended)
PHP-FPM:             7.4 or 8.0+
Database:            MySQL 8.0+ with replication
                     OR AWS RDS / Google Cloud SQL
Caching:             Redis 6.0+
Message Queue:       Optional (RabbitMQ/Kafka)
Search Engine:       Optional (Elasticsearch)
```

**Access:**
```
URL:                 https://yourdomain.com
                     https://api.yourdomain.com
Authentication:      OAuth2, JWT, or API Keys
SSL/TLS:             Let's Encrypt (auto-renewal)
                     OR Commercial Certificate
Certificate:         Valid, signed, trusted
```

**Data Volume:**
```
Expected Records:    > 1,000,000 invoices
Database Size:       > 10 GB
Daily Traffic:       > 10,000 requests
Peak Capacity:       1,000+ requests/sec
Log Retention:       90+ days (Elasticsearch)
```

---

## Configuration Comparison

### Environment Variables

**Sandbox (.env.sandbox):**
```ini
CI_ENV=development
APP_MODE=SANDBOX
DEBUG_MODE=TRUE
LOG_LEVEL=DEBUG

# Database
DB_HOST=localhost
DB_USER=sandbox_user
DB_PASS=sandbox_password
DB_NAME=einvoice_sandbox

# Government API (Mock)
GOV_API_MODE=mock
GOV_API_ENDPOINT=http://localhost:9000/mock-api/
GOV_API_TIMEOUT=30

# Email
MAIL_DRIVER=log
MAIL_LOG_PATH=/logs/emails/

# Cache
CACHE_DRIVER=file
CACHE_PREFIX=sandbox_

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

**Production (.env.production):**
```ini
CI_ENV=production
APP_MODE=PRODUCTION
DEBUG_MODE=FALSE
LOG_LEVEL=WARNING

# Database (RDS/Managed)
DB_HOST=db-prod.yourdomain.com
DB_USER=einvoice_prod
DB_PASS=strong-random-password-32-chars
DB_NAME=einvoice_prod
DB_POOL_SIZE=20
DB_CONNECTION_TIMEOUT=30
DB_READ_REPLICA=db-read.yourdomain.com

# Government API (Live)
GOV_API_MODE=production
GOV_API_ENDPOINT=https://einvoicing.gov.in/api/
GOV_API_TIMEOUT=60
GOV_API_KEY=your-live-key
GOV_API_SECRET=your-live-secret

# Email (SMTP)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=email-password

# Cache (Redis)
CACHE_DRIVER=redis
REDIS_HOST=cache-prod.yourdomain.com
REDIS_PORT=6379
REDIS_PASSWORD=redis-password
CACHE_PREFIX=prod_

# Session (Database)
SESSION_DRIVER=database
SESSION_LIFETIME=3600
SESSION_SECURE_COOKIE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

# Security
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=1000
RATE_LIMIT_PERIOD=3600
IP_WHITELIST=true
```

---

## Database Configuration

### Sandbox Database Configuration

```sql
-- Sandbox Database Settings
SET max_connections = 100;
SET max_allowed_packet = 16M;
SET query_cache_size = 0;
SET log_bin = OFF;

-- Backup Settings
SET binlog_retention_days = 1;

-- Performance Settings
SET slow_query_log = ON;
SET long_query_time = 2;
```

**my.cnf (Sandbox):**
```ini
[mysqld]
max_connections = 100
max_allowed_packet = 16M
query_cache_size = 0
log_queries_not_using_indexes = ON
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

---

### Production Database Configuration

```sql
-- Production Database Settings
SET max_connections = 500;
SET max_allowed_packet = 64M;
SET query_cache_size = 0; -- Disable for better performance
SET log_bin = ON;

-- Replication Settings
SET binlog_format = 'ROW';
SET server_id = 1; -- Unique for each server

-- Backup Settings
SET binlog_retention_days = 30;
SET backup_compression = ON;

-- Performance Settings
SET slow_query_log = ON;
SET long_query_time = 1;
SET log_queries_not_using_indexes = OFF; -- Too verbose in prod
```

**my.cnf (Production):**
```ini
[mysqld]
# Connection Pool
max_connections = 500
max_user_connections = 100
max_allowed_packet = 64M

# Query Optimization
query_cache_size = 0
query_cache_type = 0
innodb_buffer_pool_size = 6G
innodb_log_file_size = 512M
tmp_table_size = 256M
max_heap_table_size = 256M

# Replication
log_bin = mysql-bin
binlog_format = ROW
server_id = 1
relay-log = mysql-relay-bin
relay-log-index = mysql-relay-bin.index

# Backup
binlog_retention_days = 30
backup_compression = ON

# Performance
slow_query_log = ON
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 1

# Security
ssl_ca = /etc/mysql/ssl/ca.pem
ssl_cert = /etc/mysql/ssl/server-cert.pem
ssl_key = /etc/mysql/ssl/server-key.pem
require_secure_transport = ON
```

---

## Logging Configuration

### Sandbox Logging

**application/config/logging.php (Sandbox):**
```php
$config['log_threshold'] = 4; // All messages
$config['log_path'] = 'application/logs/';
$config['log_file_extension'] = '.log';
$config['log_file_permissions'] = 0644;

// Log formats
$config['log_format'] = '{date} - {level}: {message}';
$config['log_date_format'] = 'Y-m-d H:i:s';

// Log levels
$config['log_levels'] = array(
    'error' => 1,
    'debug' => 3,
    'info' => 3,
    'all' => 4
);

// File retention
$config['log_retention_days'] = 7;
$config['log_max_file_size'] = '10M';
$config['log_max_files'] = 10;
```

---

### Production Logging (ELK Stack)

**application/config/logging.php (Production):**
```php
$config['log_threshold'] = 1; // Errors only
$config['log_path'] = 'application/logs/';

// Use Elasticsearch for centralized logging
$config['elasticsearch_host'] = 'elasticsearch.yourdomain.com';
$config['elasticsearch_port'] = 9200;
$config['elasticsearch_index'] = 'einvoice-logs';

// Log levels
$config['log_levels'] = array(
    'error' => 1,
    'debug' => 0,
    'info' => 0,
);

// Critical alerts
$config['alert_on_errors'] = true;
$config['alert_email'] = 'ops@yourdomain.com';
$config['alert_slack_webhook'] = 'https://hooks.slack.com/services/...';

// File retention (logs shipped to Elasticsearch)
$config['log_retention_days'] = 7;
$config['log_max_file_size'] = '100M';
```

---

## API Response Times

### Sandbox Targets
```
Average Response Time:     < 500ms
95th Percentile:          < 1000ms
99th Percentile:          < 2000ms
Database Query Time:       < 200ms
Cache Hit Rate:            > 50%
```

### Production Targets
```
Average Response Time:     < 200ms
95th Percentile:          < 500ms
99th Percentile:          < 1000ms
Database Query Time:       < 100ms
Cache Hit Rate:            > 80%
P99 Latency:              < 500ms
```

---

## Deployment Process

### Sandbox Deployment
```
1. Push code to feature branch
2. Run automated tests
3. Deploy to sandbox (auto)
4. Run integration tests
5. Manual testing by QA
6. Ready for review
```

**Time:** 15 minutes  
**Risk:** Low  
**Rollback:** Automatic

---

### Production Deployment
```
1. Code review (2 approvals required)
2. Run full test suite
3. Deploy to staging
4. Smoke tests on staging
5. Performance testing
6. Deploy to production (blue-green)
7. Health checks
8. Monitor for 24 hours
9. Rollback plan ready
```

**Time:** 2 hours  
**Risk:** High  
**Rollback:** Manual (< 10 minutes)

---

## Security Comparison

### Sandbox Security
- [x] Input validation
- [x] Basic authentication
- [x] SQLi prevention
- [x] Basic error handling
- [ ] Rate limiting
- [ ] WAF
- [ ] CORS restrictions
- [ ] Encryption

### Production Security
- [x] Input validation
- [x] OAuth2/JWT authentication
- [x] SQLi prevention
- [x] Comprehensive error handling
- [x] Rate limiting (1000 req/hr)
- [x] WAF (CloudFlare/ModSecurity)
- [x] CORS restrictions
- [x] All data encrypted (TLS + DB encryption)
- [x] API key rotation
- [x] IP whitelisting
- [x] Audit logging
- [x] Intrusion detection

---

## Cost Comparison

### Sandbox Monthly Cost
```
Compute:             $10-20  (t2.micro/small)
Database:            $10-20  (local MySQL)
Storage:             $5-10   (S3 backup)
Monitoring:          $0      (built-in)
Total:               ~$25-50/month
```

### Production Monthly Cost
```
Compute:             $100-300 (2x t2.large)
Load Balancer:       $20-50   (ALB)
Database:            $200-500 (RDS MySQL)
Cache:               $50-100  (ElastiCache Redis)
Storage:             $50-100  (S3 backups + logs)
Monitoring:          $50-100  (Prometheus, Grafana)
CDN:                 $20-50   (CloudFlare)
Misc:                $100-200 (Other services)
Total:               ~$600-1,400/month
```

---

## Monitoring & Alerting

### Sandbox Monitoring
```
Manual log review
Basic health checks
No automated alerts
Optional APM
```

### Production Monitoring
```
24/7 Automated monitoring
Real-time alerts
Metrics collection (Prometheus)
Visualization (Grafana)
APM (New Relic/DataDog)
Error tracking (Sentry)
Incident response (PagerDuty)
```

---

## Testing Strategy

### Sandbox Testing
- Unit tests (developers)
- Integration tests
- API tests (Postman/cURL)
- Manual QA testing
- Security testing (OWASP)

### Production Pre-Deployment
- Full automated test suite
- Performance testing (k6/JMeter)
- Security scanning (SAST/DAST)
- Load testing (1000+ req/sec)
- Penetration testing (quarterly)
- Disaster recovery drill

---

## Support & SLA

### Sandbox Support
```
Support Hours:    Business hours (9-5 weekdays)
Response Time:    2-4 hours
Uptime SLA:       None
Service Level:    Best effort
```

### Production Support
```
Support Hours:    24/7/365
Response Time:    
  - Critical:     15 minutes
  - High:         1 hour
  - Medium:       4 hours
  - Low:          1 business day
Uptime SLA:       99.9% (8.75 hrs downtime/month)
Service Level:    Guaranteed
```

---

## Checklist for Moving from Sandbox to Production

**Pre-Production Checklist:**
- [ ] All tests passing
- [ ] Code review completed
- [ ] Security audit passed
- [ ] Performance baseline established
- [ ] Disaster recovery plan documented
- [ ] Monitoring configured
- [ ] Logging aggregation set up
- [ ] Backups verified
- [ ] Team trained
- [ ] Documentation updated
- [ ] SLA agreements signed
- [ ] Support team briefed

---

**Last Updated:** August 29, 2026  
**Version:** 1.0.0
