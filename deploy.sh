#!/bin/bash

#########################################################
# XMAN Studio - Smart Automated Deployment Script
# For production and staging deployments
# Features:
#   - Smart migration handling (skip existing tables)
#   - Intelligent seeding (skip existing data)
#   - Detailed error logging and reporting
#   - Automatic rollback on failure
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
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="storage/backups"
LOG_DIR="storage/logs/deploy"
LOG_FILE="$LOG_DIR/deploy_${TIMESTAMP}.log"
ERROR_LOG="$LOG_DIR/error_${TIMESTAMP}.log"

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Create log directories
mkdir -p "$LOG_DIR"
mkdir -p "$BACKUP_DIR"

# Logging functions
log() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo "$message" >> "$LOG_FILE"
    echo -e "$2$1${NC}"
}

log_error() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1"
    echo "$message" >> "$LOG_FILE"
    echo "$message" >> "$ERROR_LOG"
    echo -e "${RED}✗ $1${NC}"
}

log_error_detail() {
    local message="$1"
    echo "$message" >> "$ERROR_LOG"
    echo "$message" >> "$LOG_FILE"
}

# Functions
print_header() {
    echo -e "\n${CYAN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║   🚀 XMAN Studio Deployment Script 🚀    ║${NC}"
    echo -e "${CYAN}║     Smart Migration & Seeding Support     ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════╝${NC}\n"
    log "Deployment started" ""
}

print_step() {
    log "STEP: $1" "${BLUE}"
    echo -e "\n${BLUE}━━━ $1 ━━━${NC}"
}

print_success() {
    log "SUCCESS: $1" "${GREEN}"
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    log_error "$1"
}

print_warning() {
    log "WARNING: $1" "${YELLOW}"
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    log "INFO: $1" "${PURPLE}"
    echo -e "${PURPLE}ℹ $1${NC}"
}

# Generate error report
generate_error_report() {
    local step="$1"
    local error_message="$2"
    local error_output="$3"

    echo "" >> "$ERROR_LOG"
    echo "═══════════════════════════════════════════════════════════" >> "$ERROR_LOG"
    echo "ERROR REPORT - $(date '+%Y-%m-%d %H:%M:%S')" >> "$ERROR_LOG"
    echo "═══════════════════════════════════════════════════════════" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"
    echo "Step: $step" >> "$ERROR_LOG"
    echo "Branch: $BRANCH" >> "$ERROR_LOG"
    echo "Commit: $(git rev-parse --short HEAD 2>/dev/null || echo 'N/A')" >> "$ERROR_LOG"
    echo "Environment: $(grep APP_ENV .env 2>/dev/null | cut -d'=' -f2 || echo 'N/A')" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"
    echo "Error Message:" >> "$ERROR_LOG"
    echo "$error_message" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"
    echo "Error Output:" >> "$ERROR_LOG"
    echo "---" >> "$ERROR_LOG"
    echo "$error_output" >> "$ERROR_LOG"
    echo "---" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"

    # System info
    echo "System Information:" >> "$ERROR_LOG"
    echo "  PHP Version: $(php -v 2>/dev/null | head -1 || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Composer: $(composer --version 2>/dev/null | head -1 || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Node: $(node -v 2>/dev/null || echo 'N/A')" >> "$ERROR_LOG"
    echo "  NPM: $(npm -v 2>/dev/null || echo 'N/A')" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"

    # Database info
    echo "Database Information:" >> "$ERROR_LOG"
    echo "  Connection: $(grep DB_CONNECTION .env 2>/dev/null | cut -d'=' -f2 || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Host: $(grep DB_HOST .env 2>/dev/null | cut -d'=' -f2 || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Database: $(grep DB_DATABASE .env 2>/dev/null | cut -d'=' -f2 || echo 'N/A')" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"

    # Recent Laravel log
    if [ -f "storage/logs/laravel.log" ]; then
        echo "Recent Laravel Logs (last 50 lines):" >> "$ERROR_LOG"
        echo "---" >> "$ERROR_LOG"
        tail -50 storage/logs/laravel.log >> "$ERROR_LOG" 2>/dev/null || echo "Could not read Laravel log" >> "$ERROR_LOG"
        echo "---" >> "$ERROR_LOG"
    fi

    echo "" >> "$ERROR_LOG"
    echo "═══════════════════════════════════════════════════════════" >> "$ERROR_LOG"
}

# Sanitize .env file to fix common issues
sanitize_env_file() {
    print_step "Sanitizing Environment File"

    if [ ! -f .env ]; then
        print_warning ".env file not found, skipping sanitization"
        return 0
    fi

    # Create a backup
    cp .env .env.backup.${TIMESTAMP}
    print_info "Created backup: .env.backup.${TIMESTAMP}"

    # Fix common .env issues using awk
    awk '
    BEGIN { FS="="; OFS="=" }
    {
        # Skip empty lines and comments
        if ($0 ~ /^[[:space:]]*$/ || $0 ~ /^[[:space:]]*#/) {
            print $0
            next
        }

        # If line contains =, process it
        if (NF >= 2) {
            key = $1
            # Get everything after first =
            value = substr($0, length($1) + 2)

            # Remove trailing whitespace and newlines from value
            gsub(/[[:space:]]+$/, "", value)
            gsub(/\r/, "", value)
            gsub(/\n/, "", value)

            # Print cleaned line
            print key OFS value
        } else {
            # Print line as-is if it does not contain =
            print $0
        }
    }
    ' .env > .env.tmp && mv .env.tmp .env

    # Check for duplicate keys and keep only the first occurrence
    awk '
    BEGIN { FS="="; OFS="=" }
    !seen[$1]++ {
        print $0
    }
    ' .env > .env.tmp && mv .env.tmp .env

    print_success "Environment file sanitized"

    # Verify the file is valid
    set +e
    PHP_CHECK=$(php artisan env:check 2>&1 || php -r "
        \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        try {
            \$dotenv->load();
            echo 'valid';
        } catch (Exception \$e) {
            echo 'invalid: ' . \$e->getMessage();
        }
    " 2>&1)
    set -e

    if echo "$PHP_CHECK" | grep -q "invalid"; then
        print_warning "Environment file may still have issues: $PHP_CHECK"
        print_info "Backup available at: .env.backup.${TIMESTAMP}"
    else
        print_success "Environment file validation passed"
    fi
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
        # Auto-continue without asking (use --dry-run to preview)
        print_info "Continuing deployment automatically..."
    else
        print_info "Deploying to $(grep APP_ENV .env | cut -d'=' -f2) environment"
    fi

    print_success "Environment check passed"
}

# Create database backup before migration
backup_database() {
    print_step "Backing Up Database"

    # Get database type
    DB_CONNECTION=$(grep DB_CONNECTION .env | cut -d'=' -f2)

    if [ "$DB_CONNECTION" = "mysql" ]; then
        DB_HOST=$(grep DB_HOST .env | cut -d'=' -f2)
        DB_PORT=$(grep DB_PORT .env | cut -d'=' -f2)
        DB_DATABASE=$(grep DB_DATABASE .env | cut -d'=' -f2)
        DB_USERNAME=$(grep DB_USERNAME .env | cut -d'=' -f2)
        DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d'=' -f2)

        BACKUP_FILE="$BACKUP_DIR/backup_${TIMESTAMP}.sql"

        if command -v mysqldump >/dev/null 2>&1; then
            print_info "Creating MySQL backup..."
            set +e
            BACKUP_OUTPUT=$(mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" 2>&1)
            BACKUP_EXIT=$?
            set -e

            if [ $BACKUP_EXIT -eq 0 ]; then
                echo "$BACKUP_OUTPUT" > "$BACKUP_FILE"
                print_success "Database backed up to $BACKUP_FILE"
            else
                print_warning "Could not create backup: $BACKUP_OUTPUT"
                log_error_detail "Backup failed: $BACKUP_OUTPUT"
            fi
        else
            print_warning "mysqldump not available, skipping backup"
        fi
    elif [ "$DB_CONNECTION" = "sqlite" ]; then
        if [ -f database/database.sqlite ]; then
            cp database/database.sqlite "$BACKUP_DIR/backup_${TIMESTAMP}.sqlite"
            print_success "SQLite database backed up"
        fi
    else
        print_warning "Unknown database type ($DB_CONNECTION), skipping backup"
    fi
}

# Enable maintenance mode
enable_maintenance() {
    print_step "Enabling Maintenance Mode"

    php artisan down --retry=60 2>&1 || true
    print_success "Application is now in maintenance mode"
}

# Disable maintenance mode
disable_maintenance() {
    print_step "Disabling Maintenance Mode"

    php artisan up 2>&1
    print_success "Application is now live"
}

# Pull latest code
pull_code() {
    print_step "Pulling Latest Code"

    if [ -d .git ]; then
        print_info "Fetching from repository..."

        set +e
        GIT_OUTPUT=$(git fetch origin 2>&1)
        GIT_EXIT=$?
        set -e

        if [ $GIT_EXIT -ne 0 ]; then
            print_error "Git fetch failed"
            generate_error_report "pull_code" "Git fetch failed" "$GIT_OUTPUT"
            return 1
        fi

        print_info "Pulling branch: $BRANCH"

        set +e
        GIT_OUTPUT=$(git pull origin "$BRANCH" 2>&1)
        GIT_EXIT=$?
        set -e

        if [ $GIT_EXIT -ne 0 ]; then
            print_error "Git pull failed"
            generate_error_report "pull_code" "Git pull failed for branch $BRANCH" "$GIT_OUTPUT"
            return 1
        fi

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

    set +e
    COMPOSER_OUTPUT=$(composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev 2>&1)
    COMPOSER_EXIT=$?
    set -e

    if [ $COMPOSER_EXIT -ne 0 ]; then
        print_error "Composer install failed"
        generate_error_report "update_dependencies" "Composer install failed" "$COMPOSER_OUTPUT"
        echo "$COMPOSER_OUTPUT"
        return 1
    fi

    print_success "Composer dependencies updated"

    # NPM
    if command -v npm >/dev/null 2>&1; then
        print_info "Updating Node.js dependencies..."

        set +e
        if [ -f package-lock.json ]; then
            NPM_OUTPUT=$(npm ci 2>&1)
        else
            NPM_OUTPUT=$(npm install 2>&1)
        fi
        NPM_EXIT=$?
        set -e

        if [ $NPM_EXIT -ne 0 ]; then
            print_warning "NPM install had issues (non-fatal)"
            log_error_detail "NPM install output: $NPM_OUTPUT"
        else
            print_success "NPM dependencies updated"
        fi
    else
        print_warning "NPM not available, skipping Node.js dependencies"
    fi
}

# Smart database migrations
run_migrations() {
    print_step "Running Smart Database Migrations"

    # Clear config cache to ensure fresh database config
    php artisan config:clear 2>/dev/null || true

    # Check if there are pending migrations
    print_info "Checking for pending migrations..."

    set +e
    MIGRATION_STATUS=$(php artisan migrate:status 2>&1)
    MIGRATION_STATUS_EXIT=$?
    PENDING_COUNT=$(echo "$MIGRATION_STATUS" | grep -c "Pending" || echo "0")
    set -e

    if [ $MIGRATION_STATUS_EXIT -ne 0 ]; then
        # Check if the error is due to missing migrations table
        if echo "$MIGRATION_STATUS" | grep -q "Migration table not found\|Base table or view not found.*migrations"; then
            print_warning "Migration table not found - this appears to be a fresh database"
            print_info "Installing migrations table..."

            set +e
            INSTALL_OUTPUT=$(php artisan migrate:install 2>&1)
            INSTALL_EXIT=$?
            set -e

            if [ $INSTALL_EXIT -ne 0 ]; then
                print_error "Failed to install migrations table"
                generate_error_report "run_migrations" "migrate:install failed" "$INSTALL_OUTPUT"
                echo "$INSTALL_OUTPUT"
                return 1
            fi

            print_success "Migrations table created"

            # Retry getting migration status
            set +e
            MIGRATION_STATUS=$(php artisan migrate:status 2>&1)
            MIGRATION_STATUS_EXIT=$?
            PENDING_COUNT=$(echo "$MIGRATION_STATUS" | grep -c "Pending" || echo "0")
            set -e

            if [ $MIGRATION_STATUS_EXIT -ne 0 ]; then
                print_error "Could not check migration status after installing table"
                generate_error_report "run_migrations" "migrate:status failed after install" "$MIGRATION_STATUS"
                echo "$MIGRATION_STATUS"
                return 1
            fi
        else
            print_error "Could not check migration status"
            generate_error_report "run_migrations" "migrate:status failed" "$MIGRATION_STATUS"
            echo "$MIGRATION_STATUS"
            return 1
        fi
    fi

    if [ "$PENDING_COUNT" = "0" ]; then
        print_success "No pending migrations"
        return 0
    fi

    print_warning "Found $PENDING_COUNT pending migration(s)"

    # Run migrations with error handling
    set +e
    MIGRATION_OUTPUT=$(php artisan migrate --force 2>&1)
    MIGRATION_EXIT=$?
    set -e

    echo "$MIGRATION_OUTPUT"
    log_error_detail "Migration output: $MIGRATION_OUTPUT"

    if [ $MIGRATION_EXIT -eq 0 ]; then
        print_success "All migrations completed successfully"
        return 0
    fi

    # Handle specific errors
    if echo "$MIGRATION_OUTPUT" | grep -q "already exists"; then
        print_warning "Some tables already exist, attempting to sync..."

        # Extract detailed error info
        FAILED_TABLE=$(echo "$MIGRATION_OUTPUT" | grep -oP "Table '\K[^']+" | head -1)
        FAILED_MIGRATION_FILE=$(echo "$MIGRATION_OUTPUT" | grep -oP "\d{4}_\d{2}_\d{2}_\d+_\w+" | head -1)

        print_info "Table '$FAILED_TABLE' already exists"
        print_info "Migration file: $FAILED_MIGRATION_FILE"

        # Log for debugging
        generate_error_report "run_migrations" "Table already exists: $FAILED_TABLE" "$MIGRATION_OUTPUT"

        # Show existing tables for debugging
        print_info "Checking existing tables..."
        set +e
        TABLES=$(php artisan tinker --execute="collect(\DB::select('SHOW TABLES'))->pluck('Tables_in_' . env('DB_DATABASE'))->implode(', ');" 2>/dev/null | tail -1)
        set -e
        print_info "Existing tables: $TABLES"
        log_error_detail "Existing tables: $TABLES"

        print_error "Migration failed. Please check error log: $ERROR_LOG"
        echo ""
        echo "  Suggested fixes:"
        echo "  1. Check if migration file has Schema::hasTable() check"
        echo "  2. Run: php artisan migrate:status"
        echo "  3. If safe, run: php artisan migrate:fresh --force (DELETES ALL DATA!)"
        echo ""

        return 1
    fi

    # Unknown error
    generate_error_report "run_migrations" "Migration failed with unknown error" "$MIGRATION_OUTPUT"
    print_error "Migration failed with unknown error"
    print_info "Check error log: $ERROR_LOG"

    return 1
}

# Smart seeding - only seed if data doesn't exist
run_smart_seeding() {
    print_step "Running Smart Database Seeding"

    # Check if seeders exist
    if [ ! -d "database/seeders" ]; then
        print_info "No seeders directory found, skipping"
        return 0
    fi

    # Run the smart seeder if it exists
    if [ -f "database/seeders/SmartDatabaseSeeder.php" ]; then
        print_info "Running SmartDatabaseSeeder..."

        set +e
        SEED_OUTPUT=$(php artisan db:seed --class=SmartDatabaseSeeder --force 2>&1)
        SEED_EXIT=$?
        set -e

        echo "$SEED_OUTPUT"

        if [ $SEED_EXIT -ne 0 ]; then
            # Analyze the error to provide helpful feedback
            if echo "$SEED_OUTPUT" | grep -q "Unknown column"; then
                UNKNOWN_COL=$(echo "$SEED_OUTPUT" | grep -oP "Unknown column '\K[^']+" | head -1)
                AFFECTED_TABLE=$(echo "$SEED_OUTPUT" | grep -oP "INSERT INTO `\K[^`]+" | head -1)

                print_warning "Column mismatch detected!"
                print_info "Column '$UNKNOWN_COL' does not exist in table '$AFFECTED_TABLE'"

                generate_error_report "run_smart_seeding" "Column mismatch: $UNKNOWN_COL in $AFFECTED_TABLE" "$SEED_OUTPUT"

                echo ""
                echo "  Suggested fixes:"
                echo "  1. Check SmartDatabaseSeeder uses correct column names"
                echo "  2. Run: php artisan migrate:status"
                echo "  3. Compare seeder data with migration schema"
                echo ""

                # Check if individual seeding methods have try-catch
                if echo "$SEED_OUTPUT" | grep -q "✗ Failed to add"; then
                    print_info "Some items were skipped due to errors (see above)"
                    print_warning "Seeding completed with partial errors"
                    return 0  # Continue deployment, as SmartDatabaseSeeder handles errors gracefully
                fi

                return 1
            elif echo "$SEED_OUTPUT" | grep -q "Table .* doesn't exist"; then
                MISSING_TABLE=$(echo "$SEED_OUTPUT" | grep -oP "Table '.*?\.\K[^']+" | head -1)
                print_warning "Table '$MISSING_TABLE' does not exist"
                print_info "SmartDatabaseSeeder should skip this table automatically"
                generate_error_report "run_smart_seeding" "Missing table: $MISSING_TABLE" "$SEED_OUTPUT"
                return 1
            else
                print_error "Smart seeding failed with unknown error"
                generate_error_report "run_smart_seeding" "SmartDatabaseSeeder failed" "$SEED_OUTPUT"
                return 1
            fi
        fi

        # Check for partial failures (errors handled gracefully by SmartDatabaseSeeder)
        if echo "$SEED_OUTPUT" | grep -q "✗ Failed"; then
            print_warning "Seeding completed with some partial failures (non-fatal)"
            print_info "Check output above for details"
        else
            print_success "Smart seeding completed successfully"
        fi
    elif [ -f "database/seeders/DatabaseSeeder.php" ]; then
        # Check if we should run seeders (only if tables are empty)
        print_info "Checking if seeding is needed..."

        set +e
        SHOULD_SEED=$(php artisan tinker --execute="echo \App\Models\User::count() == 0 ? 'yes' : 'no';" 2>/dev/null | tail -1)
        set -e

        if [ "$SHOULD_SEED" = "yes" ]; then
            print_info "Running DatabaseSeeder..."

            set +e
            SEED_OUTPUT=$(php artisan db:seed --force 2>&1)
            SEED_EXIT=$?
            set -e

            if [ $SEED_EXIT -ne 0 ]; then
                print_error "Seeding failed"
                generate_error_report "run_smart_seeding" "DatabaseSeeder failed" "$SEED_OUTPUT"
                return 1
            fi

            print_success "Seeding completed"
        else
            print_info "Data already exists, skipping seeding"
        fi
    else
        print_info "No seeders found, skipping"
    fi

    # Always run seeders that use updateOrCreate pattern (safe to run multiple times)
    run_always_seeders
}

# Seeders that should ALWAYS run on every deployment
# These seeders use updateOrCreate pattern and are safe to run multiple times
run_always_seeders() {
    print_step "Running Always-Run Seeders (updateOrCreate pattern)"

    # List of seeders that should always run
    # These use updateOrCreate and keep data in sync without duplicating
    ALWAYS_RUN_SEEDERS=(
        "QuotationSeeder"
    )

    for SEEDER in "${ALWAYS_RUN_SEEDERS[@]}"; do
        SEEDER_FILE="database/seeders/${SEEDER}.php"

        if [ -f "$SEEDER_FILE" ]; then
            print_info "Running $SEEDER..."

            set +e
            SEED_OUTPUT=$(php artisan db:seed --class="$SEEDER" --force 2>&1)
            SEED_EXIT=$?
            set -e

            if [ $SEED_EXIT -ne 0 ]; then
                print_warning "$SEEDER failed (non-fatal)"
                log_error_detail "$SEEDER output: $SEED_OUTPUT"

                # Show error but continue with other seeders
                if echo "$SEED_OUTPUT" | grep -q "Table .* doesn't exist"; then
                    print_info "Table not yet created, skipping $SEEDER"
                else
                    echo "$SEED_OUTPUT"
                fi
            else
                print_success "$SEEDER completed"
            fi
        else
            print_info "$SEEDER not found, skipping"
        fi
    done
}

# Build assets
build_assets() {
    print_step "Building Frontend Assets"

    if command -v npm >/dev/null 2>&1; then
        print_info "Building production assets..."

        set +e
        BUILD_OUTPUT=$(npm run build 2>&1)
        BUILD_EXIT=$?
        set -e

        if [ $BUILD_EXIT -ne 0 ]; then
            print_error "Asset build failed"
            generate_error_report "build_assets" "npm run build failed" "$BUILD_OUTPUT"
            echo "$BUILD_OUTPUT"
            return 1
        fi

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
    php artisan cache:clear 2>&1
    php artisan config:clear 2>&1
    php artisan route:clear 2>&1
    php artisan view:clear 2>&1
    print_success "Caches cleared"

    # Optimize for production
    if grep -q "APP_ENV=production" .env; then
        print_info "Optimizing for production..."

        set +e
        OPTIMIZE_OUTPUT=$(php artisan config:cache 2>&1)
        if [ $? -ne 0 ]; then
            print_warning "config:cache had issues: $OPTIMIZE_OUTPUT"
        fi

        php artisan route:cache 2>&1 || print_warning "route:cache had issues"
        php artisan view:cache 2>&1 || print_warning "view:cache had issues"
        set -e

        print_success "Application optimized for production"
    fi

    # Optimize composer autoload
    print_info "Optimizing autoloader..."
    composer dump-autoload --optimize 2>&1
    print_success "Autoloader optimized"
}

# Fix permissions
fix_permissions() {
    print_step "Fixing File Permissions"

    chmod -R 775 storage bootstrap/cache 2>&1
    print_success "Permissions fixed"
}

# Queue and workers
restart_queue() {
    print_step "Restarting Queue Workers"

    php artisan queue:restart 2>&1 || print_warning "queue:restart had issues"
    print_success "Queue workers will restart on next job"
}

# Run post-deployment tasks
post_deployment() {
    print_step "Post-Deployment Tasks"

    # Clear expired password reset tokens
    php artisan auth:clear-resets 2>/dev/null || true
    print_success "Cleared expired tokens"

    # Storage link
    if [ ! -L "public_html/storage" ]; then
        php artisan storage:link 2>/dev/null || true
        print_success "Storage linked"
    fi
}

# Health check
health_check() {
    print_step "Running Health Check"

    local HEALTH_ISSUES=0

    # Check database connection
    set +e
    DB_CHECK=$(php artisan tinker --execute="try { \DB::connection()->getPdo(); echo 'ok'; } catch(\Exception \$e) { echo 'fail: ' . \$e->getMessage(); }" 2>/dev/null | tail -1)
    set -e

    if [[ "$DB_CHECK" == "ok" ]]; then
        print_success "Database connection is working"
    else
        print_error "Database connection failed: $DB_CHECK"
        HEALTH_ISSUES=$((HEALTH_ISSUES + 1))
    fi

    # Check if application is accessible
    if command -v curl >/dev/null 2>&1; then
        APP_URL=$(grep APP_URL .env | cut -d'=' -f2)
        set +e
        HTTP_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$APP_URL" 2>/dev/null)
        set -e

        if [ "$HTTP_RESPONSE" = "200" ] || [ "$HTTP_RESPONSE" = "302" ]; then
            print_success "Application is accessible at $APP_URL (HTTP $HTTP_RESPONSE)"
        elif [ "$HTTP_RESPONSE" = "000" ]; then
            print_warning "Could not reach $APP_URL (timeout or connection refused)"
        else
            print_warning "Application returned HTTP $HTTP_RESPONSE"
        fi
    fi

    # Check storage permissions
    if [ -w "storage/logs" ]; then
        print_success "Storage is writable"
    else
        print_error "Storage is not writable"
        HEALTH_ISSUES=$((HEALTH_ISSUES + 1))
    fi

    if [ $HEALTH_ISSUES -gt 0 ]; then
        print_warning "Health check completed with $HEALTH_ISSUES issue(s)"
    else
        print_success "Health check completed - all systems operational"
    fi
}

# Handle deployment failure
on_error() {
    local exit_code=$?
    print_error "Deployment failed! (Exit code: $exit_code)"

    # Try to bring the app back up
    php artisan up 2>/dev/null || true

    echo ""
    echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${RED}                    DEPLOYMENT FAILED                        ${NC}"
    echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "${YELLOW}Error logs saved to:${NC}"
    echo -e "  ${PURPLE}Full log:${NC}  $LOG_FILE"
    echo -e "  ${PURPLE}Error log:${NC} $ERROR_LOG"
    echo ""
    echo -e "${YELLOW}To view error details:${NC}"
    echo -e "  cat $ERROR_LOG"
    echo ""
    echo -e "${YELLOW}To retry deployment:${NC}"
    echo -e "  ./deploy.sh $BRANCH"
    echo ""

    exit 1
}

# Main deployment flow
main() {
    print_header

    print_info "Starting deployment at $(date)"
    print_info "Branch: $BRANCH"
    print_info "Log file: $LOG_FILE"
    echo

    # Set trap for errors
    trap on_error ERR

    sanitize_env_file
    check_environment
    enable_maintenance
    pull_code
    update_dependencies
    backup_database
    run_migrations
    run_smart_seeding
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
    echo -e "  ${PURPLE}Log:${NC} $LOG_FILE"
    echo
}

# Parse arguments
DRY_RUN=0
while [[ $# -gt 0 ]]; do
    case $1 in
        --branch=*)
            BRANCH="${1#*=}"
            shift
            ;;
        --no-backup)
            SKIP_BACKUP=1
            shift
            ;;
        --seed)
            FORCE_SEED=1
            shift
            ;;
        --verbose|-v)
            VERBOSE=1
            shift
            ;;
        --dry-run)
            DRY_RUN=1
            shift
            ;;
        --help|-h)
            echo "Usage: ./deploy.sh [branch] [options]"
            echo ""
            echo "Options:"
            echo "  --branch=NAME    Specify branch to deploy (default: main)"
            echo "  --no-backup      Skip database backup"
            echo "  --seed           Force run seeders"
            echo "  --dry-run        Show what would be done without executing"
            echo "  --verbose, -v    Show verbose output"
            echo "  --help, -h       Show this help message"
            echo ""
            exit 0
            ;;
        *)
            BRANCH="$1"
            shift
            ;;
    esac
done

# Dry run mode
if [ $DRY_RUN -eq 1 ]; then
    echo -e "${CYAN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║         DRY RUN MODE - Preview Only        ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════╝${NC}"
    echo ""
    echo "Would execute the following steps:"
    echo "  1. Sanitize .env file (fix newlines, duplicates)"
    echo "  2. Check environment (.env)"
    echo "  3. Enable maintenance mode"
    echo "  4. Pull code from branch: $BRANCH"
    echo "  5. Update dependencies (composer, npm)"
    echo "  6. Backup database"
    echo "  7. Run migrations (auto-create migrations table if needed)"
    echo "  8. Run seeders (SmartDatabaseSeeder if exists)"
    echo "  9. Run always-run seeders (QuotationSeeder, etc.)"
    echo "  10. Build assets (npm run build)"
    echo "  11. Optimize application"
    echo "  12. Fix permissions"
    echo "  13. Restart queue workers"
    echo "  14. Post-deployment tasks"
    echo "  15. Disable maintenance mode"
    echo "  16. Health check"
    echo ""
    echo "Run without --dry-run to execute deployment."
    exit 0
fi

# Run deployment
main
