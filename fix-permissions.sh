#!/bin/bash

#########################################################
# XMAN Studio - Fix File Permissions
# Fixes common permission issues
#########################################################

set -e

GREEN='\033[0;32m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}Fixing file permissions...${NC}\n"

# Storage and cache directories
echo "Setting storage permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Make sure directories exist
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/app/public

# Set correct permissions
chmod -R 775 storage/framework
chmod -R 775 storage/logs
chmod -R 775 storage/app

# Laravel specific
chmod -R 775 bootstrap/cache

# If running as specific user (optional)
if [ -n "$1" ]; then
    echo "Setting ownership to $1..."
    chown -R "$1:$1" storage bootstrap/cache
fi

echo -e "\n${GREEN}✓ Permissions fixed successfully!${NC}\n"

echo "Current permissions:"
ls -la storage/ | head -n 10
