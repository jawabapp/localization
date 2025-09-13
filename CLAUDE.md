# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build and Development Commands

### Frontend (JavaScript/CSS)
```bash
# Development build
npm run dev

# Watch for changes
npm run watch

# Production build
npm run production
```

### Backend (PHP/Laravel Package)
```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Install dependencies
composer install

# Run package commands
php artisan localization:export
php artisan localization:import
php artisan localization:sync
php artisan localization:clear-cache
```

## Architecture Overview

This is a modernized Laravel localization package (`jawabapp/localization`) that provides comprehensive translation management with database-driven translations, automatic locale detection, and admin interface. The package has been completely revamped for Laravel 10/11 compatibility and global usage.

### Key Components

1. **Modern Service Provider Architecture**:
   - `LocalizationServiceProvider` - Main service provider with Laravel 10/11 compatibility
   - `TranslationServiceProvider` - Custom translator implementation
   - Auto-discovery enabled, route macros, artisan commands registration
   - Supports both PHP 8.1+ and modern Laravel features

2. **Advanced Translation System**:
   - Database-driven translations with `Translation` model
   - Supports both PHP arrays and JSON translation files
   - Modern Laravel `lang/` directory structure (not `resources/lang/`)
   - Automatic export to both formats with proper Laravel path detection
   - Caching system with configurable cache drivers and TTL
   - Import/export functionality with CLI commands

3. **Smart Locale Detection**:
   - Multi-layered detection: URL segments, browser Accept-Language, session, cookies
   - Configurable priority order and fallback mechanisms
   - SEO-friendly URL structure with locale prefixes
   - Session and cookie persistence

4. **Enhanced Middleware System**:
   - `Web\Localization` - Advanced web middleware with redirect logic
   - `Api\Localization` - API-focused middleware with header-based detection
   - Support for multiple locale detection methods
   - Content-Language response headers for APIs

5. **Comprehensive Configuration**:
   - 12+ supported locales with native names
   - Flexible URL handling (hide default locale, force locale in URLs)
   - Cache configuration with multiple drivers
   - Database connection customization
   - SEO settings (hreflang, alternate links)

6. **Database Schema**:
   - Modern schema with `locale`, `group`, `key`, `value`, `metadata` columns
   - Migration handles upgrade from old schema (`language_code` → `locale`)
   - Supports JSON metadata for translation context
   - Configurable table name and database connection

7. **Advanced Features**:
   - Route macros for localized route groups (`Route::localized()`)
   - SEO support with automatic hreflang generation
   - Translation statistics and management
   - Locale copying and synchronization
   - Hot reloading (automatic export on database changes)

## Artisan Commands

The package includes comprehensive CLI tools:

- `localization:export` - Export translations from database to files
- `localization:import` - Import translations from files to database
- `localization:sync` - Synchronize translations between locales
- `localization:clear-cache` - Clear translation caches

All commands support options for specific locales, groups, formats, and overwrite behavior.

## Laravel Integration

### Required Integration Steps:
1. Middleware registration in `app/Http/Kernel.php`
2. Route prefix configuration in `RouteServiceProvider`
3. Configuration publishing and customization
4. Database migration execution

### Package Structure:
- Uses Laravel package auto-discovery
- PSR-4 autoloading with proper namespace structure
- Publishable config, views, assets, and migrations
- Compatible with Laravel 10/11 directory structure

### Translation Groups:
The package manages configurable translation groups: auth, validation, general, messages, errors, forms, emails, notifications, etc.

## API Integration

The package provides full API support with:
- Custom headers: `X-Localization`, `X-Locale`
- Accept-Language header parsing
- Query parameter support
- Content-Language response headers
- RESTful locale detection

## SEO and Performance

- Automatic hreflang tag generation
- Localized URL generation
- Translation caching with configurable TTL
- Lazy loading and performance optimization
- Support for CDN and multi-server setups