#!/bin/bash

#########################################################
# XMAN Studio - Rollback Deployment
# Rolls back to previous deployment backup
#########################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

BACKUP_DIR="backups"

echo -e "${RED}╔════════════════════════════════════╗${NC}"
echo -e "${RED}║   Deployment Rollback Tool        ║${NC}"
echo -e "${RED}╚════════════════════════════════════╝${NC}\n"

# Check if backup directory exists
if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}✗ No backups found!${NC}"
    echo -e "${YELLOW}Backup directory '$BACKUP_DIR' does not exist${NC}\n"
    exit 1
fi

# List available backups
echo -e "${CYAN}Available backups:${NC}\n"
ls -lht "$BACKUP_DIR"/ | grep -v "^total" | nl

echo -e "\n${YELLOW}⚠ WARNING: This will restore from a backup${NC}"
read -p "Do you want to continue? (yes/NO): " confirm

if [ "$confirm" != "yes" ]; then
    echo -e "${YELLOW}Rollback cancelled${NC}\n"
    exit 0
fi

# Enable maintenance mode
echo -e "\n${CYAN}Enabling maintenance mode...${NC}"
php artisan down

# Find latest backups
LATEST_DB_BACKUP=$(ls -t "$BACKUP_DIR"/database_*.sql 2>/dev/null | head -1)
LATEST_ENV_BACKUP=$(ls -t "$BACKUP_DIR"/env_* 2>/dev/null | head -1)
LATEST_STORAGE_BACKUP=$(ls -t "$BACKUP_DIR"/storage_*.tar.gz 2>/dev/null | head -1)

# Restore database
if [ -n "$LATEST_DB_BACKUP" ]; then
    echo -e "\n${CYAN}Restoring database from $LATEST_DB_BACKUP...${NC}"

    if grep -q "DB_CONNECTION=mysql" .env; then
        DB_NAME=$(grep DB_DATABASE .env | cut -d'=' -f2)
        DB_USER=$(grep DB_USERNAME .env | cut -d'=' -f2)
        DB_PASS=$(grep DB_PASSWORD .env | cut -d'=' -f2)
        DB_HOST=$(grep DB_HOST .env | cut -d'=' -f2)

        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$LATEST_DB_BACKUP"
        echo -e "${GREEN}✓ Database restored${NC}"
    elif grep -q "DB_CONNECTION=sqlite" .env; then
        LATEST_SQLITE_BACKUP=$(ls -t "$BACKUP_DIR"/database_*.sqlite 2>/dev/null | head -1)
        if [ -n "$LATEST_SQLITE_BACKUP" ]; then
            cp "$LATEST_SQLITE_BACKUP" database/database.sqlite
            echo -e "${GREEN}✓ SQLite database restored${NC}"
        fi
    fi
else
    echo -e "${YELLOW}⚠ No database backup found${NC}"
fi

# Restore environment file
if [ -n "$LATEST_ENV_BACKUP" ]; then
    echo -e "\n${CYAN}Restoring environment file...${NC}"
    cp "$LATEST_ENV_BACKUP" .env
    echo -e "${GREEN}✓ Environment file restored${NC}"
else
    echo -e "${YELLOW}⚠ No environment backup found${NC}"
fi

# Restore storage
if [ -n "$LATEST_STORAGE_BACKUP" ]; then
    echo -e "\n${CYAN}Restoring storage...${NC}"
    tar -xzf "$LATEST_STORAGE_BACKUP" -C /
    echo -e "${GREEN}✓ Storage restored${NC}"
else
    echo -e "${YELLOW}⚠ No storage backup found${NC}"
fi

# Clear caches
echo -e "\n${CYAN}Clearing caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

# Disable maintenance mode
echo -e "\n${CYAN}Disabling maintenance mode...${NC}"
php artisan up
echo -e "${GREEN}✓ Application is now live${NC}"

echo -e "\n${GREEN}╔════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ Rollback completed!           ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════╝${NC}\n"

echo -e "${YELLOW}Please verify your application is working correctly${NC}\n"
