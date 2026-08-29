# Production Environment Configuration
# For live business-critical deployments only

## Pre-Deployment Checklist

### Infrastructure
- [ ] DNS records configured and pointing to load balancer
- [ ] SSL/TLS certificate obtained (Let's Encrypt or commercial)
- [ ] WAF rules configured (ModSecurity or AWS WAF)
- [ ] Database backups configured with 30-day retention
- [ ] Database replication to standby region set up
- [ ] Automatic failover tested and working
- [ ] VPC network security groups configured
- [ ] Database encryption at rest enabled
- [ ] Secrets stored in AWS Secrets Manager
- [ ] CloudFront CDN configured for static assets

### Application
- [ ] All environment variables set correctly
- [ ] Debug mode disabled (DEBUG_MODE=FALSE)
- [ ] Error reporting configured (Sentry or similar)
- [ ] Logging aggregation setup (ELK Stack or CloudWatch)
- [ ] Rate limiting enabled (1000 req/min per IP)
- [ ] CORS restricted to authorized domains
- [ ] Session cookies set to SECURE and HTTPONLY
- [ ] JWT tokens have short expiration (15 minutes)
- [ ] All dependencies updated to latest stable versions
- [ ] Asset compilation and minification completed

### Monitoring & Alerting
- [ ] Prometheus/Grafana stack deployed
- [ ] Alert thresholds configured
- [ ] On-call schedule established
- [ ] PagerDuty integration configured
- [ ] Incident response runbook documented
- [ ] Escalation policies defined
- [ ] Log retention configured (90+ days)
- [ ] Performance baselines established
- [ ] Health check endpoints responding
- [ ] Load balancer health checks passing

### Security & Compliance
- [ ] Security audit completed
- [ ] Penetration testing passed
- [ ] OWASP top 10 verified
- [ ] SQL injection protection confirmed
- [ ] XSS protection enabled
- [ ] CSRF tokens implemented
- [ ] Rate limiting tested
- [ ] DDoS protection enabled
- [ ] Regular security updates scheduled
- [ ] Data encryption in transit and at rest

### Operational Readiness
- [ ] Support team trained on monitoring
- [ ] Runbooks created for common issues
- [ ] Disaster recovery plan tested
- [ ] Backup/restore procedures verified
- [ ] Database replication lag monitoring
- [ ] Automated scaling policies configured
- [ ] Performance load testing completed
- [ ] Documentation finalized
- [ ] Approval from stakeholders obtained
- [ ] Cutover plan reviewed with team

## Environment Variables

```env
# Application
CI_ENV=production
APP_MODE=PRODUCTION
DEBUG_MODE=FALSE
LOG_LEVEL=WARNING
LOG_PATH=/var/log/einvoice
CACHE_PATH=/var/cache/einvoice

# Database
DB_DRIVER=mysqli
DB_HOST=einvoice-prod.c9akciq32.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_USER=admin
DB_PASS=${AWS_SECRETS_DATABASE_PASSWORD}
DB_NAME=einvoice_prod
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_DEBUG=FALSE
DB_POOL_SIZE=50
DB_POOL_MIN_IDLE=10

# Cache
CACHE_DRIVER=redis
CACHE_TTL=86400
REDIS_HOST=10.0.1.50
REDIS_PORT=6379
REDIS_PASSWORD=${AWS_SECRETS_REDIS_PASSWORD}
REDIS_DB=0
REDIS_CLUSTER=true
REDIS_TIMEOUT=5

# Session
SESSION_DRIVER=database
SESSION_TIMEOUT=1800
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=strict

# Government API
GOV_API_MODE=production
GOV_API_ENDPOINT=https://einvoicing.gov.in/api/
GOV_API_TIMEOUT=60
GOV_API_KEY=${AWS_SECRETS_GOV_API_KEY}
GOV_API_SECRET=${AWS_SECRETS_GOV_API_SECRET}
GOV_API_DEBUG=FALSE
GOV_API_RETRY_MAX=3
GOV_API_RETRY_BACKOFF=exponential

# Email
MAIL_DRIVER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USER=${AWS_SECRETS_SMTP_USER}
MAIL_PASS=${AWS_SECRETS_SMTP_PASS}
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME="e-Invoice System"
MAIL_QUEUE_DRIVER=database

# Security
ENCRYPTION_KEY=${AWS_SECRETS_ENCRYPTION_KEY}
JWT_SECRET=${AWS_SECRETS_JWT_SECRET}
JWT_ALGORITHM=HS256
JWT_EXPIRATION=900  # 15 minutes
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
CORS_ALLOWED_METHODS=GET,POST,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization
CORS_MAX_AGE=86400

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=1000
RATE_LIMIT_WINDOW=60  # seconds
RATE_LIMIT_WHITELIST=

# Monitoring
SENTRY_DSN=${AWS_SECRETS_SENTRY_DSN}
MONITORING_ENABLED=true
MONITORING_SAMPLE_RATE=0.1  # 10% of requests
DATADOG_API_KEY=${AWS_SECRETS_DATADOG_API_KEY}
DATADOG_APP_KEY=${AWS_SECRETS_DATADOG_APP_KEY}

# Application URLs
APP_URL=https://yourdomain.com
DOMAIN_NAME=yourdomain.com
API_BASE_URL=https://yourdomain.com/api/v1

# Features
FEATURE_EMAIL_NOTIFICATIONS=true
FEATURE_WEBHOOK_SUPPORT=true
FEATURE_BULK_UPLOAD=true
FEATURE_API_ANALYTICS=true
```

## System Configuration Files

### Nginx Configuration

```nginx
# /etc/nginx/sites-available/einvoice-prod

upstream php_backend {
    least_conn;
    server 127.0.0.1:9000 max_fails=3 fail_timeout=30s;
    server 127.0.0.1:9001 max_fails=3 fail_timeout=30s;
    keepalive 32;
}

server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    # SSL configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Logging
    access_log /var/log/nginx/einvoice-access.log json_combined buffer=32k flush=5s;
    error_log /var/log/nginx/einvoice-error.log warn;

    # Compression
    gzip on;
    gzip_types text/plain text/css text/javascript application/json application/javascript;
    gzip_min_length 1000;
    gzip_level 6;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api_limit:10m rate=1000r/m;
    limit_req zone=api_limit burst=2000 nodelay;

    root /var/www/einvoice/public;
    index index.php;

    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|webp)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # API endpoints
    location /api/v1/ {
        try_files $uri $uri/ /index.php?$query_string;
        fastcgi_pass php_backend;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        include fastcgi_params;
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;
    }

    # PHP handler
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass php_backend;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

### PHP-FPM Pool Configuration

```ini
# /etc/php/8.0/fpm/pool.d/einvoice.conf

[einvoice]
user = www-data
group = www-data

listen = 127.0.0.1:9000
listen_backlog = -1

pm = dynamic
pm.max_children = 16
pm.start_servers = 8
pm.min_spare_servers = 4
pm.max_spare_servers = 12
pm.max_requests = 500
pm.status_path = /fpm-status

; Performance
request_slowlog_timeout = 2s
slowlog = /var/log/php-fpm/slow.log
request_terminate_timeout = 60s

; Security
security.limit_extensions = .php
catch_workers_output = yes
decorate_workers_output = no

; Environment
env[CI_ENV] = production
env[APP_MODE] = PRODUCTION
```

### MySQL Configuration

```ini
# /etc/mysql/mysql.conf.d/einvoice.cnf

[mysqld]
# Performance
max_connections = 1000
max_allowed_packet = 256M
thread_stack = 256K
sort_buffer_size = 2M
bulk_insert_buffer_size = 16M

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2

# Replication
server-id = 1
log_bin = /var/log/mysql/mysql-bin.log
binlog_format = ROW
binlog_retention_days = 30
relay-log = /var/log/mysql/mysql-relay-bin
relay-log-index = /var/log/mysql/mysql-relay-bin.index

# Backup
skip-external-locking
innodb_autoinc_lock_mode = 2

# Security
skip-symbolic-links
symbolic-links = 0
```

## Deployment Steps

### 1. Pre-Deployment
```bash
# SSH into production server
ssh -i ~/.ssh/production.pem ubuntu@yourdomain.com

# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Ensure backup exists
sudo /usr/local/bin/backup-einvoice.sh
```

### 2. Deploy Code
```bash
cd /var/www/einvoice

# Pull latest code
sudo git fetch origin geekswebs-svg-ci3-einvoice-api-types
sudo git checkout geekswebs-svg-ci3-einvoice-api-types
sudo git reset --hard origin/geekswebs-svg-ci3-einvoice-api-types

# Set ownership
sudo chown -R www-data:www-data .
```

### 3. Install Dependencies
```bash
# Copy production env
sudo cp .env.production .env

# Install PHP dependencies
sudo composer install --no-dev --optimize-autoloader
```

### 4. Database Migration
```bash
# Run migrations
php index.php migrate

# Verify migration
mysql -u admin -p einvoice_prod -e "SHOW TABLES;"
```

### 5. Cache Warm-up
```bash
# Clear old cache
sudo redis-cli FLUSHALL

# Warm up cache
php scripts/warmup-cache.php
```

### 6. Service Restart
```bash
# Restart services (zero-downtime with load balancer)
sudo systemctl restart php8.0-fpm
sudo systemctl restart nginx

# Verify services
sudo systemctl status php8.0-fpm
sudo systemctl status nginx
```

### 7. Health Check
```bash
# Test API
curl -I https://yourdomain.com/api/v1/health

# Monitor logs
tail -f /var/log/nginx/einvoice-access.log
tail -f /var/log/php-fpm/slow.log
```

## Rollback Procedure

If issues detected after deployment:

```bash
cd /var/www/einvoice

# Revert to previous commit
sudo git reset --hard HEAD~1

# Clear cache
sudo redis-cli FLUSHALL

# Restart services
sudo systemctl restart php8.0-fpm
sudo systemctl restart nginx

# Verify rollback
curl https://yourdomain.com/api/v1/health
```

## Monitoring & Alerting

### Prometheus Metrics
```yaml
# /etc/prometheus/prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

alerting:
  alertmanagers:
    - static_configs:
        - targets: ['localhost:9093']

rule_files:
  - /etc/prometheus/rules/*.yml

scrape_configs:
  - job_name: 'php-fpm'
    static_configs:
      - targets: ['localhost:9000']
    metrics_path: '/fpm-status'

  - job_name: 'nginx'
    static_configs:
      - targets: ['localhost:80']
    metrics_path: '/nginx-status'

  - job_name: 'mysql'
    static_configs:
      - targets: ['localhost:3306']
    metrics_path: '/metrics'
```

### Alert Rules
```yaml
# /etc/prometheus/rules/alerts.yml
groups:
  - name: application
    rules:
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
        for: 5m
        annotations:
          summary: "High error rate detected"

      - alert: HighLatency
        expr: histogram_quantile(0.99, http_request_duration_seconds) > 2
        for: 5m
        annotations:
          summary: "High latency detected (p99 > 2s)"

      - alert: DatabaseConnectionPoolExhausted
        expr: mysql_global_status_threads_connected / mysql_global_variables_max_connections > 0.8
        for: 5m
        annotations:
          summary: "Database connection pool usage > 80%"

      - alert: RedisCacheMemoryHigh
        expr: redis_memory_used_bytes / redis_memory_max_bytes > 0.85
        for: 5m
        annotations:
          summary: "Redis memory usage > 85%"
```

## Backup & Recovery

### Automated Daily Backup
```bash
# /usr/local/bin/backup-einvoice.sh
#!/bin/bash
BACKUP_DIR="/backups/einvoice"
DATE=$(date +%Y%m%d_%H%M%S)
DB_PASS=$(grep DB_PASS /var/www/einvoice/.env | cut -d= -f2)

# Backup database
mysqldump -u admin -p$DB_PASS einvoice_prod | \
  gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Upload to S3
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://einvoice-backups/

# Cleanup old local backups (keep 7 days)
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
```

### Point-in-Time Recovery
```bash
# 1. Stop application
sudo systemctl stop php8.0-fpm

# 2. Restore from backup
BACKUP_FILE="/backups/einvoice/db_20240115_120000.sql.gz"
gunzip < $BACKUP_FILE | mysql -u admin -p einvoice_prod

# 3. Start application
sudo systemctl start php8.0-fpm
```

## Performance Optimization

### Database Query Optimization
```sql
-- Add missing indexes
ALTER TABLE einvoice_invoices ADD INDEX idx_status_seller (status, seller_id);
ALTER TABLE einvoice_invoices ADD INDEX idx_submitted_at (submitted_at);
ALTER TABLE einvoice_items ADD INDEX idx_invoice_id (invoice_id);

-- Analyze tables
ANALYZE TABLE einvoice_invoices;
ANALYZE TABLE einvoice_items;
ANALYZE TABLE einvoice_submissions;
```

### Cache Strategy
```php
// Cache TTLs (in seconds)
$cache_config = [
    'party_data' => 86400,        // 24 hours
    'invoice_details' => 3600,    // 1 hour
    'api_responses' => 300,       // 5 minutes
    'validation_rules' => 604800, // 7 days
];
```

## On-Call Responsibilities

- [ ] Monitor application health continuously
- [ ] Respond to alerts within 15 minutes
- [ ] Investigate and resolve critical issues
- [ ] Document incident timeline
- [ ] Escalate infrastructure issues to DevOps
- [ ] Communicate status to stakeholders
- [ ] Perform post-incident analysis within 24 hours

## Maintenance Windows

- **Scheduled:** Every 2nd Sunday, 2:00-4:00 AM UTC
- **Emergency:** As needed (with stakeholder approval)
- **Communication:** Status page update + email notification

---

**Last Updated:** 2024-01-15
**Version:** 1.0
**Next Review:** Quarterly
