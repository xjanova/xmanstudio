#!/bin/bash

#########################################################
# XMAN Studio - Automated Deployment Script
# For production and staging deployments
#########################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
BRANCH=${1:-main}
SKIP_BACKUP=${2:-false}
BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Functions
print_header() {
    echo -e "\n${CYAN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║   🚀 XMAN Studio Deployment Script 🚀    ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════╝${NC}\n"
}

print_step() {
    echo -e "\n${BLUE}━━━ $1 ━━━${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${PURPLE}ℹ $1${NC}"
}

# Check if in production
check_environment() {
    print_step "Checking Environment"

    if [ ! -f .env ]; then
        print_error ".env file not found"
        print_info "Please run ./install.sh first"
        exit 1
    fi

    if grep -q "APP_ENV=production" .env; then
        print_warning "Deploying to PRODUCTION environment"
        read -p "Are you sure you want to continue? (y/N): " confirm
        if [[ ! "$confirm" =~ ^[Yy](es)?$ ]]; then
            print_info "Deployment cancelled"
            exit 0
        fi
    else
        print_info "Deploying to $(grep APP_ENV .env | cut -d'=' -f2) environment"
    fi

    print_success "Environment check passed"
}

# Enable maintenance mode
enable_maintenance() {
    print_step "Enabling Maintenance Mode"

    php artisan down || true
    print_success "Application is now in maintenance mode"
}

# Disable maintenance mode
disable_maintenance() {
    print_step "Disabling Maintenance Mode"

    php artisan up
    print_success "Application is now live"
}

# Create backup
create_backup() {
    if [ "$SKIP_BACKUP" = "true" ]; then
        print_warning "Skipping backup (--skip-backup flag set)"
        return
    fi

    print_step "Creating Backup"

    # Create backup directory
    mkdir -p "$BACKUP_DIR"

    # Backup database
    if grep -q "DB_CONNECTION=mysql" .env; then
        print_info "Backing up MySQL database..."
        DB_NAME=$(grep DB_DATABASE .env | cut -d'=' -f2)
        DB_USER=$(grep DB_USERNAME .env | cut -d'=' -f2)
        DB_PASS=$(grep DB_PASSWORD .env | cut -d'=' -f2)
        DB_HOST=$(grep DB_HOST .env | cut -d'=' -f2)

        mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database_$TIMESTAMP.sql"
        print_success "Database backed up to $BACKUP_DIR/database_$TIMESTAMP.sql"
    elif grep -q "DB_CONNECTION=sqlite" .env; then
        print_info "Backing up SQLite database..."
        if [ -f database/database.sqlite ]; then
            cp database/database.sqlite "$BACKUP_DIR/database_$TIMESTAMP.sqlite"
            print_success "Database backed up to $BACKUP_DIR/database_$TIMESTAMP.sqlite"
        fi
    fi

    # Backup .env file
    cp .env "$BACKUP_DIR/env_$TIMESTAMP"
    print_success "Environment file backed up"

    # Backup storage
    print_info "Backing up storage..."
    tar -czf "$BACKUP_DIR/storage_$TIMESTAMP.tar.gz" storage/app/public 2>/dev/null || true
    print_success "Storage backed up"

    print_success "Backup completed: $BACKUP_DIR/*_$TIMESTAMP.*"
}

# Pull latest code
pull_code() {
    print_step "Pulling Latest Code"

    if [ -d .git ]; then
        print_info "Fetching from repository..."
        git fetch origin

        print_info "Pulling branch: $BRANCH"
        git pull origin "$BRANCH"

        CURRENT_COMMIT=$(git rev-parse --short HEAD)
        print_success "Updated to commit: $CURRENT_COMMIT"
    else
        print_warning "Not a git repository, skipping code pull"
    fi
}

# Install/Update dependencies
update_dependencies() {
    print_step "Updating Dependencies"

    # Composer
    print_info "Updating PHP dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    print_success "Composer dependencies updated"

    # NPM
    if command -v npm >/dev/null 2>&1; then
        print_info "Updating Node.js dependencies..."
        npm ci --production
        print_success "NPM dependencies updated"
    else
        print_warning "NPM not available, skipping Node.js dependencies"
    fi
}

# Run database migrations
run_migrations() {
    print_step "Running Database Migrations"

    read -p "Run database migrations? (Y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        # Try running migrations
        if php artisan migrate --force 2>&1 | tee /tmp/migration_output.log; then
            print_success "Migrations completed"
        else
            # Check if error is about table already exists
            if grep -q "already exists" /tmp/migration_output.log; then
                print_warning "Migration failed: Tables already exist"
                print_info "Attempting to repair database..."

                # Ask for confirmation to drop and recreate
                read -p "Drop all tables and recreate? This will delete all data! (y/N): " -n 1 -r
                echo
                if [[ $REPLY =~ ^[Yy]$ ]]; then
                    print_info "Running migrate:fresh to rebuild database..."
                    if php artisan migrate:fresh --force; then
                        print_success "Database rebuilt successfully"
                    else
                        print_error "Failed to rebuild database"
                        rm -f /tmp/migration_output.log
                        return 1
                    fi
                else
                    print_error "Migration repair cancelled"
                    rm -f /tmp/migration_output.log
                    return 1
                fi
            else
                print_error "Migration failed with unknown error"
                rm -f /tmp/migration_output.log
                return 1
            fi
        fi
        rm -f /tmp/migration_output.log
    else
        print_info "Skipping migrations"
    fi
}

# Build assets
build_assets() {
    print_step "Building Frontend Assets"

    if command -v npm >/dev/null 2>&1; then
        print_info "Building production assets..."
        npm run build
        print_success "Assets built successfully"
    else
        print_warning "NPM not available, skipping asset build"
    fi
}

# Clear and optimize cache
optimize_application() {
    print_step "Optimizing Application"

    # Clear all caches
    print_info "Clearing caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    print_success "Caches cleared"

    # Optimize for production
    if grep -q "APP_ENV=production" .env; then
        print_info "Optimizing for production..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        print_success "Application optimized for production"
    fi

    # Optimize composer autoload
    print_info "Optimizing autoloader..."
    composer dump-autoload --optimize
    print_success "Autoloader optimized"
}

# Fix permissions
fix_permissions() {
    print_step "Fixing File Permissions"

    chmod -R 775 storage bootstrap/cache
    print_success "Permissions fixed"
}

# Queue and workers
restart_queue() {
    print_step "Restarting Queue Workers"

    php artisan queue:restart
    print_success "Queue workers will restart on next job"
}

# Run post-deployment tasks
post_deployment() {
    print_step "Post-Deployment Tasks"

    # Clear expired password reset tokens
    php artisan auth:clear-resets
    print_success "Cleared expired password reset tokens"

    # Clear and cache routes (production only)
    if grep -q "APP_ENV=production" .env; then
        php artisan route:cache
        print_success "Routes cached"
    fi
}

# Health check
health_check() {
    print_step "Running Health Check"

    # Check if application is accessible
    if command -v curl >/dev/null 2>&1; then
        APP_URL=$(grep APP_URL .env | cut -d'=' -f2)
        if curl -f -s -o /dev/null "$APP_URL"; then
            print_success "Application is accessible at $APP_URL"
        else
            print_warning "Application might not be accessible at $APP_URL"
        fi
    fi

    # Check database connection
    if php artisan db:show >/dev/null 2>&1; then
        print_success "Database connection is working"
    else
        print_error "Database connection failed"
    fi

    print_success "Health check completed"
}

# Rollback function
rollback() {
    print_error "\nDeployment failed! Starting rollback..."

    if [ -d "$BACKUP_DIR" ] && [ "$SKIP_BACKUP" != "true" ]; then
        # Find latest backup
        LATEST_DB_BACKUP=$(ls -t "$BACKUP_DIR"/database_*.sql 2>/dev/null | head -1)

        if [ -n "$LATEST_DB_BACKUP" ]; then
            print_info "Restoring database from $LATEST_DB_BACKUP..."
            DB_NAME=$(grep DB_DATABASE .env | cut -d'=' -f2)
            DB_USER=$(grep DB_USERNAME .env | cut -d'=' -f2)
            DB_PASS=$(grep DB_PASSWORD .env | cut -d'=' -f2)
            DB_HOST=$(grep DB_HOST .env | cut -d'=' -f2)

            mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$LATEST_DB_BACKUP"
            print_success "Database restored"
        fi

        # Restore .env if needed
        LATEST_ENV_BACKUP=$(ls -t "$BACKUP_DIR"/env_* 2>/dev/null | head -1)
        if [ -n "$LATEST_ENV_BACKUP" ]; then
            cp "$LATEST_ENV_BACKUP" .env
            print_success "Environment file restored"
        fi
    fi

    disable_maintenance
    print_error "Rollback completed. Please check your application."
    exit 1
}

# Main deployment flow
main() {
    print_header

    print_info "Starting deployment at $(date)"
    print_info "Branch: $BRANCH"
    echo

    # Set trap for errors
    trap rollback ERR

    check_environment
    enable_maintenance
    create_backup
    pull_code
    update_dependencies
    run_migrations
    build_assets
    optimize_application
    fix_permissions
    restart_queue
    post_deployment
    disable_maintenance
    health_check

    # Success message
    echo -e "\n${GREEN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║   ✓ Deployment Completed Successfully!   ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}\n"

    print_info "Deployment finished at $(date)"
    print_success "Your application is now live!"

    # Show deployment summary
    echo -e "\n${CYAN}Deployment Summary:${NC}"
    echo -e "  ${PURPLE}Branch:${NC} $BRANCH"
    echo -e "  ${PURPLE}Time:${NC} $(date)"
    if [ -d .git ]; then
        echo -e "  ${PURPLE}Commit:${NC} $(git rev-parse --short HEAD)"
    fi
    echo -e "  ${PURPLE}Environment:${NC} $(grep APP_ENV .env | cut -d'=' -f2)"
    echo
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --branch=*)
            BRANCH="${1#*=}"
            shift
            ;;
        *)
            BRANCH="$1"
            shift
            ;;
    esac
done

# Run deployment
main
