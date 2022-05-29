# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jawabapp/localization.svg?style=flat-square)](https://packagist.org/packages/jawabapp/localization)
[![Total Downloads](https://img.shields.io/packagist/dt/jawabapp/localization.svg?style=flat-square)](https://packagist.org/packages/jawabapp/localization)
![GitHub Actions](https://github.com/jawabapp/localization/actions/workflows/main.yml/badge.svg)

This is where your description should go. Try and limit it to a paragraph or two, and maybe throw in a mention of what PSRs you support to avoid any confusion with users and contributors.

## Installation

You can install the package via composer:

```bash
composer require jawabapp/localization
```

## Usage

Add the following to App\Providers\RouteServiceProvider

```php
use Jawabapp\Localization\Libraries\Localization;

Route::prefix(Localization::routePrefix())
        ->middleware('web')
        ->group(base_path('routes/web.php'));
```

Add the following to App\Http\Kernel

```php
protected $middlewareGroups = [
    'web' => [
        \Jawabapp\Localization\Http\Middleware\Web\Localization::class,
    ],

    'api' => [
        \Jawabapp\Localization\Http\Middleware\Api\Localization::class,
    ],
];
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email i.qanah@gmail.com instead of using the issue tracker.

## Credits

-   [Ibraheem Qanah](https://github.com/jawabapp)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
