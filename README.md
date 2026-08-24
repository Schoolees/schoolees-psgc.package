# 📍PSGC Laravel Package

[![Tests](https://github.com/Schoolees/schoolees-psgc.package/actions/workflows/tests.yml/badge.svg)](https://github.com/Schoolees/schoolees-psgc.package/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/schoolees/laravel-psgc.svg?style=flat-square)](https://packagist.org/packages/schoolees/laravel-psgc)
[![Total Downloads](https://img.shields.io/packagist/dt/schoolees/laravel-psgc.svg?style=flat-square)](https://packagist.org/packages/schoolees/laravel-psgc)
[![License](https://img.shields.io/packagist/l/schoolees/laravel-psgc.svg?style=flat-square)](LICENSE)

A Laravel package for handling **Philippine Standard Geographic Code (PSGC)** data — including Regions, Provinces, Cities/Municipalities, and Barangays.  

It comes complete with **migrations**, **seeders**, **JSON data**, **Eloquent models**, **services**, **controllers**, **API resources**, and **routes** following clean Laravel architecture.

---

## 📦 Features
- Full PSGC database structure (Regions, Provinces, Cities, Barangays)
- JSON PSGC dataset in `resources/psgc/`
- Database migrations and seeders for an initial data load
- Eloquent models with relationships and searchable fields
- Service layer for clean business logic
- REST API controllers & resources
- Artisan command to regenerate PSGC models
- Ready-to-use API routes for all PSGC endpoints


## 📋 Requirements
- PHP >= 8.1
- Laravel 10.x to 13.x
- Laravel 13 requires PHP 8.3+ (per Laravel's support policy)
- MySQL / MariaDB

## 🤝 Contributing
Repository development/testing notes are in `CONTRIBUTING.md`.


## ⚙️ Installation
**Require the package via Composer:**
```bash
composer require schoolees/laravel-psgc
```

**Quick installation:**
```bash
php artisan psgc:install --seed
php artisan psgc:install --force --seed # Overwrite previously published package files
```

By default, the package auto-registers routes at `/{PSGC_API_PREFIX}` and keeps the same URL shape if you later switch to published routes.

**Publishing assets (optional):**
```bash
# Config
php artisan vendor:publish --tag=psgc-config

# Seeders
php artisan vendor:publish --tag=psgc-seeders

# Routes
php artisan psgc:publish-routes
php artisan psgc:publish-routes --force # Overwrite if re-running

# Resources
php artisan vendor:publish --tag=psgc-resources
php artisan vendor:publish --tag=psgc-resources-classes
```
**Generate PSGC models (optional):**
```bash
php artisan make:psgc-models
php artisan make:psgc-models --force # Overwrite existing models
php artisan make:psgc-models --softdeletes # Include SoftDeletes trait
```

**Example Request:**
```php
# Get all Regions
GET /psgc/regions

# Get Cities in the National Capital Region (NCR)
GET /psgc/cities?region_code=1300000000

# Get the City of Manila
GET /psgc/cities?code=1380600000

# Get Barangays in the City of Manila
GET /psgc/barangays?city_code=1380600000
```
**Example JSON Response:**
```json
{
  "code": 200,
  "draw": 1,
  "recordsFiltered": 1,
  "recordsTotal": 1,
  "recordsPerPage": 10,
  "data": [
    {
      "code": "1380600000",
      "name": "City of Manila",
      "province_code": null,
      "region_code": "1300000000"
    }
  ],
  "filters": {
    "code": "1380600000"
  }
}
```

**Pagination response mode**

`response_format` decides the envelope. The example above is the default `datatable` shape; setting it to `pagination` returns the generic Laravel resource payload instead, with the same `filters` echo:

```json
{
  "data": [ { "code": "1380600000", "name": "City of Manila" } ],
  "meta": { "current_page": 1, "per_page": 10, "total": 1 },
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "filters": { "code": "1380600000" }
}
```

Errors answer as `{"code": <status>, "error": "<message>"}`. A 5xx message is replaced with `Server Error` unless `app.debug` is on, so an internal exception is never echoed to an API caller.

**Paging through results**

| Parameter | Meaning |
| --- | --- |
| `limit` | Rows per page. Defaults to `psgc.paginate`, capped by `psgc.max_limit`. |
| `page` | 1-based page number. Takes precedence over `offset`. |
| `offset` | Row offset, honoured exactly — `?limit=10&offset=5` starts at the 6th row. |

The `links` emitted in `pagination` mode are page-based and can be followed directly:

```php
GET /psgc/barangays?limit=25          // rows 1-25
GET /psgc/barangays?limit=25&page=2   // rows 26-50
GET /psgc/barangays?limit=25&offset=5 // rows 6-30
```

**Customising the response envelope**

Set `psgc.response_formatter` to take over the envelope. It receives the resource collection and returns an array, and it applies in both `response_format` modes:

```php
// config/psgc.php
'response_formatter' => fn ($collection) => [
    'items' => $collection->collection,
    'total' => $collection->total(),
],
```

It accepts a callable, `'Class@method'`, `[Class::class, 'method']`, or a class name that is invokable or exposes `dataTableResponse()`.

> Earlier versions looked for an `\App\Libraries\UtilityLibrary::dataTableResponse()` in the host app. That fallback still works so existing apps keep their envelope, but it is deprecated in favour of this config key and will be removed in 2.0.

**Controlling the `filters` echo**

`filters_echo` decides what the `filters` key contains:

| Value | Behaviour |
| --- | --- |
| `request` | The whole query string, echoed back unvalidated. The default, kept for compatibility. |
| `applied` | Only the filters that actually reached the query — unknown, blank and malformed parameters are omitted. |
| `none` | An empty object. |

**Filtering and Searching**

You can filter results by passing query parameters. Refer to the `getSearchable()` method on each model for available filterable fields.

**Example: Get cities in the National Capital Region (NCR)**
```php
GET /psgc/cities?region_code=1300000000
```

**Example: Search for a city by name**
```php
GET /psgc/cities?name=Manila
```

`query_like` filters use SQL `LIKE` under the hood, but `%` and `_` in your search value are escaped and matched literally — they are not treated as wildcards.

Filter semantics:

- **Filters combine with `AND`.** Adding a parameter always narrows the result set, never widens it — `?name=Bacarra&city_class=CC` matches rows that satisfy both.
- **Blank parameters are ignored.** `?code=` is treated as "no filter", so an unfilled form field does not zero out the results.
- **Booleans accept the usual spellings.** For a boolean-cast column such as `is_city`, any of `true`/`false`/`1`/`0`/`yes`/`no` work. A value that is not a recognisable boolean is ignored rather than guessed at.
- **Array and unrecognised parameters are ignored.** `?name[]=a&name[]=b` and `?bogus=1` are dropped rather than erroring.

## 🔍 Searchable Fields
Each model has a `getSearchable()` method to define searchable columns for filtering via API.

**Example for a City model:**
```php
public function getSearchable(): array
{
    return [
        'query' => ['code', 'region_code', 'province_code', 'is_city'],
        'query_like' => ['name', 'city_class'],
    ];
}
```

## 🧩 Service Layer
The package follows the Service-Controller-Resource pattern for clean, maintainable code.

**Example:**
```php
use Schoolees\Psgc\Support\QueryOptions;

$results = $this->cityService->getCities(
    request()->all(),                                       // filters
    QueryOptions::stringOrNull(request()->input('order_by')),
    QueryOptions::stringOrNull(request()->input('sort_by')),
    QueryOptions::intOrNull(request()->input('limit')),
    QueryOptions::intOrNull(request()->input('offset')) ?? 0,
    QueryOptions::intOrNull(request()->input('page'))
);
```

## Optional .env overrides
**To customize API prefix:**
```env
PSGC_API_PREFIX=geo # Will change /psgc/regions to /geo/regions.
```

**Route strategy (important):**
- Default: package auto-registers routes via service provider.
- Published routes are appended to `routes/web.php` with the configured PSGC prefix and middleware so they keep the same `/{prefix}/*` URLs.
- If you want to use published `routes/psgc.php`, disable package route registration to avoid duplicates:
```php
// config/psgc.php
'register_package_routes' => false,
```

**Configuration reference (`config/psgc.php`):**

| Key | Default | What it does |
| --- | --- | --- |
| `api_prefix` | `psgc` | URL prefix for every route (`PSGC_API_PREFIX`). |
| `middleware` | `['api']` | Middleware applied to the package's route group. |
| `register_package_routes` | `true` | Set to `false` when using published `routes/psgc.php`, to avoid duplicates. |
| `append_include_on_publish` | `false` | Whether `psgc:publish-routes` appends the include to `routes/web.php`. |
| `response_format` | `datatable` | `datatable` or `pagination` (see above). |
| `response_formatter` | `null` | Optional hook to shape the envelope yourself (see below). |
| `filters_echo` | `request` | `request`, `applied`, or `none` (see below). |
| `log_exceptions` | `true` | Also write 5xx responses to the log. |
| `paginate` | `10` | Default page size. |
| `max_limit` | `100` | Caps `?limit=`. |
| `order_by` | `name` | Default sort column, used when `?order_by=` is absent or not allow-listed. |
| `sort_by` | `asc` | Default sort direction (`asc` or `desc`). |
| `tables` | region/province/city/barangay names | Table names, if yours differ. |
| `resources_path` | `base_path('resources/psgc')` | Where the JSON dataset is read from when seeding. |
| `truncate_before_seed` | `true` | Whether a seed empties the tables first. |

## 📜 License
This package is open-sourced software licensed under the MIT license.

## 🏢 About
Developed & maintained by Schoolees as part of the Schoolees Educational Suite.

## 🔢 Code Format
Every `code` (and every `region_code`/`province_code`/`city_code` reference) is a zero-padded, fixed-width **10-character string**, per the current PSA PSGC format, e.g. National Capital Region is `1300000000` and Region I (Ilocos Region) is `0100000000`. Codes are strings, not integers — always compare/store them as strings so leading zeros aren't lost.

## 📊 Data Source
This package uses the official **Philippine Standard Geographic Code (PSGC)** dataset published by the **Philippine Statistics Authority (PSA)**.

Latest Dataset Used:
[📄 PSGC 2Q 2025 Publication Datafile (Excel)](https://psa.gov.ph/system/files/scd/PSGC-2Q-2025-Publication-Datafile.xlsx)

Attribution:
Philippine Statistics Authority — *Philippine Standard Geographic Code (PSGC)*

Update Frequency:
Quarterly (based on PSA publication schedule)
