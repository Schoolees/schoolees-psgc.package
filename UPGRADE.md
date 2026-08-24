# Upgrade Guide

## 1.x → 2.0

Six breaking changes. Most consumers only need to check the first two.

### 1. Laravel 10 and 11 support dropped

Requires **PHP 8.2+** and **Laravel 12 or 13**.

Every release on the Laravel 10 and 11 branches now carries an unpatched
security advisory, so Composer's default policy will not resolve them and CI
cannot install anything to test against. Rather than claim support that is
untestable, 2.0 drops it.

**On Laravel 10 or 11:** stay on `^1.1`, which continues to work. It receives no
new features.

```json
"schoolees/laravel-psgc": "^1.1"
```

### 2. The `\App\Libraries\UtilityLibrary` hook is gone

1.1.3 deprecated it in favour of a config key. It is now removed.

```php
// config/psgc.php
'response_formatter' => \App\Libraries\UtilityLibrary::class . '@dataTableResponse',
```

Anything callable works: a closure, `'Class@method'`, `[Class::class, 'method']`,
or a class that is invokable or exposes `dataTableResponse()`. Unlike the old
hook, it applies in **both** `response_format` modes.

**If you never had that class, this affects you not at all.**

### 3. `city_class` is an exact filter

It was a `LIKE` filter, so `?city_class=CC` also matched `ICC` — component
cities and independent component cities are different things, and the old
behaviour silently conflated them.

```
GET /psgc/cities?city_class=CC     # before: CC + ICC     now: CC only
```

For prefix behaviour, filter client-side or add `city_class` back to
`query_like` in your own published model.

### 4. `created_at` / `updated_at` are no longer sortable

`?order_by=created_at` now falls back to the configured default column.

The seeder stamps every row with the same timestamp, so ordering by them was
arbitrary, and neither column is indexed — on barangays that was a filesort over
~42k rows. `sort_by` is still honoured; only the column falls back.

### 5. Console commands share one namespace

`Schoolees\Psgc\Console\InstallPsgcCommand`
→ `Schoolees\Psgc\Console\Commands\InstallPsgcCommand`

Only matters if you referenced the class directly. The `psgc:install` command
signature is unchanged.

### 6. `psgc:test-publish` is removed

A debug helper that shipped by accident. Use `php artisan vendor:publish --tag=psgc`
and check the files yourself.

---

## New in 2.0

### Single-record endpoints

```
GET /psgc/regions/{code}
GET /psgc/provinces/{code}
GET /psgc/cities/{code}
GET /psgc/barangays/{code}
```

404 for an unknown code, in the same error envelope as everything else.

### Opt-in caching

PSGC data only changes when re-seeded, so lookups can be cached hard:

```php
// config/psgc.php
'cache' => [
    'enabled' => env('PSGC_CACHE', false),
    'store'   => env('PSGC_CACHE_STORE'),   // null = default store
    'ttl'     => 86400,
],
```

Invalidation is by version counter, so it works on every cache driver including
`file` and `database` — no tag support needed. The seeder flushes automatically;
`php artisan psgc:cache-clear` does it by hand.

### PostgreSQL support for name search

`query_like` filters now use `ILIKE` on PostgreSQL. `LIKE` is case-sensitive
there, so `?name=manila` previously found nothing.

### Migrations

Code columns are `varchar(20)` rather than an unbounded `varchar(255)`. Under
utf8mb4 the old width put the cities composite index at 3060 bytes against
InnoDB's 3072-byte limit.

**Existing tables are not altered.** A `change()` migration would need
`doctrine/dbal`. To shrink them yourself:

```sql
ALTER TABLE cities
  MODIFY code          VARCHAR(20) NOT NULL,
  MODIFY region_code   VARCHAR(20) NOT NULL,
  MODIFY province_code VARCHAR(20) NULL,
  MODIFY city_class    VARCHAR(20) NULL;
```

Run `php artisan migrate` either way — 1.1.2's `name` index migration still
needs to apply.
