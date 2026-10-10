<div class="filament-hidden">

![Filament Page Visits](https://raw.githubusercontent.com/jeffersongoncalves/filament-page-visits/1.x/art/jeffersongoncalves-filament-page-visits.png)

</div>

# Filament Page Visits

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/filament-page-visits.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-page-visits)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-page-visits/tests.yml?branch=1.x&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/filament-page-visits/actions?query=workflow%3ATests+branch%3A1.x)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-page-visits/pint.yml?branch=1.x&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/filament-page-visits/actions?query=workflow%3A%22Fix+PHP+code+styling%22+branch%3A1.x)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/filament-page-visits.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-page-visits)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/filament-page-visits.svg?style=flat-square)](LICENSE.md)

A read-only Filament admin resource for
[`jeffersongoncalves/laravel-page-visits`](https://github.com/jeffersongoncalves/laravel-page-visits) — the
headless core package that tracks page-view visits on your app's public routes (device, browser, geo, locale,
referer, UTM). This package owns no models, migrations or tracking logic of its own: it is purely the
presentation layer (a Resource, Pages, widgets) built on top of the core's `PageVisit` model.

## Compatibility

| `filament-page-visits` | `laravel-page-visits` | Filament |
| --- | --- | --- |
| [1.x](https://github.com/jeffersongoncalves/filament-page-visits/tree/1.x) | `^1.0` | v3 |
| [2.x](https://github.com/jeffersongoncalves/filament-page-visits/tree/2.x) | `^1.0` | v4 |
| [3.x](https://github.com/jeffersongoncalves/filament-page-visits/tree/3.x) | `^1.0` | v5 |

> `laravel-page-visits` requires Laravel 12 or 13 (`illuminate/contracts: ^12.0|^13.0`) on every branch — see the
> note below if a given Filament major doesn't hold up against that floor in practice.

## Screenshots

<!-- SCREENSHOTS -->
| Screenshot | Light | Dark |
|---|---|---|
| Page visit list | ![page-visit-list](screenshots/light/page-visit-list.png) | ![page-visit-list](screenshots/dark/page-visit-list.png) |
| Page visit view | ![page-visit-view](screenshots/light/page-visit-view.png) | ![page-visit-view](screenshots/dark/page-visit-view.png) |
| Metrics page | ![metrics-page](screenshots/light/metrics-page.png) | ![metrics-page](screenshots/dark/metrics-page.png) |
<!-- SCREENSHOTS -->

## What's included

- **`PageVisitResource`** — a browsable, filterable list of tracked visits (date/time, path, method, status
  code, device, browser, OS, country, referer type, bot flag), with a detail view grouped into Request, Device,
  Location, Referer & UTM, Bot/Risk flags and Fingerprint sections. The resource is intentionally read-only
  (no create, edit or delete) — visits are an append-only audit log written by the core package.
- **A dedicated Metrics page** with 14 toggleable widgets:
  - `StatsOverview` — total visits, visits today, bot %, top country.
  - `SecurityOverview` — VPN / Proxy / Tor / Datacenter %.
  - `HourlyChart` — visits per hour (last 24h).
  - `TrafficTrendChart` — visits per day (last 14 days).
  - `LongTermTrendChart` — visits per day (last 90 days), read from `laravel-page-visits` 1.1.0+'s
    `page_visit_daily_stats` aggregate table so it stays accurate even after raw `page_visits` rows are pruned by
    `page-visits:aggregate-and-prune`. Auto-hides itself on older `laravel-page-visits` installs that don't have
    that table yet.
  - `ResponseTimeTrendChart` — daily p50 / p95 / p99 response time in ms (last 90 days), read from the
    `response_time_stats` column `laravel-page-visits` 1.3.0+ writes during `page-visits:aggregate-and-prune`.
    Auto-hides itself on installs that haven't run that migration yet.
  - `DevicesChart`, `BrowsersChart`, `OperatingSystemsChart` — device / browser / OS breakdown.
  - `StatusCodesChart` — 2xx/3xx/4xx/5xx breakdown.
  - `RefererTypesChart` — direct / search / social / unknown breakdown.
  - `TopPages`, `TopReferrers`, `TopCountries`, `TopAsn` — top-10 tables (ASN/ISP included).
- pt_BR and en translations.

Publish `config/filament-page-visits.php` to change the metrics page slug or turn individual widgets off — useful
on installs with a huge `page_visits` table where a given widget's query is too costly to run on every load:

```bash
php artisan vendor:publish --tag="filament-page-visits-config"
```

```php
return [
    'metrics_page' => [
        'slug' => 'page-visits-metrics',
        'widgets' => [
            'stats_overview' => true,
            // ...
            'top_asn' => false, // turn off any widget you don't need
        ],
    ],
];
```

Table name, tracked fields, excluded paths and retention are configured separately, on `laravel-page-visits`' own
`config/page-visits.php` (see that package's README).

## Installation

You can install the package via composer:

```bash
composer require jeffersongoncalves/filament-page-visits:"^1.0"
```

This pulls in `jeffersongoncalves/laravel-page-visits` (`^1.0`) as a dependency. Publish and run its migration
per that package's own installation instructions, then register the plugin in your PanelProvider:

```php
use JeffersonGoncalves\Filament\PageVisits\FilamentPageVisitsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentPageVisitsPlugin::make(),
        ]);
}
```

## Testing

```bash
composer test        # Pest
composer analyse      # PHPStan (Larastan)
composer format        # Pint
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
