#!/bin/bash

#########################################################
# XMAN Studio - Installation Wizard
# Interactive installation script for DirectAdmin hosting
# Installs in the same directory as the script (no subdirectory created)
#########################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Logo
print_logo() {
    echo -e "${CYAN}"
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║                                                       ║"
    echo "║              🚀 XMAN STUDIO INSTALLER 🚀             ║"
    echo "║                                                       ║"
    echo "║          IT Solutions & Software Development         ║"
    echo "║           DirectAdmin Hosting Compatible             ║"
    echo "║                                                       ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

# Print step header
print_step() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}▶ $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

# Print success message
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Print error message
print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Print warning message
print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Print info message
print_info() {
    echo -e "${PURPLE}ℹ $1${NC}"
}

# Check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check system requirements
check_requirements() {
    print_step "Checking System Requirements"

    local has_errors=0

    # Check PHP
    if command_exists php; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        print_success "PHP $PHP_VERSION installed"

        # Check PHP version >= 8.2
        if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
            print_success "PHP version requirement met (>= 8.2)"
        else
            print_error "PHP version must be >= 8.2 (current: $PHP_VERSION)"
            has_errors=1
        fi
    else
        print_error "PHP is not installed"
        has_errors=1
    fi

    # Check Composer
    if command_exists composer; then
        COMPOSER_VERSION=$(composer --version | grep -oP '\d+\.\d+\.\d+' | head -1)
        print_success "Composer $COMPOSER_VERSION installed"
    else
        print_error "Composer is not installed"
        has_errors=1
    fi

    # Check Node.js
    if command_exists node; then
        NODE_VERSION=$(node --version)
        print_success "Node.js $NODE_VERSION installed"
    else
        print_warning "Node.js is not installed (optional but recommended)"
    fi

    # Check NPM
    if command_exists npm; then
        NPM_VERSION=$(npm --version)
        print_success "NPM $NPM_VERSION installed"
    else
        print_warning "NPM is not installed (optional but recommended)"
    fi

    # Check Git
    if command_exists git; then
        GIT_VERSION=$(git --version | grep -oP '\d+\.\d+\.\d+')
        print_success "Git $GIT_VERSION installed"
    else
        print_warning "Git is not installed"
    fi

    if [ $has_errors -eq 1 ]; then
        print_error "\nSystem requirements not met. Please install missing dependencies."
        exit 1
    fi

    print_success "\nAll required dependencies are installed!"
    print_info "Installation directory: $SCRIPT_DIR"
}

# Get user input
get_input() {
    local prompt="$1"
    local default="$2"
    local value

    if [ -n "$default" ]; then
        read -p "$prompt [$default]: " value
        echo "${value:-$default}"
    else
        read -p "$prompt: " value
        echo "$value"
    fi
}

# Get password input
get_password() {
    local prompt="$1"
    local password

    read -s -p "$prompt: " password
    echo ""
    echo "$password"
}

# Escape password for .env file (handles all special characters)
escape_for_env() {
    local value="$1"
    # If password contains special characters, wrap in double quotes and escape necessary chars
    if [[ "$value" =~ [\ \$\"\'\`\\!\#\&\|\;\<\>\(\)\[\]\{\}\*\?\~] ]]; then
        # Escape backslashes first, then double quotes, then dollar signs
        value="${value//\\/\\\\}"
        value="${value//\"/\\\"}"
        value="${value//\$/\\\$}"
        value="${value//\`/\\\`}"
        echo "\"$value\""
    else
        echo "$value"
    fi
}

# Configure environment
configure_environment() {
    print_step "Environment Configuration"

    if [ -f .env ]; then
        print_warning ".env file already exists"
        read -p "Do you want to reconfigure? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_info "Skipping environment configuration"
            return
        fi
    fi

    # Copy .env.example to .env
    cp .env.example .env
    print_success "Created .env file"

    # Application settings
    echo -e "\n${CYAN}Application Settings:${NC}"
    APP_NAME=$(get_input "Application Name" "XMAN Studio")
    APP_ENV=$(get_input "Environment (local/production)" "production")
    APP_DEBUG=$(get_input "Debug Mode (true/false)" "false")
    APP_URL=$(get_input "Application URL" "https://yourdomain.com")

    # Database settings
    echo -e "\n${CYAN}Database Configuration:${NC}"
    DB_CONNECTION=$(get_input "Database Type (mysql/sqlite)" "mysql")

    if [ "$DB_CONNECTION" = "mysql" ]; then
        DB_HOST=$(get_input "Database Host" "localhost")
        DB_PORT=$(get_input "Database Port" "3306")
        DB_DATABASE=$(get_input "Database Name" "xmanstudio")
        DB_USERNAME=$(get_input "Database Username" "root")
        DB_PASSWORD=$(get_password "Database Password")

        # Update .env file
        sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
        sed -i "s|DB_HOST=.*|DB_HOST=$DB_HOST|" .env
        sed -i "s|DB_PORT=.*|DB_PORT=$DB_PORT|" .env
        sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env
        sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env
        # Escape special characters in password for .env file
        DB_PASSWORD_ENV=$(escape_for_env "$DB_PASSWORD")
        # Use a different delimiter and escape for sed
        DB_PASSWORD_SED=$(printf '%s\n' "$DB_PASSWORD_ENV" | sed -e 's/[&/\]/\\&/g')
        sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD_SED|" .env
    else
        # SQLite - just use the default
        sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=sqlite/" .env
    fi

    # Update other .env values
    sed -i "s|APP_NAME=.*|APP_NAME=\"$APP_NAME\"|" .env
    sed -i "s|APP_ENV=.*|APP_ENV=$APP_ENV|" .env
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=$APP_DEBUG|" .env
    sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" .env

    # Mail configuration (optional)
    echo -e "\n${CYAN}Mail Configuration (Optional - press Enter to skip):${NC}"
    read -p "Configure mail settings? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        MAIL_MAILER=$(get_input "Mail Mailer (smtp/log)" "log")
        if [ "$MAIL_MAILER" = "smtp" ]; then
            MAIL_HOST=$(get_input "Mail Host" "smtp.gmail.com")
            MAIL_PORT=$(get_input "Mail Port" "587")
            MAIL_USERNAME=$(get_input "Mail Username" "")
            MAIL_PASSWORD=$(get_password "Mail Password")
            MAIL_FROM_ADDRESS=$(get_input "From Address" "info@xmanstudio.com")

            sed -i "s|MAIL_MAILER=.*|MAIL_MAILER=$MAIL_MAILER|" .env
            sed -i "s|MAIL_HOST=.*|MAIL_HOST=$MAIL_HOST|" .env
            sed -i "s|MAIL_PORT=.*|MAIL_PORT=$MAIL_PORT|" .env
            sed -i "s|MAIL_USERNAME=.*|MAIL_USERNAME=$MAIL_USERNAME|" .env
            # Escape special characters in mail password for .env file
            MAIL_PASSWORD_ENV=$(escape_for_env "$MAIL_PASSWORD")
            MAIL_PASSWORD_SED=$(printf '%s\n' "$MAIL_PASSWORD_ENV" | sed -e 's/[&/\]/\\&/g')
            sed -i "s|MAIL_PASSWORD=.*|MAIL_PASSWORD=$MAIL_PASSWORD_SED|" .env
            sed -i "s|MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=\"$MAIL_FROM_ADDRESS\"|" .env
        fi
    fi

    print_success "Environment configured successfully"
}

# Install dependencies
install_dependencies() {
    print_step "Installing Dependencies"

    # Composer install
    print_info "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    print_success "PHP dependencies installed"

    # NPM install (if available)
    if command_exists npm; then
        print_info "Installing Node.js dependencies..."
        npm install
        print_success "Node.js dependencies installed"
    else
        print_warning "NPM not available, skipping Node.js dependencies"
    fi
}

# Setup application
setup_application() {
    print_step "Setting Up Application"

    # Generate application key
    print_info "Generating application key..."
    php artisan key:generate --force
    print_success "Application key generated"

    # Create storage directories
    print_info "Creating storage directories..."
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    print_success "Storage directories created"

    # Set permissions
    print_info "Setting file permissions..."
    chmod -R 775 storage bootstrap/cache
    print_success "File permissions set"

    # Create SQLite database if needed
    if grep -q "DB_CONNECTION=sqlite" .env; then
        print_info "Creating SQLite database..."
        touch database/database.sqlite
        print_success "SQLite database created"
    fi
}

# Run migrations
run_migrations() {
    print_step "Database Migration"

    read -p "Do you want to run database migrations? (Y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        print_info "Running migrations..."
        php artisan migrate --force
        print_success "Database migrations completed"

        # Ask for seeders
        read -p "Do you want to run database seeders (demo data)? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_info "Running seeders..."
            php artisan db:seed --force
            print_success "Database seeded with demo data"
        fi
    else
        print_info "Skipping database migration"
    fi
}

# Build assets
build_assets() {
    print_step "Building Frontend Assets"

    if command_exists npm; then
        read -p "Do you want to build frontend assets? (Y/n): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Nn]$ ]]; then
            print_info "Building assets..."
            npm run build
            print_success "Assets built successfully"
        else
            print_info "Skipping asset build"
        fi
    else
        print_warning "NPM not available, skipping asset build"
    fi
}

# Final setup
final_setup() {
    print_step "Final Setup"

    # Clear cache
    print_info "Clearing application cache..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    print_success "Cache cleared"

    # Optimize for production
    if grep -q "APP_ENV=production" .env; then
        print_info "Optimizing for production..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        print_success "Application optimized"
    fi

    # Create symbolic link for storage (public_html/storage -> storage/app/public)
    print_info "Creating storage symbolic link..."
    php artisan storage:link
    print_success "Storage linked to public_html/storage"
}

# Print completion message
print_completion() {
    echo -e "\n${GREEN}"
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║                                                       ║"
    echo "║         🎉 INSTALLATION COMPLETED! 🎉                ║"
    echo "║                                                       ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo -e "${NC}"

    echo -e "\n${CYAN}DirectAdmin Hosting Setup:${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "The application is installed in: ${GREEN}$SCRIPT_DIR${NC}"
    echo -e ""
    echo -e "${PURPLE}Document Root Configuration:${NC}"
    echo -e "Set your domain's document root to:"
    echo -e "   ${GREEN}$SCRIPT_DIR/public_html${NC}"
    echo -e ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    echo -e "\n${CYAN}Visit your application at:${NC}"
    if grep -q "APP_URL=" .env; then
        APP_URL=$(grep "APP_URL=" .env | cut -d '=' -f2)
        echo -e "   ${GREEN}$APP_URL${NC}"
    else
        echo -e "   ${GREEN}https://yourdomain.com${NC}"
    fi

    echo -e "\n${CYAN}Useful Commands:${NC}"
    echo -e "  ${GREEN}./clear-cache.sh${NC}      - Clear all caches"
    echo -e "  ${GREEN}./fix-permissions.sh${NC}  - Fix file permissions"
    echo -e "  ${GREEN}./run-migrations.sh${NC}   - Run database migrations"
    echo -e "  ${GREEN}./deploy.sh${NC}           - Deploy updates"

    echo -e "\n${PURPLE}📖 Documentation:${NC} README_XMANSTUDIO.md"
    echo -e "${PURPLE}🆘 Support:${NC} support@xmanstudio.com"
    echo -e "${PURPLE}🌐 Website:${NC} https://xmanstudio.com"

    echo -e "\n${GREEN}Thank you for choosing XMAN Studio!${NC}\n"
}

# Main installation flow
main() {
    clear
    print_logo

    echo -e "${YELLOW}This wizard will guide you through the installation process.${NC}"
    echo -e "${YELLOW}Installation will be in the current directory: ${GREEN}$SCRIPT_DIR${NC}"
    echo -e "${YELLOW}No additional subdirectory will be created.${NC}"
    echo -e "\n${YELLOW}Press Enter to continue or Ctrl+C to cancel...${NC}"
    read

    check_requirements
    configure_environment
    install_dependencies
    setup_application
    run_migrations
    build_assets
    final_setup
    print_completion
}

# Run main function
main
