#!/bin/bash

#########################################################
# XMAN Studio - Quick Deploy Script
# สคริปต์ Deploy แบบง่ายๆ ที่สุด
#########################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}"
echo "╔════════════════════════════════════════════╗"
echo "║   🚀 XMAN Studio - Quick Deploy 🚀        ║"
echo "╚════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if we're on the server or local
if [ -f /var/www/html/xmanstudio/deploy.sh ] || [ -d /var/www/xmanstudio ]; then
    echo -e "${GREEN}✓ Detected production server environment${NC}"
    ON_SERVER=true
else
    echo -e "${YELLOW}⚠ Detected local environment${NC}"
    ON_SERVER=false
fi

# Function to deploy on server
deploy_on_server() {
    echo -e "\n${BLUE}━━━ Step 1: Installing Composer Dependencies ━━━${NC}"
    composer install --no-dev --optimize-autoloader --no-interaction

    echo -e "\n${BLUE}━━━ Step 2: Setting up Environment ━━━${NC}"
    if [ ! -f .env ]; then
        cp .env.example .env
        php artisan key:generate
        echo -e "${GREEN}✓ Created .env and generated app key${NC}"
    else
        echo -e "${YELLOW}⚠ .env already exists, skipping...${NC}"
    fi

    echo -e "\n${BLUE}━━━ Step 3: Running Migrations ━━━${NC}"
    php artisan migrate --force 2>&1 || echo "Migration warnings (if any)"

    echo -e "\n${BLUE}━━━ Step 4: Building Frontend Assets ━━━${NC}"
    if command -v npm >/dev/null 2>&1; then
        npm install --production
        npm run build
        echo -e "${GREEN}✓ Assets built${NC}"
    else
        echo -e "${YELLOW}⚠ NPM not available, skipping asset build${NC}"
    fi

    echo -e "\n${BLUE}━━━ Step 5: Optimizing Application ━━━${NC}"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo -e "${GREEN}✓ Caches optimized${NC}"

    echo -e "\n${BLUE}━━━ Step 6: Fixing Permissions ━━━${NC}"
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || {
        echo -e "${YELLOW}⚠ Could not change ownership (requires root)${NC}"
        echo -e "${YELLOW}  Please run: sudo chown -R www-data:www-data storage bootstrap/cache${NC}"
    }

    echo -e "\n${BLUE}━━━ Step 7: Restarting Services ━━━${NC}"
    # Try to restart web server
    if command -v systemctl >/dev/null 2>&1; then
        sudo systemctl restart nginx 2>/dev/null && echo -e "${GREEN}✓ Nginx restarted${NC}" || \
        sudo systemctl restart apache2 2>/dev/null && echo -e "${GREEN}✓ Apache restarted${NC}" || \
        echo -e "${YELLOW}⚠ Could not restart web server (requires sudo)${NC}"
    fi

    echo -e "\n${GREEN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║   ✅ Deployment Completed Successfully!   ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}\n"
    echo -e "${CYAN}Your website should now be live at: https://xman4289.com${NC}\n"
}

# Function to show SSH instructions
show_ssh_instructions() {
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}คุณอยู่ใน Local Environment${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

    echo -e "${CYAN}วิธีที่ 1: Deploy ผ่าน SSH (แนะนำ - ง่ายที่สุด)${NC}\n"

    echo -e "ขั้นตอนที่คุณต้องทำ:"
    echo -e "1️⃣  SSH เข้าสู่ server ของคุณ:"
    echo -e "   ${GREEN}ssh user@your-server-ip${NC}"
    echo -e ""
    echo -e "2️⃣  ไปที่โฟลเดอร์ของ project:"
    echo -e "   ${GREEN}cd /var/www/xmanstudio${NC}"
    echo -e "   หรือ"
    echo -e "   ${GREEN}cd /path/to/your/project${NC}"
    echo -e ""
    echo -e "3️⃣  Pull โค้ดล่าสุด:"
    echo -e "   ${GREEN}git pull origin claude/fix-website-performance-X0B7g${NC}"
    echo -e ""
    echo -e "4️⃣  รัน deployment script:"
    echo -e "   ${GREEN}chmod +x quick-deploy.sh${NC}"
    echo -e "   ${GREEN}./quick-deploy.sh${NC}"
    echo -e ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

    echo -e "${CYAN}วิธีที่ 2: Deploy ทุกอย่างในคำสั่งเดียว (Copy-Paste)${NC}\n"

    echo -e "Copy คำสั่งนี้ แล้ว Paste ใน SSH:"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    cat << 'DEPLOY_COMMAND'
cd /var/www/xmanstudio && \
git pull origin claude/fix-website-performance-X0B7g && \
composer install --no-dev --optimize-autoloader && \
[ ! -f .env ] && cp .env.example .env && php artisan key:generate; \
php artisan migrate --force && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
chmod -R 775 storage bootstrap/cache && \
echo "✅ Deployment เสร็จสมบูรณ์!"
DEPLOY_COMMAND
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

    echo -e "${YELLOW}⚠️  หมายเหตุ: ถ้าโฟลเดอร์ไม่ใช่ /var/www/xmanstudio${NC}"
    echo -e "${YELLOW}    ให้แก้ path ให้ตรงกับ server ของคุณ${NC}\n"

    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

    echo -e "${CYAN}วิธีที่ 3: Deploy ผ่าน GitHub Actions (อัตโนมัติ)${NC}\n"

    echo -e "1️⃣  ไปที่: ${GREEN}https://github.com/xjanova/xmanstudio/actions${NC}"
    echo -e "2️⃣  คลิก: ${GREEN}Deploy to Production${NC}"
    echo -e "3️⃣  คลิก: ${GREEN}Run workflow${NC}"
    echo -e "4️⃣  เลือก: ${GREEN}production${NC}"
    echo -e "5️⃣  คลิก: ${GREEN}Run workflow${NC} (สีเขียว)"
    echo -e ""
    echo -e "${YELLOW}⚠️  ต้องตั้งค่า Secrets ใน GitHub ก่อน:${NC}"
    echo -e "   - SSH_HOST (IP ของ server)"
    echo -e "   - SSH_USER (username)"
    echo -e "   - SSH_PRIVATE_KEY (SSH key)"
    echo -e "   - DEPLOY_PATH (/var/www/xmanstudio)"
    echo -e "   - APP_URL (https://xman4289.com)"
    echo -e ""
}

# Main execution
if [ "$ON_SERVER" = true ]; then
    deploy_on_server
else
    show_ssh_instructions
fi
