# 🌍 Laravel Localization Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jawabapp/localization.svg?style=flat-square)](https://packagist.org/packages/jawabapp/localization)
[![Total Downloads](https://img.shields.io/packagist/dt/jawabapp/localization.svg?style=flat-square)](https://packagist.org/packages/jawabapp/localization)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue?style=flat-square)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%2B-red?style=flat-square)](https://laravel.com)

A comprehensive, modern Laravel package for managing multilingual applications with database-driven translations, automatic locale detection, and a beautiful admin interface. Built for Laravel 10+ with full support for the latest language directory structure.

## ✨ Features

- 🌐 **12+ Languages**: Pre-configured support for major world languages
- 🎯 **Smart Locale Detection**: Browser, URL, session, and cookie-based detection
- 📁 **Modern Laravel Compatibility**: Laravel 10/11 `lang/` directory structure
- 🗂️ **Dual Format Support**: Both PHP arrays and JSON translations
- 🎛️ **Professional Admin Interface**: Beautiful Tailwind CSS interface
- ⚡ **Performance Optimized**: Built-in caching and file-based translations
- 🔧 **Artisan Commands**: CLI tools for import/export and management
- 🛣️ **SEO Friendly**: Automatic hreflang tags and localized URLs
- 📱 **API Support**: RESTful API with intelligent locale detection
- 🔄 **Hot Reloading**: Automatic file export on database changes

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 10.0 or higher
- **Extensions**: mbstring, json

## 🚀 Quick Installation

### 1. Install via Composer

```bash
composer require jawabapp/localization
```

### 2. Publish Configuration

```bash
# Publish config file
php artisan vendor:publish --tag=localization-config

# Publish and run migrations
php artisan vendor:publish --tag=localization-migrations
php artisan migrate
```

### 3. Configure Middleware

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

### 4. Setup Route Prefixes

Update your `App\Providers\RouteServiceProvider.php`:

```php
use Jawabapp\Localization\Libraries\Localization;

public function boot(): void
{
    // Web routes with locale prefix
    Route::prefix(Localization::routePrefix())
        ->middleware('web')
        ->group(base_path('routes/web.php'));
}
```

### 5. Access the Admin Interface

Visit `/localization` in your browser to manage translations!

## ⚙️ Configuration

The main configuration file is published to `config/localization.php`:

```php
<?php

return [
    // Supported locales
    'supported_locales' => ['en', 'ar', 'es', 'fr', 'de'],

    // Locale detection
    'detect_browser_locale' => true,
    'store_in_session' => true,
    'store_in_cookie' => true,

    // URL configuration
    'url' => [
        'hide_default' => true, // Hide default locale in URLs
        'force_locale_in_url' => false,
    ],

    // Cache settings
    'cache' => [
        'enabled' => true,
        'duration' => 60 * 24, // 24 hours
    ],

    // Translation groups
    'translation_groups' => [
        'auth', 'validation', 'general', 'messages'
    ],
];
```

### Available Locales

The package comes pre-configured with these locales:

| Code | Language | Native Name |
|------|----------|-------------|
| `en` | English | English |
| `ar` | Arabic | العربية |
| `es` | Spanish | Español |
| `fr` | French | Français |
| `de` | German | Deutsch |
| `it` | Italian | Italiano |
| `pt` | Portuguese | Português |
| `ru` | Russian | Русский |
| `zh` | Chinese | 中文 |
| `ja` | Japanese | 日本語 |
| `ko` | Korean | 한국어 |
| `tr` | Turkish | Türkçe |

## 📖 Usage Guide

### Basic Translation Management

Use Laravel's built-in translation functions:

```php
// In controllers or views
echo __('messages.welcome');
echo trans('auth.failed');
echo trans_choice('messages.items', 5);

// With parameters
echo __('messages.hello', ['name' => 'John']);
```

### Dynamic Locale Switching

```php
use Jawabapp\Localization\Libraries\Localization;

// Set locale programmatically
Localization::setLocale('fr');

// Get current locale
$locale = app()->getLocale();

// Get all supported locales
$locales = Localization::getSupportedLocales();

// Get locale native name
$name = Localization::getLocaleName('ar'); // Returns: العربية
```

### Working with Localized Routes

Create routes that automatically work with all locales:

```php
Route::localized(function ($locale) {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/products', [ProductController::class, 'index'])->name('products');
});
```

Generate localized URLs:

```php
// Current locale URL
$url = route('products');

// Specific locale URL
$url = Route::localizedUrl('products', 'fr');

// Get all locale URLs for hreflang tags
$alternateUrls = Localization::getAlternateUrls();
```

### Model Integration

For models with translatable fields, use the `_key` suffix:

```php
class Post extends Model
{
    protected $fillable = ['title_key', 'content_key', 'slug'];

    // Accessors for translated content
    public function getTitleAttribute()
    {
        return $this->title_key ? __($this->title_key) : '';
    }

    public function getContentAttribute()
    {
        return $this->content_key ? __($this->content_key) : '';
    }
}

// Usage
$post = Post::create([
    'title_key' => 'My Blog Post Title',
    'content_key' => 'This is the blog post content...',
    'slug' => 'my-blog-post'
]);
```

## 🎛️ Admin Interface

Access the admin interface at `/localization` to:

### Translation Management
- ✅ View all translations by language and group
- ✅ Add, edit, and delete translations
- ✅ Search and filter translations
- ✅ Bulk operations (delete, export)
- ✅ Visual translation status indicators

### Import/Export Tools
- ✅ Export translations to PHP/JSON files
- ✅ Import from existing language files
- ✅ Sync translations between locales
- ✅ Translation statistics and completion rates

### Features
- 📱 **Responsive Design**: Works perfectly on mobile and desktop
- 🎨 **Modern UI**: Beautiful Tailwind CSS interface
- ⚡ **Real-time Updates**: Live search and filtering
- 🔄 **Batch Operations**: Handle multiple translations at once

## 🔧 Artisan Commands

The package includes powerful CLI commands:

### Export Translations

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

```bash
# Import all translations from files
php artisan localization:import

# Import specific locale
php artisan localization:import --locale=fr

# Overwrite existing translations
php artisan localization:import --overwrite
```

### Sync Between Locales

```bash
# Sync missing translations from English to French
php artisan localization:sync --from=en --to=fr --missing-only

# Copy all translations (overwrite existing)
php artisan localization:sync --from=en --to=de --overwrite
```

### Cache Management

```bash
# Clear translation cache
php artisan localization:clear-cache

# Clear cache for specific locale
php artisan localization:clear-cache --locale=fr
```

## 🌐 API Usage

The package provides full API support with intelligent locale detection:

### Request Headers

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

### Response Headers

The API middleware automatically adds:
- `Content-Language: fr` header to responses
- Proper locale detection from multiple sources

## 🗂️ File Structure

The package supports Laravel's modern language directory structure:

```
lang/
├── en/
│   ├── auth.php
│   ├── validation.php
│   └── messages.php
├── fr/
│   ├── auth.php
│   └── validation.php
├── ar/
│   └── messages.php
├── en.json
├── fr.json
└── ar.json
```

### File Types

- **PHP Files** (`lang/{locale}/group.php`): Structured translations with nested arrays
- **JSON Files** (`lang/{locale}.json`): Simple key-value translations

## 🔍 SEO Features

### Automatic Hreflang Tags

Add to your layout:

```blade
@foreach(Localization::getAlternateUrls() as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}" />
@endforeach
```

### Localized URLs

```php
// Generates: /en/products or /products (if default)
Route::get('/products', [ProductController::class, 'index'])->name('products');
```

## ⚡ Performance Optimization

### Caching

Translations are automatically cached:

```php
// Cache keys:
// localization.{locale} - All translations for locale
// localization.{locale}.{group} - Specific group translations
```

### Static File Export

Export to static files for optimal performance:

```bash
php artisan localization:export
```

Static files load faster than database queries and are automatically cached by Laravel.

## 🔧 Advanced Configuration

### Custom Database Connection

```php
'database' => [
    'connection' => 'translations_db',
    'table' => 'app_translations',
],
```

### Custom Translation Groups

```php
'translation_groups' => [
    'auth', 'validation', 'emails',
    'custom_module', 'product_catalog'
],
```

### Middleware Configuration

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'admin/localization', // Custom admin path
    'middleware' => ['web', 'auth', 'admin'],
],
```

## 📚 Examples

### Complete Laravel Application Setup

1. **Install and Configure**:
```bash
composer require jawabapp/localization
php artisan vendor:publish --tag=localization-config
php artisan migrate
```

2. **Create Localized Routes**:
```php
// routes/web.php
Route::localized(function ($locale) {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('/products', function () {
        return view('products.index');
    })->name('products');

    Route::get('/contact', function () {
        return view('contact');
    })->name('contact');
});
```

3. **Add Language Switcher**:
```blade
<!-- resources/views/layouts/app.blade.php -->
<div class="language-switcher">
    @foreach(Localization::getSupportedLocales() as $locale)
        <a href="{{ Route::localizedUrl(Route::currentRouteName(), $locale) }}"
           class="{{ app()->getLocale() === $locale ? 'active' : '' }}">
            {{ Localization::getLocaleName($locale) }}
        </a>
    @endforeach
</div>
```

4. **Use Translations in Views**:
```blade
<!-- resources/views/welcome.blade.php -->
<h1>{{ __('messages.welcome') }}</h1>
<p>{{ __('messages.description', ['app' => config('app.name')]) }}</p>
```

### API Integration

```php
// app/Http/Controllers/Api/ProductController.php
class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Locale is automatically set by middleware
        $locale = app()->getLocale();

        $products = Product::select([
            'id', 'name_key', 'description_key', 'price'
        ])->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => __($product->name_key),
                'description' => __($product->description_key),
                'price' => $product->price,
            ];
        });

        return response()->json($products);
    }
}
```

## 🐛 Troubleshooting

### Common Issues

**1. Translations not loading**
```bash
# Clear cache and regenerate
php artisan localization:clear-cache
php artisan localization:export
```

**2. Middleware not working**
```php
// Ensure middleware is registered in Kernel.php
\Jawabapp\Localization\Http\Middleware\Web\Localization::class,
```

**3. Routes not found**
```php
// Make sure RouteServiceProvider is configured
Route::prefix(Localization::routePrefix())
    ->middleware('web')
    ->group(base_path('routes/web.php'));
```

**4. Admin interface 404**
```bash
# Check if routes are enabled
'routes' => ['enabled' => true]
```

### Debug Mode

Enable debug logging:

```php
'fallback' => [
    'log_missing' => env('APP_DEBUG', false),
],
```

### Performance Issues

```bash
# Enable caching
php artisan config:cache

# Export translations to static files
php artisan localization:export
```

## 🔄 Upgrading from v1.x

The package uses a new database schema:

1. **Backup translations**:
```bash
php artisan localization:export
```

2. **Run migration**:
```bash
php artisan migrate
```

3. **Verify data**:
The migration automatically converts `language_code` → `locale` and extracts groups from keys.

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone repository
git clone https://github.com/jawabapp/localization
cd localization

# Install dependencies
composer install
npm install

# Run tests
composer test
```

## 🔐 Security

If you discover security vulnerabilities, please email [i.qanah@gmail.com](mailto:i.qanah@gmail.com) instead of using the issue tracker.

## 📜 License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## 👨‍💻 Credits

- **[Ibraheem Qanah](https://github.com/jawabapp)** - Creator & Maintainer
- **[All Contributors](../../contributors)** - Thank you!

## 🌟 Support

- ⭐ **Star this repo** if it helped you!
- 🐛 **[Report bugs](https://github.com/jawabapp/localization/issues)**
- 💡 **[Request features](https://github.com/jawabapp/localization/discussions)**
- 📖 **[Documentation](https://github.com/jawabapp/localization/wiki)**

---

<div align="center">

**Made with ❤️ for the Laravel community**

[Documentation](https://github.com/jawabapp/localization/wiki) • [Report Bug](https://github.com/jawabapp/localization/issues) • [Request Feature](https://github.com/jawabapp/localization/discussions)

</div>