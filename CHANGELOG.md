# Changelog

All notable changes to XMAN Studio will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-29

### Added
- Initial release of XMAN Studio
- E-commerce system with shopping cart and order management
- License key management system with activation tracking
- Custom software ordering with category separation
- Line OA integration for automatic order notifications
- Support ticket system with priority management
- Admin backend for complete management
- Comprehensive deployment system with auto-repair capabilities
- Laravel 11 with Symfony 7.x for PHP 8.3 compatibility
- Tailwind CSS v4 for modern UI design
- GitHub Actions workflows for CI/CD, versioning, and deployment

### Changed
- Migrated from Laravel 12 to Laravel 11 for better PHP 8.3 compatibility
- Updated Symfony packages to v7.x

### Fixed
- Migration execution order to resolve foreign key constraints
- Tailwind CSS v4 PostCSS configuration
- Deploy script to accept 'y' for production confirmation
- Auto-repair capability for migration errors
- NPM dependencies installation for vite

### Security
- PHP 8.2+ requirement with all security patches
- Proper input validation and sanitization
- CSRF protection on all forms
- SQL injection prevention through Eloquent ORM
