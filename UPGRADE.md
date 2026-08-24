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

**Existing tables are migrated automatically.** Run:

```bash
php artisan migrate
```

`2025_01_01_000005_shrink_psgc_code_columns` narrows the code columns on
tables created by 1.x. It uses Laravel's native `->change()`, so **no
`doctrine/dbal` is required** — that dependency stopped being necessary in
Laravel 11, and 2.0 requires 12+.

The migration is careful about a few things:

- **It refuses to truncate.** Every column is checked for oversized values
  first, and it aborts with the offending length rather than letting MySQL
  silently cut data short in non-strict mode — which, on a primary key, would
  mean losing rows to duplicate keys. PSGC codes are at most 10 characters, so
  this only trips on non-PSGC data.
- **It preserves nullability.** `cities.province_code` is `NULL` for every
  HUC/ICC, and `->change()` restates the whole column definition, so getting
  this wrong would fail against existing rows.
- **It skips columns already the right width**, so a fresh 2.0 install does not
  pay for a table rebuild it does not need.
- **It only runs on MySQL, MariaDB and PostgreSQL.** SQLite does not enforce
  `VARCHAR` lengths, so there is nothing to gain there and a full table rebuild
  to lose.

It is reversible with `php artisan migrate:rollback`.

> On a large `barangays` table (~42k rows) this rebuilds indexes and will take
> a moment. It is a normal `ALTER TABLE`, so plan it like any other.
