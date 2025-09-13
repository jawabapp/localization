# Laravel Localization Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jawabapp/localization.svg?style=flat-square)](https://packagist.org/packages/jawabapp/localization)
[![Total Downloads](https://img.shields.io/packagist/dt/jawabapp/localization.svg?style=flat-square)](https://packagist.org/packages/jawabapp/localization)
![GitHub Actions](https://github.com/jawabapp/localization/actions/workflows/main.yml/badge.svg)

A comprehensive Laravel package for managing multilingual applications with database-driven translations, automatic locale detection, and admin interface for translation management.

## Features

- 🌍 **Multi-language Support**: Support for 12+ popular languages out of the box
- 🔍 **Smart Locale Detection**: Automatic detection from URL, browser, session, and cookies
- 📁 **Modern Laravel Compatibility**: Compatible with Laravel 10 & 11 language directory structure
- 🗂️ **Dual Format Support**: Both PHP and JSON translation files
- 🎛️ **Admin Interface**: Web-based translation management
- 🚀 **Caching**: Built-in caching for optimal performance
- 🔧 **Artisan Commands**: CLI tools for import/export and synchronization
- 🛣️ **SEO Friendly**: Automatic hreflang tags and localized URLs
- 📱 **API Support**: RESTful API with locale detection
- ⚡ **Hot Reloading**: Automatic translation export on database changes

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+

## Installation

Install the package via Composer:

```bash
composer require jawabapp/localization
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=localization-config
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=localization-migrations
php artisan migrate
```

## Configuration

### Basic Configuration

The package configuration is located in `config/localization.php`:

```php
return [
    // Supported locales
    'supported_locales' => ['en', 'ar', 'es', 'fr', 'de', 'it'],

    // Locale detection
    'detect_browser_locale' => true,
    'store_in_session' => true,
    'store_in_cookie' => true,

    // URL configuration
    'url' => [
        'hide_default' => true,
        'force_locale_in_url' => false,
    ],

    // Cache settings
    'cache' => [
        'enabled' => true,
        'duration' => 60 * 24, // 24 hours
    ],
];
```

### Middleware Registration

Add the middleware to your `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Jawabapp\Localization\Http\Middleware\Web\Localization::class,
    ],

    'api' => [
        // ... other middleware
        \Jawabapp\Localization\Http\Middleware\Api\Localization::class,
    ],
];
```

### Route Configuration

For web routes, add locale prefix handling to `App\Providers\RouteServiceProvider`:

```php
use Jawabapp\Localization\Libraries\Localization;

Route::prefix(Localization::routePrefix())
    ->middleware('web')
    ->group(base_path('routes/web.php'));
```

## Usage

### Basic Translation Management

The package automatically handles locale detection and setting. You can use Laravel's built-in translation functions:

```php
// In your controllers or views
echo __('welcome.message');
echo trans('auth.failed');
```

### Working with Localized Routes

Create localized route groups:

```php
Route::localized(function ($locale) {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about', [AboutController::class, 'index'])->name('about');
});
```

Generate localized URLs:

```php
// Current locale
$url = route('home');

// Specific locale
$url = Route::localizedUrl('home', 'fr');

// Get all alternate URLs for SEO
$alternates = Localization::getAlternateUrls();
```

### Setting Locale Programmatically

```php
use Jawabapp\Localization\Libraries\Localization;

// Set locale for current request
Localization::setLocale('fr');

// Get supported locales
$locales = Localization::getSupportedLocales();

// Get locale native name
$name = Localization::getLocaleName('ar'); // Returns: العربية
```

### Model Integration

For models with translatable attributes, use the `_key` suffix:

```php
class Post extends Model
{
    protected $fillable = ['title_key', 'content_key'];

    // The package will automatically generate translation keys
}

// In your form
$post = Post::create([
    'title_key' => 'Hello World',
    'content_key' => 'This is the content...',
]);
```

## Artisan Commands

### Export Translations

Export database translations to files:

```bash
# Export all translations
php artisan localization:export

# Export specific locale
php artisan localization:export --locale=fr

# Export specific group
php artisan localization:export --locale=en --group=auth

# Export only JSON format
php artisan localization:export --format=json
```

### Import Translations

Import translations from files to database:

```bash
# Import all translations
php artisan localization:import

# Import specific locale
php artisan localization:import --locale=fr

# Overwrite existing translations
php artisan localization:import --overwrite
```

### Sync Translations

Synchronize translations between locales:

```bash
# Sync missing translations from English to French
php artisan localization:sync --from=en --to=fr --missing-only

# Copy all translations (overwrite existing)
php artisan localization:sync --from=en --to=de --overwrite
```

### Clear Cache

Clear translation cache:

```bash
# Clear all translation caches
php artisan localization:clear-cache

# Clear cache for specific locale
php artisan localization:clear-cache --locale=fr
```

## API Usage

The package includes API middleware for handling locale detection in API requests:

### Headers

Send locale preferences via headers:

```bash
# Custom headers
curl -H "X-Localization: fr" /api/endpoint
curl -H "X-Locale: es" /api/endpoint

# Standard Accept-Language header
curl -H "Accept-Language: fr,en;q=0.8" /api/endpoint
```

### Query Parameters

```bash
curl /api/endpoint?locale=fr
```

## Admin Interface

Access the translation management interface at `/localization` (configurable).

Features:
- View all translations by locale and group
- Add/edit/delete translations
- Search and filter translations
- Import/export translations
- View translation statistics
- Copy translations between locales

## File Structure

The package works with Laravel's modern language directory structure:

```
lang/
├── en/
│   ├── auth.php
│   ├── validation.php
│   └── messages.php
├── fr/
│   ├── auth.php
│   └── validation.php
├── en.json
└── fr.json
```

- **PHP files** (`lang/{locale}/group.php`): Structured translations
- **JSON files** (`lang/{locale}.json`): Simple key-value translations

## Advanced Features

### SEO Support

Automatic hreflang tags for multilingual SEO:

```php
// In your layout
@foreach(Localization::getAlternateUrls() as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}" />
@endforeach
```

### Caching

Translations are automatically cached for performance. Cache keys include:

- `localization.{locale}` - All translations for a locale
- `localization.{locale}.{group}` - Specific group translations

### Custom Translation Groups

Define custom groups in configuration:

```php
'translation_groups' => [
    'auth',
    'validation',
    'custom_module',
    'emails',
    'notifications',
],
```

### Database Connection

Use a specific database connection for translations:

```php
'database' => [
    'connection' => 'translations_db',
    'table' => 'app_translations',
],
```

## Upgrading from v1.x

The package now uses a different database schema. Run the migration to automatically upgrade:

1. Backup your current translations
2. Run `php artisan migrate`
3. The migration will convert `language_code` to `locale` and extract groups from keys
4. Verify your translations in the admin interface

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email i.qanah@gmail.com instead of using the issue tracker.

## Credits

- [Ibraheem Qanah](https://github.com/jawabapp)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- 📖 [Documentation](https://github.com/jawabapp/localization/wiki)
- 🐛 [Issue Tracker](https://github.com/jawabapp/localization/issues)
- 💬 [Discussions](https://github.com/jawabapp/localization/discussions)