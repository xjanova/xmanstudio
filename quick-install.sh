#!/bin/bash

#########################################################
# XMAN Studio - Quick Installation Script
# Fast installation with default settings
# Perfect for development and testing
#########################################################

set -e

# Colors
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════╗"
echo "║  🚀 XMAN Studio - Quick Install 🚀          ║"
echo "║  Installation time: ~5 minutes              ║"
echo "╚══════════════════════════════════════════════╝"
echo -e "${NC}\n"

echo -e "${YELLOW}This will install XMAN Studio with default settings.${NC}"
echo -e "${YELLOW}Press Enter to continue or Ctrl+C to cancel...${NC}"
read

# Step 1: Copy environment file
echo -e "\n${CYAN}[1/8] Setting up environment...${NC}"
cp .env.example .env
sed -i "s/APP_NAME=.*/APP_NAME=\"XMAN Studio\"/" .env
sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=sqlite/" .env
echo -e "${GREEN}✓ Environment configured${NC}"

# Step 2: Install PHP dependencies
echo -e "\n${CYAN}[2/8] Installing PHP dependencies...${NC}"
composer install --no-interaction --prefer-dist --optimize-autoloader
echo -e "${GREEN}✓ PHP dependencies installed${NC}"

# Step 3: Install Node dependencies
echo -e "\n${CYAN}[3/8] Installing Node.js dependencies...${NC}"
if command -v npm >/dev/null 2>&1; then
    npm install --silent
    echo -e "${GREEN}✓ Node.js dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ NPM not found, skipping${NC}"
fi

# Step 4: Generate application key
echo -e "\n${CYAN}[4/8] Generating application key...${NC}"
php artisan key:generate --force
echo -e "${GREEN}✓ Application key generated${NC}"

# Step 5: Create database
echo -e "\n${CYAN}[5/8] Creating database...${NC}"
touch database/database.sqlite
echo -e "${GREEN}✓ SQLite database created${NC}"

# Step 6: Run migrations
echo -e "\n${CYAN}[6/8] Running database migrations...${NC}"
php artisan migrate --force --seed
echo -e "${GREEN}✓ Database migrated and seeded${NC}"

# Step 7: Set permissions
echo -e "\n${CYAN}[7/8] Setting permissions...${NC}"
chmod -R 775 storage bootstrap/cache
php artisan storage:link
echo -e "${GREEN}✓ Permissions set${NC}"

# Step 8: Build assets
echo -e "\n${CYAN}[8/8] Building frontend assets...${NC}"
if command -v npm >/dev/null 2>&1; then
    npm run build
    echo -e "${GREEN}✓ Assets built${NC}"
else
    echo -e "${YELLOW}⚠ NPM not found, skipping asset build${NC}"
fi

# Clear cache
php artisan cache:clear >/dev/null 2>&1

# Success message
echo -e "\n${GREEN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✓ Installation Completed Successfully!     ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════╝${NC}\n"

echo -e "${CYAN}🚀 Quick Start:${NC}"
echo -e "   ${GREEN}php artisan serve${NC}"
echo -e "\n${CYAN}📱 Access your application at:${NC}"
echo -e "   ${GREEN}http://localhost:8000${NC}\n"

echo -e "${CYAN}📚 Next Steps:${NC}"
echo -e "   1. Visit your application in a browser"
echo -e "   2. Customize .env file for your needs"
echo -e "   3. Read README_XMANSTUDIO.md for documentation\n"

echo -e "${YELLOW}For production deployment, use:${NC} ${GREEN}./deploy.sh${NC}\n"
