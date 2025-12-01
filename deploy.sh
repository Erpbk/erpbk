#!/bin/bash

###############################################################################
# Laravel Deployment Script
# This script automates the deployment process
###############################################################################

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║         Laravel Deployment Script - Asset Fix                  ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check PHP version
echo "Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_VERSION;")
REQUIRED_VERSION="8.2.0"

if [ "$(printf '%s\n' "$REQUIRED_VERSION" "$PHP_VERSION" | sort -V | head -n1)" != "$REQUIRED_VERSION" ]; then
    echo -e "${RED}✗ PHP version $PHP_VERSION is too old!${NC}"
    echo -e "${YELLOW}  This project requires PHP 8.2.0 or higher${NC}"
    echo -e "${YELLOW}  Current PHP version: $PHP_VERSION${NC}"
    echo ""
    echo -e "${YELLOW}Please read PHP_VERSION_FIX.md for instructions on upgrading PHP.${NC}"
    echo ""
    exit 1
fi

echo -e "${GREEN}✓ PHP version $PHP_VERSION (OK)${NC}"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}✗ .env file not found!${NC}"
    echo -e "${YELLOW}  Creating .env from .env.example...${NC}"
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ .env file created${NC}"
        echo -e "${YELLOW}  Please edit .env and set APP_URL, database credentials, etc.${NC}"
        echo -e "${YELLOW}  Then run this script again.${NC}"
        exit 1
    else
        echo -e "${RED}✗ .env.example not found either!${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}✓ .env file found${NC}"

# Check if APP_KEY is set
if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${YELLOW}Generating application key...${NC}"
    php artisan key:generate
    echo -e "${GREEN}✓ Application key generated${NC}"
else
    echo -e "${GREEN}✓ Application key already set${NC}"
fi

# Install Composer dependencies
echo ""
echo "Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --optimize-autoloader --no-dev
    echo -e "${GREEN}✓ Composer dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ Composer not found. Please install dependencies manually.${NC}"
fi

# Set permissions
echo ""
echo "Setting file permissions..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || chmod -R 777 storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"

# Create storage link
echo ""
echo "Creating storage symlink..."
php artisan storage:link 2>/dev/null
echo -e "${GREEN}✓ Storage symlink created (or already exists)${NC}"

# Clear caches
echo ""
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

# Cache for production (optional)
read -p "Do you want to cache config/routes for production? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo -e "${GREEN}✓ Production caches created${NC}"
fi

# Build assets (if npm is available)
echo ""
read -p "Do you want to build assets with npm? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if command -v npm &> /dev/null; then
        echo "Installing npm dependencies..."
        npm install
        echo "Building assets for production..."
        npm run production
        echo -e "${GREEN}✓ Assets built${NC}"
    else
        echo -e "${YELLOW}⚠ npm not found. Skipping asset build.${NC}"
    fi
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                    Deployment Complete!                        ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo "1. Verify APP_URL in .env matches your domain"
echo "2. Ensure document root points to /public folder"
echo "3. Visit your website and check browser console for errors"
echo "4. Clear your browser cache (Ctrl + F5)"
echo ""
echo "For troubleshooting, see DEPLOYMENT_GUIDE.md"
echo ""

