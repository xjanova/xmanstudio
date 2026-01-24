# Claude AI Development Documentation

This directory contains comprehensive documentation for Claude AI assistants working on the XMAN Studio project.

## 📚 Documentation Files

### 1. [DEVELOPMENT_GUIDE.md](./DEVELOPMENT_GUIDE.md)
**Complete development guide for Claude AI**

Topics covered:
- Project overview and architecture
- Technology stack details
- Project structure walkthrough
- Development workflow and Git strategy
- Common development tasks
- Comprehensive troubleshooting guide
- Deployment procedures
- Quick reference commands

**Use this when:**
- Starting work on a new feature
- Troubleshooting issues
- Learning about the project structure
- Need deployment instructions

### 2. [CODING_STANDARDS.md](./CODING_STANDARDS.md)
**Coding standards and conventions**

Topics covered:
- PHP/Laravel coding standards (PSR-12)
- Database design conventions
- Blade template best practices
- JavaScript/CSS standards
- Tailwind CSS conventions
- Testing standards
- Security best practices
- Performance optimization

**Use this when:**
- Writing new code
- Refactoring existing code
- Code review
- Ensuring consistency

### 3. [LICENSE_SYSTEM.md](../docs/LICENSE_SYSTEM.md) ⚠️ CRITICAL
**License system documentation for all products**

> **⚠️ MUST READ when working on ANY license-related code!**

Topics covered:
- Device registration process
- Trial abuse detection methods
- Fake server protection techniques
- API endpoint reference
- Database schema
- Best practices for all products

**Use this when:**
- Working on license validation
- Adding new products to license system
- Debugging license issues
- Implementing trial system
- Adding anti-piracy features

## 🚀 Quick Start for Claude AI

### First Time Working on This Project?

1. **Read the Development Guide:**
   ```bash
   cat .claude/DEVELOPMENT_GUIDE.md
   ```

2. **Familiarize yourself with Coding Standards:**
   ```bash
   cat .claude/CODING_STANDARDS.md
   ```

3. **Check current project status:**
   ```bash
   git status
   git log --oneline -10
   cat VERSION
   ```

4. **Review recent changes:**
   ```bash
   git diff HEAD~5..HEAD
   ```

### Before Making Changes

1. **Understand the context:**
   - What is the user trying to achieve?
   - What files are involved?
   - What is the current behavior vs desired behavior?

2. **Read existing code:**
   - Never modify code you haven't read
   - Understand existing patterns
   - Follow the same conventions

3. **Check for existing solutions:**
   - Search the codebase for similar implementations
   - Check Laravel documentation
   - Review related GitHub issues

### After Making Changes

1. **Test your changes:**
   ```bash
   php artisan test
   ./vendor/bin/pint --test
   npm run build
   ```

2. **Verify no regressions:**
   - Check related functionality
   - Test edge cases
   - Review error logs

3. **Document your changes:**
   - Update CHANGELOG.md if significant
   - Add code comments where necessary
   - Update documentation if needed

## 🔍 Common Scenarios

### Scenario 1: Route Not Working

**Problem:** User reports "Method Not Allowed" error

**Solution:**
```bash
# Use the fix script
./fix-route-error.sh

# Or manually
php artisan route:clear
php artisan config:clear
php artisan optimize:clear
```

**Reference:** DEVELOPMENT_GUIDE.md → Troubleshooting → Route Method Not Allowed

### Scenario 2: Adding New Feature

**Steps:**
1. Read DEVELOPMENT_GUIDE.md → Development Workflow
2. Follow CODING_STANDARDS.md for code style
3. Create feature branch: `git checkout -b feature/name`
4. Make changes following existing patterns
5. Test thoroughly
6. Commit with conventional commit message
7. Push and create PR

**Reference:** DEVELOPMENT_GUIDE.md → Development Workflow

### Scenario 3: Database Migration Error

**Problem:** Foreign key constraint error

**Solution:**
1. Check migration timestamps - ensure correct order
2. Parent tables must have earlier timestamps
3. Rename migrations if needed
4. See DEVELOPMENT_GUIDE.md → Troubleshooting → Migration Foreign Key Error

### Scenario 4: Tailwind Build Error

**Problem:** PostCSS plugin error

**Solution:**
1. Verify postcss.config.js only has autoprefixer
2. Tailwind v4 uses @tailwindcss/vite plugin
3. See DEVELOPMENT_GUIDE.md → Troubleshooting → Tailwind CSS Build Error

## 📖 Additional Resources

### Project Documentation
- [Main README](../README.md) - Project overview
- [CHANGELOG](../CHANGELOG.md) - Version history
- [WORKFLOWS](./.github/WORKFLOWS.md) - GitHub Actions guide
- [VERSION](../VERSION) - Current version

### Laravel Documentation
- [Laravel 11.x Docs](https://laravel.com/docs/11.x)
- [Laravel Eloquent](https://laravel.com/docs/11.x/eloquent)
- [Laravel Blade](https://laravel.com/docs/11.x/blade)
- [Laravel Validation](https://laravel.com/docs/11.x/validation)

### Frontend Resources
- [Tailwind CSS v4](https://tailwindcss.com/docs)
- [Vite](https://vitejs.dev/)

## 🎯 Best Practices Summary

### DO ✅
- Read existing code before modifying
- Follow existing patterns and conventions
- Use Laravel conventions (Eloquent, Blade, etc.)
- Write tests for new features
- Validate all user input
- Use type hints and return types
- Document complex logic
- Test thoroughly before committing

### DON'T ❌
- Modify code you haven't read
- Use raw SQL instead of Eloquent
- Skip validation of user input
- Commit without testing
- Break existing functionality
- Ignore security best practices
- Use var in JavaScript
- Add unnecessary dependencies

## 🆘 Getting Help

1. **Check documentation first:**
   - This README
   - DEVELOPMENT_GUIDE.md
   - CODING_STANDARDS.md

2. **Search codebase:**
   - Look for similar implementations
   - Check existing patterns

3. **Laravel documentation:**
   - Official docs are comprehensive
   - Check version-specific features

4. **Ask the user:**
   - When requirements are unclear
   - When multiple approaches are possible
   - When making architectural decisions

## 📝 Maintaining This Documentation

### When to Update

Update documentation when:
- Adding new major features
- Changing development workflow
- Discovering new common issues
- Updating dependencies significantly
- Changing coding standards

### How to Update

1. Edit the relevant file
2. Update "Last Updated" date at bottom
3. Commit with message: `docs: update Claude AI documentation`
4. Keep documentation concise and practical

---

**Last Updated:** 2025-12-29
**Maintained By:** Development Team
**For:** Claude AI Assistants
