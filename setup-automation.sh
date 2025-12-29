#!/bin/bash

#########################################################
# XMAN Studio - Automated Setup Script
# Sets up GitHub Actions, Git Hooks, and Automation
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

print_header() {
    echo -e "\n${CYAN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║   🚀 XMAN Studio Automation Setup 🚀    ║${NC}"
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

# Check if in git repository
check_git_repo() {
    if [ ! -d .git ]; then
        print_error "Not a git repository!"
        print_info "Please run this script from the project root"
        exit 1
    fi
}

# Setup Git Hooks
setup_git_hooks() {
    print_step "Setting Up Git Hooks"

    # Create hooks directory
    mkdir -p .git/hooks

    # Pre-commit hook
    cat > .git/hooks/pre-commit << 'HOOK_EOF'
#!/bin/bash

echo "🔍 Running pre-commit checks..."

# Run Laravel Pint (code style)
if [ -f "./vendor/bin/pint" ]; then
    echo "  → Running Laravel Pint..."
    ./vendor/bin/pint --test
    if [ $? -ne 0 ]; then
        echo "❌ Code style check failed! Run './vendor/bin/pint' to fix."
        exit 1
    fi
fi

# Run tests
echo "  → Running tests..."
php artisan test --parallel
if [ $? -ne 0 ]; then
    echo "❌ Tests failed! Fix them before committing."
    exit 1
fi

echo "✅ Pre-commit checks passed!"
HOOK_EOF

    # Pre-push hook
    cat > .git/hooks/pre-push << 'HOOK_EOF'
#!/bin/bash

echo "🚀 Running pre-push checks..."

# Check for pending migrations
PENDING=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")
if [ "$PENDING" -gt "0" ]; then
    echo "⚠️  Warning: You have $PENDING pending migration(s)"
    read -p "Continue push? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "✅ Pre-push checks passed!"
HOOK_EOF

    # Commit-msg hook (enforce conventional commits)
    cat > .git/hooks/commit-msg << 'HOOK_EOF'
#!/bin/bash

COMMIT_MSG_FILE=$1
COMMIT_MSG=$(cat "$COMMIT_MSG_FILE")

# Conventional commit pattern
PATTERN="^(feat|fix|docs|style|refactor|test|chore|perf|ci|build|revert)(\(.+\))?: .{3,}"

if ! echo "$COMMIT_MSG" | grep -qE "$PATTERN"; then
    echo "❌ Invalid commit message format!"
    echo ""
    echo "Commit message must follow Conventional Commits:"
    echo "  type(scope): subject"
    echo ""
    echo "Types: feat, fix, docs, style, refactor, test, chore, perf, ci, build, revert"
    echo ""
    echo "Examples:"
    echo "  feat(cart): add quantity update"
    echo "  fix(auth): resolve login timeout"
    echo "  docs(readme): update installation guide"
    echo ""
    exit 1
fi
HOOK_EOF

    # Make hooks executable
    chmod +x .git/hooks/pre-commit
    chmod +x .git/hooks/pre-push
    chmod +x .git/hooks/commit-msg

    print_success "Git hooks installed"
    print_info "Pre-commit: Code style & tests"
    print_info "Pre-push: Migration check"
    print_info "Commit-msg: Conventional commits"
}

# Setup GitHub Issue Templates
setup_issue_templates() {
    print_step "Setting Up GitHub Issue Templates"

    mkdir -p .github/ISSUE_TEMPLATE

    # Bug report template
    cat > .github/ISSUE_TEMPLATE/bug_report.md << 'EOF'
---
name: Bug Report
about: Report a bug to help us improve
title: '[BUG] '
labels: bug
assignees: ''
---

## Bug Description
A clear description of what the bug is.

## Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. See error

## Expected Behavior
What you expected to happen.

## Actual Behavior
What actually happened.

## Screenshots
If applicable, add screenshots.

## Environment
- OS: [e.g. Ubuntu 22.04]
- PHP: [e.g. 8.3.28]
- Laravel: [e.g. 11.47.0]
- Browser: [e.g. Chrome 120]

## Additional Context
Any other relevant information.
EOF

    # Feature request template
    cat > .github/ISSUE_TEMPLATE/feature_request.md << 'EOF'
---
name: Feature Request
about: Suggest a new feature
title: '[FEATURE] '
labels: enhancement
assignees: ''
---

## Feature Description
A clear description of the feature you'd like.

## Problem It Solves
What problem does this solve?

## Proposed Solution
How should this work?

## Alternatives Considered
Any alternative solutions you've considered.

## Additional Context
Any other relevant information.
EOF

    print_success "Issue templates created"
}

# Setup Pull Request Template
setup_pr_template() {
    print_step "Setting Up Pull Request Template"

    cat > .github/pull_request_template.md << 'EOF'
## Description
Brief description of changes.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests added/updated
- [ ] All tests pass
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex logic
- [ ] Documentation updated
- [ ] No breaking changes (or documented)
- [ ] CHANGELOG.md updated

## Related Issues
Closes #(issue number)

## Screenshots (if applicable)
Add screenshots here.
EOF

    print_success "Pull request template created"
}

# Setup GitHub Actions Secrets Template
setup_secrets_template() {
    print_step "Creating GitHub Secrets Template"

    cat > .github/SECRETS_TEMPLATE.md << 'EOF'
# GitHub Secrets Configuration

Configure these secrets in GitHub:
**Settings → Secrets and variables → Actions → New repository secret**

## Required Secrets for Deployment

### SSH Configuration
```
SSH_HOST=xman4289.com
SSH_USER=admin
SSH_PORT=22
SSH_PRIVATE_KEY=[your-ssh-private-key]
```

### Server Configuration
```
DEPLOY_PATH=/home/admin/domains/xman4289.com/public_html
APP_URL=https://xman4289.com
```

## Optional Secrets

### Slack Notifications (optional)
```
SLACK_WEBHOOK_URL=[your-slack-webhook]
```

### Discord Notifications (optional)
```
DISCORD_WEBHOOK_URL=[your-discord-webhook]
```

## How to Generate SSH Key

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions@xmanstudio" -f ~/.ssh/github-actions

# Copy public key to server
ssh-copy-id -i ~/.ssh/github-actions.pub admin@xman4289.com

# Copy private key content for GitHub Secret
cat ~/.ssh/github-actions
# Copy everything including BEGIN and END lines
```

## Testing Secrets

After adding secrets, test with:
```
Actions → Deploy to Production → Run workflow
Select environment: staging (test first)
```
EOF

    print_success "Secrets template created: .github/SECRETS_TEMPLATE.md"
}

# Setup Development Environment
setup_dev_environment() {
    print_step "Setting Up Development Environment"

    # Create .env.example if not exists
    if [ ! -f .env.example ]; then
        cat > .env.example << 'EOF'
APP_NAME="XMAN Studio"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Bangkok
APP_URL=http://localhost

APP_LOCALE=th
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=th_TH

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=xmanstudio
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
EOF
        print_success ".env.example created"
    else
        print_info ".env.example already exists"
    fi

    # Create database directory for SQLite
    mkdir -p database
    touch database/.gitkeep

    print_success "Development environment configured"
}

# Setup Dependabot
setup_dependabot() {
    print_step "Setting Up Dependabot"

    cat > .github/dependabot.yml << 'EOF'
version: 2
updates:
  # Composer dependencies
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
    open-pull-requests-limit: 5
    labels:
      - "dependencies"
      - "composer"

  # NPM dependencies
  - package-ecosystem: "npm"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
    open-pull-requests-limit: 5
    labels:
      - "dependencies"
      - "npm"

  # GitHub Actions
  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
    labels:
      - "dependencies"
      - "github-actions"
EOF

    print_success "Dependabot configuration created"
}

# Create automated version bump script
create_version_script() {
    print_step "Creating Version Bump Scripts"

    cat > scripts/bump-version.sh << 'EOF'
#!/bin/bash

# Bump version script
# Usage: ./scripts/bump-version.sh [major|minor|patch]

TYPE=${1:-patch}

if [ ! -f VERSION ]; then
    echo "1.0.0" > VERSION
fi

CURRENT=$(cat VERSION)

case $TYPE in
    major)
        NEW=$(echo $CURRENT | awk -F. '{print $1+1".0.0"}')
        ;;
    minor)
        NEW=$(echo $CURRENT | awk -F. '{print $1"."$2+1".0"}')
        ;;
    patch)
        NEW=$(echo $CURRENT | awk -F. '{print $1"."$2"."$3+1}')
        ;;
    *)
        echo "Usage: $0 [major|minor|patch]"
        exit 1
        ;;
esac

echo "Bumping version: $CURRENT → $NEW"
echo "$NEW" > VERSION

echo "Version bumped to $NEW"
echo "Commit and tag:"
echo "  git add VERSION"
echo "  git commit -m \"chore: bump version to v$NEW\""
echo "  git tag v$NEW"
echo "  git push origin v$NEW"
EOF

    chmod +x scripts/bump-version.sh
    print_success "Version bump script created"
}

# Main setup function
main() {
    print_header

    check_git_repo

    print_info "This script will configure:"
    print_info "  • Git hooks (pre-commit, pre-push, commit-msg)"
    print_info "  • GitHub issue templates"
    print_info "  • Pull request template"
    print_info "  • Dependabot configuration"
    print_info "  • Development environment"
    print_info "  • Version bump scripts"
    echo ""

    read -p "Continue with setup? (Y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        print_info "Setup cancelled"
        exit 0
    fi

    # Create scripts directory
    mkdir -p scripts

    # Run setup steps
    setup_git_hooks
    setup_issue_templates
    setup_pr_template
    setup_secrets_template
    setup_dev_environment
    setup_dependabot
    create_version_script

    # Success message
    echo -e "\n${GREEN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║   ✓ Automation Setup Complete!          ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}\n"

    print_info "Next steps:"
    echo "1. Review generated files"
    echo "2. Commit automation configs:"
    echo "   ${YELLOW}git add .github/ scripts/ .env.example${NC}"
    echo "   ${YELLOW}git commit -m \"chore: setup automation\"${NC}"
    echo "3. Configure GitHub Secrets (see ${YELLOW}.github/SECRETS_TEMPLATE.md${NC})"
    echo "4. Push to GitHub:"
    echo "   ${YELLOW}git push origin$(git branch --show-current)${NC}"
    echo ""
    print_success "You're all set for professional development! 🚀"
}

# Run main
main
