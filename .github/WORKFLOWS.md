# GitHub Actions Workflows Documentation

This document describes all GitHub Actions workflows configured for XMAN Studio.

## Table of Contents

1. [CI - Tests & Quality Checks](#ci---tests--quality-checks)
2. [Release & Versioning](#release--versioning)
3. [Deploy to Production](#deploy-to-production)
4. [Setup Requirements](#setup-requirements)

---

## CI - Tests & Quality Checks

**File:** `.github/workflows/ci.yml`

**Trigger:** Automatically runs on:
- Push to `main`, `develop`, or any `claude/**` branches
- Pull requests to `main` or `develop`

### Jobs

#### 1. Tests
- Runs on PHP 8.2 and 8.3 (matrix strategy)
- Uses MySQL 8.0 for database testing
- Installs dependencies (Composer & NPM)
- Builds assets with Vite
- Runs database migrations
- Executes PHPUnit tests with coverage

#### 2. Code Quality
- Runs Laravel Pint for code style checking
- Performs security vulnerability audit with `composer audit`

#### 3. Build Check
- Verifies that production assets build successfully
- Checks for build manifest file

### Usage

```bash
# Automatically runs on git push
git push origin main

# Runs on pull request
gh pr create
```

---

## Release & Versioning

**File:** `.github/workflows/release.yml`

**Trigger:** Manual workflow dispatch

### Features

- **Semantic Versioning:** Supports major, minor, and patch bumps
- **Automatic Changelog:** Updates CHANGELOG.md with version info
- **Release Notes:** Generates notes from commit history
- **Git Tagging:** Creates version tags automatically
- **GitHub Releases:** Creates official release on GitHub

### How to Create a Release

1. **Via GitHub UI:**
   ```
   Actions → Release & Versioning → Run workflow
   Select version bump type: major | minor | patch
   ```

2. **Version Bump Types:**
   - `major`: Breaking changes (1.0.0 → 2.0.0)
   - `minor`: New features (1.0.0 → 1.1.0)
   - `patch`: Bug fixes (1.0.0 → 1.0.1)

### What Happens

1. Reads current version from `VERSION` file
2. Bumps version according to selected type
3. Updates `VERSION` file
4. Generates changelog entry in `CHANGELOG.md`
5. Creates release notes from git commits
6. Commits version changes
7. Creates git tag (e.g., `v1.2.3`)
8. Pushes changes and tag to repository
9. Creates GitHub Release with notes

### Example

```bash
# Current version: v1.0.0
# Select "minor" bump
# Result: v1.1.0

# Files updated:
# - VERSION (1.1.0)
# - CHANGELOG.md (new entry)
# - Git tag: v1.1.0
# - GitHub Release created
```

---

## Deploy to Production

**File:** `.github/workflows/deploy.yml`

**Trigger:**
- Manual workflow dispatch
- Automatically on version tag push (e.g., `v1.0.0`)

### Features

- **Environment Selection:** Deploy to production or staging
- **Asset Building:** Builds production assets
- **Deployment Package:** Creates optimized tar.gz
- **SSH Deployment:** Uploads and deploys via SSH
- **Health Check:** Verifies application after deployment
- **Status Notification:** Reports success/failure

### Prerequisites

You must configure these secrets in GitHub:

```
Settings → Secrets and variables → Actions → New repository secret
```

Required secrets:
- `SSH_HOST` - Server hostname or IP (e.g., xman4289.com)
- `SSH_USER` - SSH username (e.g., admin)
- `SSH_PRIVATE_KEY` - SSH private key for authentication
- `SSH_PORT` - SSH port (default: 22)
- `DEPLOY_PATH` - Deployment directory (e.g., /home/admin/domains/xman4289.com/public_html)
- `APP_URL` - Application URL for health check (e.g., https://xman4289.com)

### How to Deploy

1. **Automatic (on release):**
   ```bash
   # Create a release (triggers deploy automatically)
   Actions → Release & Versioning → Run workflow
   ```

2. **Manual:**
   ```
   Actions → Deploy to Production → Run workflow
   Select environment: production | staging
   ```

### Deployment Process

1. Checks out code
2. Installs PHP and Composer dependencies (production, optimized)
3. Installs Node.js and NPM dependencies
4. Builds production assets
5. Creates deployment package (excludes dev files)
6. Uploads package to server via SCP
7. Runs `deploy.sh` script on server
8. Performs health check
9. Reports status

### SSH Key Setup

Generate SSH key for GitHub Actions:

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions@xmanstudio" -f github-actions-key

# Copy public key to server
ssh-copy-id -i github-actions-key.pub admin@xman4289.com

# Add private key to GitHub Secrets
# Copy content of github-actions-key (private key)
cat github-actions-key
# Paste into GitHub → Settings → Secrets → SSH_PRIVATE_KEY
```

---

## Setup Requirements

### 1. Enable GitHub Actions

```
Repository → Settings → Actions → General
Workflow permissions: Read and write permissions
```

### 2. Configure Secrets

Add all required secrets as described in each workflow.

### 3. Branch Protection (Optional but Recommended)

```
Settings → Branches → Add rule

Branch name pattern: main
☑ Require status checks to pass
  - Tests (PHP 8.2)
  - Tests (PHP 8.3)
  - Code Quality Checks
  - Build Assets Check
☑ Require branches to be up to date
```

### 4. Environment Setup (for Deploy workflow)

```
Settings → Environments → New environment

Name: production
☑ Required reviewers (optional)
Add secrets specific to this environment
```

---

## Workflow Best Practices

### 1. Development Flow

```bash
# 1. Create feature branch
git checkout -b feature/new-feature

# 2. Make changes and push
git add .
git commit -m "feat: add new feature"
git push origin feature/new-feature

# 3. CI runs automatically

# 4. Create pull request
gh pr create

# 5. After approval and merge, create release
# Go to Actions → Release & Versioning
```

### 2. Hot Fix Flow

```bash
# 1. Create hotfix branch
git checkout -b hotfix/critical-bug

# 2. Fix and push
git add .
git commit -m "fix: resolve critical bug"
git push origin hotfix/critical-bug

# 3. Create PR and merge quickly

# 4. Create patch release
# Actions → Release & Versioning → Select "patch"

# 5. Deploy automatically triggers
```

### 3. Version Strategy

- **Major (X.0.0):** Breaking changes, major rewrites
- **Minor (1.X.0):** New features, backward compatible
- **Patch (1.0.X):** Bug fixes, security patches

### 4. Changelog Guidelines

Keep `CHANGELOG.md` updated with:
- **Added:** New features
- **Changed:** Changes to existing functionality
- **Deprecated:** Soon-to-be removed features
- **Removed:** Removed features
- **Fixed:** Bug fixes
- **Security:** Security improvements

---

## Troubleshooting

### CI Failures

**Tests failing:**
```bash
# Run tests locally
php artisan test
```

**Build failing:**
```bash
# Run build locally
npm run build
```

**Code style issues:**
```bash
# Fix code style
./vendor/bin/pint
```

### Deployment Failures

**SSH connection failed:**
- Verify SSH secrets are correct
- Test SSH connection manually:
  ```bash
  ssh -i github-actions-key admin@xman4289.com
  ```

**Health check failed:**
- Check server logs: `tail -f storage/logs/laravel.log`
- Verify `.env` configuration on server
- Check file permissions: `./fix-permissions.sh`

### Release Failures

**Permission denied:**
- Ensure workflow has write permissions
- Check branch protection rules

**Tag already exists:**
- Delete tag and try again:
  ```bash
  git tag -d v1.0.0
  git push origin :refs/tags/v1.0.0
  ```

---

## Support

For issues with workflows:
1. Check workflow logs in GitHub Actions tab
2. Review this documentation
3. Check repository issues
4. Contact development team

---

**Last Updated:** 2025-12-29
**Version:** 1.0.0
