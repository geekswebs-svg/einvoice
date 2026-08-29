#!/bin/bash
# Setup script for Production Environment
# Usage: sudo bash setup-production.sh

set -e

echo "======================================"
echo "CI3 e-Invoice Production Setup"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root${NC}"
   exit 1
fi

# Configuration
DOMAIN="yourdomain.com"
APP_USER="www-data"
APP_DIR="/var/www/einvoice"
BACKUP_DIR="/backups/einvoice"

echo -e "${YELLOW}Configuration:${NC}"
echo "Domain: $DOMAIN"
echo "App Directory: $APP_DIR"
echo "Backup Directory: $BACKUP_DIR"
echo ""

# Step 1: Update system
echo -e "${YELLOW}Step 1: Updating system...${NC}"
apt-get update
apt-get upgrade -y

# Step 2: Install packages
echo -e "${YELLOW}Step 2: Installing packages...${NC}"
apt-get install -y \
    nginx \
    php8.0-fpm \
    php8.0-mysql \
    php8.0-mbstring \
    php8.0-xml \
    php8.0-json \
    php8.0-curl \
    php8.0-opcache \
    mysql-server \
    redis-server \
    git \
    curl \
    composer \
    certbot \
    python3-certbot-nginx

# Step 3: Create directories
echo -e "${YELLOW}Step 3: Creating directories...${NC}"
mkdir -p $APP_DIR
mkdir -p $BACKUP_DIR
mkdir -p /var/log/php-fpm
chown -R $APP_USER:$APP_USER $APP_DIR
chmod 755 $APP_DIR

# Step 4: Clone application
echo -e "${YELLOW}Step 4: Cloning application...${NC}"
cd $APP_DIR
git clone https://github.com/geekswebs-svg/einvoice.git .
git checkout geekswebs-svg-ci3-einvoice-api-types

# Step 5: Install dependencies
echo -e "${YELLOW}Step 5: Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# Step 6: Create .env file
echo -e "${YELLOW}Step 6: Creating .env file...${NC}"
cat > $APP_DIR/.env.production << EOF
CI_ENV=production
APP_MODE=PRODUCTION
DEBUG_MODE=FALSE
LOG_LEVEL=WARNING

DB_HOST=localhost
DB_USER=einvoice_prod
DB_PASS=$(openssl rand -base64 32)
DB_NAME=einvoice_prod

GOV_API_MODE=production
GOV_API_ENDPOINT=https://einvoicing.gov.in/api/
GOV_API_TIMEOUT=60

MAIL_DRIVER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587

CACHE_DRIVER=redis
REDIS_HOST=localhost
REDIS_PORT=6379

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
EOF
chmod 600 $APP_DIR/.env.production

# Step 7: Create database
echo -e "${YELLOW}Step 7: Creating database...${NC}"
mysql -e "
CREATE DATABASE IF NOT EXISTS einvoice_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'einvoice_prod'@'localhost' IDENTIFIED BY '$(grep DB_PASS $APP_DIR/.env.production | cut -d= -f2)';
GRANT ALL PRIVILEGES ON einvoice_prod.* TO 'einvoice_prod'@'localhost';
FLUSH PRIVILEGES;
"

# Step 8: Import schema
echo -e "${YELLOW}Step 8: Importing database schema...${NC}"
DB_PASS=$(grep DB_PASS $APP_DIR/.env.production | cut -d= -f2)
mysql -u einvoice_prod -p$DB_PASS einvoice_prod < $APP_DIR/application/migrations/001_create_einvoice_tables.sql

# Step 9: Configure SSL
echo -e "${YELLOW}Step 9: Configuring SSL certificate...${NC}"
certbot certonly --standalone -d $DOMAIN -d www.$DOMAIN

# Step 10: Configure Nginx
echo -e "${YELLOW}Step 10: Configuring Nginx...${NC}"
cp scripts/nginx-config.conf /etc/nginx/sites-available/einvoice-prod
sed -i "s/yourdomain.com/$DOMAIN/g" /etc/nginx/sites-available/einvoice-prod
ln -sf /etc/nginx/sites-available/einvoice-prod /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx

# Step 11: Configure PHP-FPM
echo -e "${YELLOW}Step 11: Configuring PHP-FPM...${NC}"
cp scripts/php-fpm-pool.conf /etc/php/8.0/fpm/pool.d/einvoice.conf
systemctl restart php8.0-fpm

# Step 12: Set up backups
echo -e "${YELLOW}Step 12: Setting up backups...${NC}"
cp scripts/backup-einvoice.sh /usr/local/bin/
chmod +x /usr/local/bin/backup-einvoice.sh
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-einvoice.sh") | crontab -

# Step 13: Set permissions
echo -e "${YELLOW}Step 13: Setting permissions...${NC}"
chown -R $APP_USER:$APP_USER $APP_DIR
chmod 755 $APP_DIR
chmod 755 $APP_DIR/application
chmod 755 $APP_DIR/application/logs
chmod 755 $APP_DIR/application/cache
chmod 600 $APP_DIR/application/config/database.php
chmod 600 $APP_DIR/.env.production

# Step 14: Test health endpoint
echo -e "${YELLOW}Step 14: Testing health endpoint...${NC}"
sleep 2
curl -s https://localhost/api/v1/health | jq . || echo "Health check endpoint may take a moment to be ready"

echo ""
echo -e "${GREEN}✅ Production setup completed!${NC}"
echo ""
echo "Next steps:"
echo "1. Update .env.production with actual credentials"
echo "2. Verify SSL certificate: curl -I https://$DOMAIN"
echo "3. Check logs: tail -f /var/log/nginx/einvoice-access.log"
echo "4. Verify backups: ls -la $BACKUP_DIR"
echo ""
