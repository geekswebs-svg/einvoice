#!/bin/bash
# Setup script for Sandbox Environment
# Usage: bash setup-sandbox.sh

set -e

echo "======================================"
echo "CI3 e-Invoice Sandbox Setup"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as sudo
if [[ $EUID -eq 0 ]]; then
   echo -e "${RED}This script should not be run as root${NC}"
   exit 1
fi

# Step 1: Install PHP packages
echo -e "${YELLOW}Step 1: Installing PHP packages...${NC}"
sudo apt-get update
sudo apt-get install -y \
    php7.4-fpm \
    php7.4-mysql \
    php7.4-mbstring \
    php7.4-xml \
    php7.4-json \
    php7.4-curl \
    php7.4-cli \
    composer

# Step 2: Install MySQL
echo -e "${YELLOW}Step 2: Installing MySQL...${NC}"
sudo apt-get install -y mysql-server

# Step 3: Create database and user
echo -e "${YELLOW}Step 3: Creating database and user...${NC}"
mysql -u root -p -e "
CREATE DATABASE IF NOT EXISTS einvoice_sandbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'sandbox_user'@'localhost' IDENTIFIED BY 'sandbox_password';
GRANT ALL PRIVILEGES ON einvoice_sandbox.* TO 'sandbox_user'@'localhost';
FLUSH PRIVILEGES;
"

# Step 4: Import schema
echo -e "${YELLOW}Step 4: Importing database schema...${NC}"
mysql -u sandbox_user -p sandbox_password einvoice_sandbox < application/migrations/001_create_einvoice_tables.sql

# Step 5: Install composer dependencies
echo -e "${YELLOW}Step 5: Installing Composer dependencies...${NC}"
composer install

# Step 6: Create necessary directories
echo -e "${YELLOW}Step 6: Creating necessary directories...${NC}"
mkdir -p application/logs
mkdir -p application/cache
mkdir -p application/config
chmod 755 application/logs
chmod 755 application/cache

# Step 7: Create .env file
echo -e "${YELLOW}Step 7: Creating .env file...${NC}"
cat > .env.sandbox << EOF
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
EOF

# Step 8: Set permissions
echo -e "${YELLOW}Step 8: Setting permissions...${NC}"
chmod 644 .env.sandbox
chmod 755 application/config

echo ""
echo -e "${GREEN}✅ Sandbox setup completed!${NC}"
echo ""
echo "Next steps:"
echo "1. Update .env.sandbox with your settings"
echo "2. Start PHP development server: php -S localhost:8000"
echo "3. Test with: curl -X GET http://localhost:8000/api/v1/health"
echo ""
