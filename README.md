# 📍PSGC Laravel Package

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
$results = $this->cityService->getCities(
    request()->all(),
    request()->input('order_by', 'name'),
    request()->input('sort_by', 'desc'),
    request()->input('limit', 10),
    request()->input('offset', 0)
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

**Pagination safety override:**
```php
// config/psgc.php
'max_limit' => 100, // caps ?limit=...
```

**Response format override:**
```php
// config/psgc.php
'response_format' => 'datatable', // or 'pagination'
```

## 📜 License
This package is open-sourced software licensed under the MIT license.

## 🏢 About
Developed & maintained by Schoolees as part of the Schoolees Educational Suite.

## 📊 Data Source
This package uses the official **Philippine Standard Geographic Code (PSGC)** dataset published by the **Philippine Statistics Authority (PSA)**.

Latest Dataset Used:
[📄 PSGC 2Q 2025 Publication Datafile (Excel)](https://psa.gov.ph/system/files/scd/PSGC-2Q-2025-Publication-Datafile.xlsx)

Attribution:
Philippine Statistics Authority — *Philippine Standard Geographic Code (PSGC)*

Update Frequency:
Quarterly (based on PSA publication schedule)
