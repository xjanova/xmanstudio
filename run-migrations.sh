#!/bin/bash

#########################################################
# XMAN Studio - Run Database Migrations
# Safely runs database migrations
#########################################################

set -e

GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}╔════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   Database Migration Tool         ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════╝${NC}\n"

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}✗ .env file not found!${NC}"
    echo -e "${YELLOW}Please run ./install.sh first${NC}\n"
    exit 1
fi

# Show current environment
ENV=$(grep APP_ENV .env | cut -d'=' -f2)
echo -e "${YELLOW}Environment:${NC} $ENV"
echo -e "${YELLOW}Database:${NC} $(grep DB_CONNECTION .env | cut -d'=' -f2)\n"

# Warning for production
if [ "$ENV" = "production" ]; then
    echo -e "${RED}⚠ WARNING: Running migrations on PRODUCTION!${NC}"
    read -p "Are you sure you want to continue? (yes/NO): " confirm
    if [ "$confirm" != "yes" ]; then
        echo -e "${YELLOW}Migration cancelled${NC}\n"
        exit 0
    fi
fi

# Check migration status
echo -e "${CYAN}Checking migration status...${NC}\n"
php artisan migrate:status

echo -e "\n${YELLOW}Choose an option:${NC}"
echo "1) Run migrations (migrate)"
echo "2) Run migrations with seed (migrate --seed)"
echo "3) Rollback last migration (migrate:rollback)"
echo "4) Reset all migrations (migrate:reset)"
echo "5) Fresh migration (migrate:fresh)"
echo "6) Fresh migration with seed (migrate:fresh --seed)"
echo "7) Cancel"

read -p "Enter your choice (1-7): " choice

case $choice in
    1)
        echo -e "\n${CYAN}Running migrations...${NC}"
        php artisan migrate --force
        echo -e "${GREEN}✓ Migrations completed${NC}\n"
        ;;
    2)
        echo -e "\n${CYAN}Running migrations with seeders...${NC}"
        php artisan migrate --seed --force
        echo -e "${GREEN}✓ Migrations and seeders completed${NC}\n"
        ;;
    3)
        echo -e "\n${CYAN}Rolling back last migration...${NC}"
        php artisan migrate:rollback --force
        echo -e "${GREEN}✓ Rollback completed${NC}\n"
        ;;
    4)
        echo -e "\n${RED}⚠ This will rollback ALL migrations!${NC}"
        read -p "Are you sure? (yes/NO): " confirm
        if [ "$confirm" = "yes" ]; then
            php artisan migrate:reset --force
            echo -e "${GREEN}✓ All migrations reset${NC}\n"
        fi
        ;;
    5)
        echo -e "\n${RED}⚠ This will DROP all tables and re-run migrations!${NC}"
        read -p "Are you sure? (yes/NO): " confirm
        if [ "$confirm" = "yes" ]; then
            php artisan migrate:fresh --force
            echo -e "${GREEN}✓ Fresh migration completed${NC}\n"
        fi
        ;;
    6)
        echo -e "\n${RED}⚠ This will DROP all tables and re-run migrations with seeders!${NC}"
        read -p "Are you sure? (yes/NO): " confirm
        if [ "$confirm" = "yes" ]; then
            php artisan migrate:fresh --seed --force
            echo -e "${GREEN}✓ Fresh migration with seeders completed${NC}\n"
        fi
        ;;
    7)
        echo -e "${YELLOW}Cancelled${NC}\n"
        exit 0
        ;;
    *)
        echo -e "${RED}Invalid choice${NC}\n"
        exit 1
        ;;
esac

# Show final status
echo -e "${CYAN}Final migration status:${NC}\n"
php artisan migrate:status
