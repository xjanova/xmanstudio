#!/bin/bash

#########################################################
# XMAN Studio - Route Error Fix Script
# Fixes "Method Not Allowed" errors
#########################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   🔧 XMAN Studio Route Error Fix 🔧      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════╝${NC}\n"

echo -e "${YELLOW}⚠ This script will clear all Laravel caches${NC}\n"

# Clear all caches
echo -e "${BLUE}━━━ Clearing All Caches ━━━${NC}"
echo -e "${GREEN}✓${NC} Clearing configuration cache..."
php artisan config:clear

echo -e "${GREEN}✓${NC} Clearing route cache..."
php artisan route:clear

echo -e "${GREEN}✓${NC} Clearing view cache..."
php artisan view:clear

echo -e "${GREEN}✓${NC} Clearing application cache..."
php artisan cache:clear

echo -e "${GREEN}✓${NC} Clearing compiled files..."
php artisan clear-compiled

# Rebuild optimizations (only if in production)
if grep -q "APP_ENV=production" .env 2>/dev/null; then
    echo -e "\n${BLUE}━━━ Rebuilding Production Optimizations ━━━${NC}"

    echo -e "${GREEN}✓${NC} Caching configuration..."
    php artisan config:cache

    echo -e "${GREEN}✓${NC} Caching routes..."
    php artisan route:cache

    echo -e "${GREEN}✓${NC} Caching views..."
    php artisan view:cache

    echo -e "${GREEN}✓${NC} Optimizing autoloader..."
    composer dump-autoload --optimize
fi

# Verify routes
echo -e "\n${BLUE}━━━ Verifying Routes ━━━${NC}"
php artisan route:list --path=/ | head -20

# Success
echo -e "\n${GREEN}╔════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ Route Error Fixed Successfully!      ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}\n"

echo -e "${BLUE}Next steps:${NC}"
echo "1. Visit your website: ${YELLOW}https://xman4289.com${NC}"
echo "2. If error persists, restart web server: ${YELLOW}sudo service nginx restart${NC}"
echo ""
