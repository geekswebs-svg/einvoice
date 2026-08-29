# Sandbox vs Production Configuration Reference

## Quick Comparison Table

| Aspect | Sandbox | Production |
|--------|---------|-----------|
| **Environment** | Development/Testing | Live/User-facing |
| **Debug Mode** | ON (verbose logging) | OFF (minimal logging) |
| **Database** | Local MySQL | AWS RDS / Managed DB |
| **Cache** | File-based | Redis cluster |
| **API Mode** | Mock (localhost:9000) | Live government API |
| **Domain** | localhost:8000 | yourdomain.com |
| **SSL/TLS** | Self-signed or none | Let's Encrypt / Commercial |
| **Web Server** | PHP Built-in | Nginx + PHP-FPM |
| **Log Level** | DEBUG | WARNING/ERROR |
| **Performance** | Single thread | Multi-worker (8-16) |
| **Uptime SLA** | None | 99.9% |
| **Monthly Cost** | $25-50 | $600-1400 |
| **Backup** | Manual/Daily | Hourly + Replication |
| **Monitoring** | Basic logs | Full stack (Prometheus/Grafana) |
| **Email** | File logging | SMTP with templates |
| **Rate Limiting** | Disabled | 1000 req/min per IP |
| **WAF** | None | Enabled (ModSecurity) |

---

## Database Configuration

### Sandbox (Local Development)
```
Host: localhost
Port: 3306
User: sandbox_user
Password: sandbox_password
Database: einvoice_sandbox
Charset: utf8mb4
Collation: utf8mb4_unicode_ci
Max Connections: 10
Query Cache: Enabled
```

### Production (AWS RDS)
```
Host: einvoice-prod.c9akciq32.us-east-1.rds.amazonaws.com
Port: 3306
User: admin
Password: [SecureAWSSecretsManager]
Database: einvoice_prod
Charset: utf8mb4
Collation: utf8mb4_unicode_ci
Max Connections: 1000
Multi-AZ: Enabled
Backup Retention: 30 days
Enhanced Monitoring: Enabled
```

---

## Caching Configuration

### Sandbox (File-based)
```php
$config['cache_driver'] = 'file';
$config['cache_path'] = './application/cache/';
$config['cache_ttl'] = 3600; // 1 hour
```

### Production (Redis Cluster)
```php
$config['cache_driver'] = 'redis';
$config['redis_host'] = '10.0.1.50';
$config['redis_port'] = 6379;
$config['redis_password'] = [from AWS SecretsManager];
$config['cache_ttl'] = 86400; // 24 hours
$config['cache_key_prefix'] = 'einvoice_prod_';
```

---

## API Endpoints Configuration

### Sandbox (Mock API)
```
Government API: http://localhost:9000/mock-api/
Mock Responses: Immediate (50ms)
IRN Format: DEV-{timestamp}-{hash}
Mock Data: Available for all scenarios
Rate Limiting: None
```

### Production (Live API)
```
Government API: https://einvoicing.gov.in/api/
Live Responses: Varies (200-5000ms)
IRN Format: {official-government-format}
Credentials: OAuth2 from government portal
Rate Limiting: 1000 req/min per org
Timeout: 60 seconds
Retry Strategy: Exponential backoff (3 attempts)
```

---

## Security Configuration

### Sandbox
```
HTTPS: Optional (self-signed OK)
CORS: Allow all origins
Rate Limiting: Disabled
WAF: Disabled
Authentication: Basic for testing
Session Timeout: 24 hours
IP Whitelist: None
```

### Production
```
HTTPS: Required (Let's Encrypt)
CORS: Restricted to known domains
Rate Limiting: 1000 req/min per IP
WAF: ModSecurity with OWASP rules
Authentication: OAuth2 + API Keys
Session Timeout: 30 minutes
IP Whitelist: Optional (configurable)
DDoS Protection: CloudFlare or AWS Shield
```

---

## Email Configuration

### Sandbox
```
Driver: Log File
Log Path: application/logs/emails/
Format: Plain text in log
Delivery Time: Instant (no mail server)
Use Case: Development testing
Example Log: [2024-01-15 10:30:45] Invoice submitted to buyer@test.com
```

### Production
```
Driver: SMTP
Host: smtp.yourdomain.com
Port: 587
Authentication: TLS
From Address: noreply@yourdomain.com
Templates: HTML with branding
Delivery Time: 1-5 seconds
Use Case: Customer notifications
Template Variables: {invoice_number}, {buyer_name}, {due_date}
```

---

## Logging Configuration

### Sandbox
```
Level: DEBUG
Log Path: application/logs/
File Retention: 7 days
Log Format: Verbose with full context
Output: Console + File
Query Logging: Enabled
Stack Traces: Full (with file paths)
```

### Production
```
Level: WARNING/ERROR only
Log Path: /var/log/einvoice/
File Retention: 90 days
Log Format: JSON (machine-readable)
Output: Syslog + CloudWatch
Query Logging: Disabled (performance)
Stack Traces: Minimal (no sensitive info)
Centralized Logging: ELK Stack / CloudWatch
```

---

## Performance Tuning

### Sandbox
```
PHP Workers: 1-2
Memory Limit: 512MB
Max Execution Time: 300 seconds
Opcache: Disabled (faster dev reloads)
Query Time Limit: 30 seconds
```

### Production
```
PHP-FPM Workers: 16 (4 × CPU cores)
Memory Limit: 256MB per worker (4GB total)
Max Execution Time: 60 seconds
Opcache: Enabled (99% hit rate target)
Query Time Limit: 5 seconds
Connection Pool: Enabled (persistent connections)
Slow Query Log Threshold: 2 seconds
```

---

## Monitoring & Alerting

### Sandbox
```
Monitoring: Basic system stats
Tools: Top, df, vmstat
Alert Threshold: Manual checks only
Uptime Tracking: Not required
Error Tracking: Sentry (optional)
```

### Production
```
Monitoring: Full observability stack
Tools: Prometheus + Grafana + AlertManager
Metrics Collected:
  - Request rate, latency, error rate
  - Database connections, query time
  - Cache hit/miss ratio
  - Memory, CPU, disk usage
  - Invoice processing success rate
  
Alert Thresholds:
  - CPU > 80% for 5 minutes
  - Memory > 90%
  - Error rate > 1%
  - Response time > 2 seconds (p99)
  - Database replication lag > 10 seconds
  - Disk usage > 85%
  
On-Call: 24/7 rotation
Escalation: 15min (warning), 30min (critical)
```

---

## Backup & Disaster Recovery

### Sandbox
```
Backup Frequency: Daily (manual or cron)
Backup Method: mysqldump via shell script
Retention Period: 7 days
Backup Location: Local filesystem
Recovery Time Objective (RTO): 1 hour
Recovery Point Objective (RPO): 24 hours
```

### Production
```
Backup Frequency: Hourly (automated)
Backup Method: AWS RDS automated backups + replication
Retention Period: 30 days
Backup Location: AWS S3 (cross-region)
Replication: Real-time to standby region
RTO: 15 minutes
RPO: 5 minutes

Disaster Recovery Plan:
1. Detection: Automated health check failure
2. Notification: PagerDuty alert to on-call team
3. Failover: Automatic to standby (2-5 minutes)
4. Validation: Health checks confirm service
5. Post-Incident: Root cause analysis within 24 hours
```

---

## Cost Comparison (Monthly)

### Sandbox (Development)
```
Server: $10-20 (small t3.micro on AWS)
Database: $0 (local MySQL) or $5-10 (small RDS)
Cache: $0 (file-based)
Domain/SSL: $0 (localhost) or $12/year (local cert)
Traffic: $0 (internal testing)
Monitoring: $0 (basic)
Total: $25-50/month
```

### Production (Live)
```
Compute: $100-200/month (t3.large, 2-4 instances)
Database: $200-400/month (db.r5.large with backups)
Cache: $50-100/month (Redis cluster)
Domain/SSL: $12/year + $0 (Let's Encrypt)
CDN/WAF: $100-200/month (CloudFlare or AWS)
Traffic: $50-100/month (outbound)
Monitoring: $50-100/month (Datadog/New Relic)
Support/Licensing: $100-300/month
Total: $600-1400/month
```

---

## Environment Variables Template

### .env.sandbox
```bash
CI_ENV=development
APP_MODE=SANDBOX
DEBUG_MODE=TRUE
LOG_LEVEL=DEBUG

DB_HOST=localhost
DB_USER=sandbox_user
DB_PASS=sandbox_password
DB_NAME=einvoice_sandbox

GOV_API_MODE=mock
GOV_API_ENDPOINT=http://localhost:9000/mock-api/

MAIL_DRIVER=log
CACHE_DRIVER=file
SESSION_DRIVER=database
```

### .env.production
```bash
CI_ENV=production
APP_MODE=PRODUCTION
DEBUG_MODE=FALSE
LOG_LEVEL=WARNING

DB_HOST=einvoice-prod.c9akciq32.us-east-1.rds.amazonaws.com
DB_USER=admin
DB_PASS=${AWS_SECRETS_MANAGER_SECRET}
DB_NAME=einvoice_prod

GOV_API_MODE=production
GOV_API_ENDPOINT=https://einvoicing.gov.in/api/
GOV_API_KEY=${GOV_PORTAL_API_KEY}
GOV_API_SECRET=${GOV_PORTAL_API_SECRET}

MAIL_DRIVER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USER=${SMTP_USERNAME}
MAIL_PASS=${SMTP_PASSWORD}

CACHE_DRIVER=redis
REDIS_HOST=10.0.1.50
REDIS_PORT=6379
REDIS_PASSWORD=${REDIS_AUTH_TOKEN}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_TIMEOUT=1800

APP_URL=https://yourdomain.com
DOMAIN_NAME=yourdomain.com
```

---

## Migration Checklist: Sandbox → Production

- [ ] Review and update all environment variables
- [ ] Verify SSL certificate is valid and renewed
- [ ] Test database replication and failover
- [ ] Verify all backups are working correctly
- [ ] Configure monitoring and alerting
- [ ] Set up log aggregation (ELK/CloudWatch)
- [ ] Test disaster recovery plan
- [ ] Configure email templates and SMTP
- [ ] Enable rate limiting and WAF rules
- [ ] Test API with production government endpoint
- [ ] Load test to verify performance targets
- [ ] Security audit and penetration testing
- [ ] Prepare incident response runbook
- [ ] Train support team on monitoring
- [ ] Document all configuration details
- [ ] Create rollback plan

---

## Support & Troubleshooting

### Sandbox Issues
- Check `application/logs/` for detailed error logs
- Enable DEBUG_MODE for verbose output
- Use `php -S localhost:8000 -t public` for built-in server
- Run database migrations: `php index.php migrate`

### Production Issues
- Check CloudWatch Logs for application errors
- Check system logs: `journalctl -u php8.0-fpm`
- Check Nginx error log: `/var/log/nginx/error.log`
- Use `tail -f /var/log/einvoice/application.log`
- Query slow query log for DB performance
- Check Redis connection: `redis-cli ping`
- Verify database replication status
- Contact AWS support if infrastructure issue

---

**Last Updated:** 2024-01-15
**Version:** 1.0
**Maintenance:** Review quarterly
