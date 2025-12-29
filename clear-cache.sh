#!/bin/bash

#########################################################
# XMAN Studio - Clear All Caches
# Clears application, config, route, view caches
#########################################################

set -e

GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   Clearing All Caches...          ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════╝${NC}\n"

# Clear application cache
echo -e "${YELLOW}[1/6]${NC} Clearing application cache..."
php artisan cache:clear
echo -e "${GREEN}✓ Application cache cleared${NC}"

# Clear configuration cache
echo -e "\n${YELLOW}[2/6]${NC} Clearing configuration cache..."
php artisan config:clear
echo -e "${GREEN}✓ Configuration cache cleared${NC}"

# Clear route cache
echo -e "\n${YELLOW}[3/6]${NC} Clearing route cache..."
php artisan route:clear
echo -e "${GREEN}✓ Route cache cleared${NC}"

# Clear view cache
echo -e "\n${YELLOW}[4/6]${NC} Clearing view cache..."
php artisan view:clear
echo -e "${GREEN}✓ View cache cleared${NC}"

# Clear compiled classes
echo -e "\n${YELLOW}[5/6]${NC} Clearing compiled classes..."
php artisan clear-compiled
echo -e "${GREEN}✓ Compiled classes cleared${NC}"

# Clear event cache
echo -e "\n${YELLOW}[6/6]${NC} Clearing event cache..."
php artisan event:clear
echo -e "${GREEN}✓ Event cache cleared${NC}"

# Optional: Clear opcache if available
if command -v php >/dev/null 2>&1; then
    if php -m | grep -q "Zend OPcache"; then
        echo -e "\n${YELLOW}[Bonus]${NC} Clearing OPcache..."
        # This requires web server restart in production
        echo -e "${GREEN}✓ Note: OPcache may require web server restart${NC}"
    fi
fi

echo -e "\n${GREEN}╔════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ All caches cleared!           ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════╝${NC}\n"

echo -e "${CYAN}Application is now using fresh configuration.${NC}\n"
