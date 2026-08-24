---
name: module-review
description: "Review a PSGC module end-to-end across its full vertical slice — model, service, controller, resource, route, migration, seeder, config key, and publish tag. Use when asked to review, audit, or check a PSGC entity (regions, provinces, cities, barangays), when adding a new PSGC entity or endpoint, when changing filtering/sorting/pagination behavior, or when touching Support/QueryOptions.php or Support/Utility.php. Verifies the layers stay in sync and probes the API's runtime behavior instead of trusting the code by inspection."
license: MIT
metadata:
  author: schoolees
---

# PSGC Module Review

A "module" in this package is one PSGC entity as a **vertical slice**. Reviewing a
module means checking every layer of that slice and, critically, checking that the
layers still agree with each other. Most defects here are not inside one file —
they live in the gap between two.

## The slice

For an entity `X` (Region, Province, City, Barangay), the slice is:

| Layer | File | Owns |
|---|---|---|
| Model | `src/Models/X.php` | table name, casts, `getSearchable()`, relations |
| Service | `src/Services/XService.php` | `allowedOrderBy` whitelist |
| Base service | `src/Services/SearchablePsgcService.php` | filter → query translation |
| Controller | `src/Http/Controllers/XController.php` | request → service args |
| Resource | `src/Http/Resources/XResources.php` | response field list |
| Route | `routes/psgc.php` | endpoint |
| Migration | `database/migrations/*_create_Xs_table.php` | columns, indexes |
| Seeder | `database/seeders/PSGCSeeder.php` | upsert keys + update columns |
| Config | `config/psgc.php` | `tables.Xs` |
| Cache | `src/Support/PsgcCache.php` | key shape, version-counter invalidation |
| Provider | `src/Providers/PsgcServiceProvider.php` | publish tags |

## Review procedure

Work through the phases in order. Do not skip phase 2 — it is where the real bugs
surface.

### Phase 1 — Cross-layer consistency

Read the whole slice, then check each invariant. Report any that fail with
`file:line` on both sides of the mismatch.

1. **Every column in `getSearchable()` exists in the migration.** A typo becomes a
   500 at runtime, never a test failure.
2. **Every column in `allowedOrderBy` exists in the migration and is indexed** (or
   is the primary key). An un-indexed sort column is a full table scan on
   barangays (~42k rows).
3. **`allowedOrderBy` and `getSearchable()` are consistent with each other.** They
   live in different files and drift independently.
4. **Every field in the Resource exists in the migration**, and every column a
   consumer needs is in the Resource.
5. **Seeder `$updates` covers every migration column except the upsert keys and
   `created_at`.** A column added to the migration but missed here silently never
   updates on re-seed.
6. **The model's `getTable()` reads `config('psgc.tables.Xs')`, the migration uses
   the same key, and the key exists in `config/psgc.php`.**
7. **Relations point at the right FK/owner-key pair** — every PSGC key is a string
   `code`, never an auto-increment id, so both arguments must be passed explicitly.

### Phase 2 — Probe the runtime behavior

Inspection misses driver-dependent and input-shape bugs. Write a scratch test
under `tests/Feature/`, run it, read the actual output, then **delete it** (keep
only tests that assert a fixed bug).

```bash
vendor/bin/phpunit --filter ProbeTest
```

Every item below is a **regression check**: each one was a real shipped bug,
fixed in 1.1.2 or 2.0, with a test now guarding it. The expected behaviour is
what is stated. If a probe disagrees, something has regressed.

| Probe | Expected | Regressed if |
|---|---|---|
| `?page=2` | Returns the second page; the paginator's own `links.next` is followable | It returns page 1 |
| `?limit=N&offset=M`, `M` not a multiple of `N` | Skips exactly `M` rows; `meta.from`/`meta.to` reflect the true offset | The offset snaps to a page boundary |
| `?is_city=true` / `false` / `1` / `0` / `yes` / `no` | Matches the intended rows, cast via the model's `$casts` | Empty on SQLite, or *inverted* on MySQL |
| Two `query_like` filters at once | AND — adding a filter narrows | Adding a filter widens the result set |
| `?name[]=a&name[]=b` | 200, filter ignored | HTTP 500 |
| `?code=` (blank) | Filter ignored, all rows | Zero rows |
| `?bogus=1` | Ignored | Crash, or leaks into the query |
| `?order_by=code;DROP TABLE x` | Falls back to the configured default column | Reaches SQL |
| `?limit=` above `max_limit` | Clamps to `max_limit` | Honours the larger value |
| `?city_class=CC` | Exact match only | Also returns `ICC` |
| `?order_by=created_at` | Falls back to the default column; `sort_by` still honoured | Sorts by the timestamp |
| `GET /{resource}/{code}` | The record, or 404 for an unknown code | 200 with an empty body, or a 500 |
| Both envelopes | Well-formed under `datatable` **and** `pagination` for the same query | Either is malformed |
| With `psgc.cache.enabled=true`, the same query twice | Second request issues no query | It re-queries, or serves a *different* query's result |

Anything genuinely new — a filter, a column, an endpoint — still needs probing
from scratch. This table is the floor, not the ceiling.

### Drivers lie differently

`composer test` runs on in-memory SQLite and **skips 9 tests**. SQLite ignores
`VARCHAR` lengths and has no native boolean, so column widths, InnoDB index
headroom, boolean filtering and the shrink migration are only real on MySQL:

```bash
docker run -d --name psgc-mysql -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=psgc_test -p 33061:3306 mysql:8

DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=33061 DB_DATABASE=psgc_test \
DB_USERNAME=root DB_PASSWORD=root vendor/bin/phpunit
```

Run this before claiming anything about booleans, column widths, or index size.
`LIKE` is case-insensitive on MySQL and case-sensitive on PostgreSQL, which is
why `query_like` uses `ILIKE` on `pgsql` — say explicitly how any change behaves
on each.

### Phase 3 — Security and input handling

- Column identifiers must only ever come from a whitelist, never from request
  input. `orWhereRaw()` in `SearchablePsgcService` interpolates `$column`
  directly — verify the whitelist path is airtight for any new column source.
- LIKE values must go through `QueryOptions::escapeLike()`; `%` and `_` are
  matched literally by contract (documented in README).
- The `filters` key echoes request input back per `psgc.filters_echo`
  (`request` / `applied` / `none`). Under the default `request` it is the raw
  query string — confirm nothing sensitive can be reflected into it.
- `catch (Throwable)` in controllers converts everything to JSON. 5xx must reach
  the log via `psgc.log_exceptions`; 4xx must not be logged as a server failure.
- Confirm `app.debug=false` does not leak exception messages for 5xx.

### Phase 4 — Package hygiene

This ships to Packagist, so host-app assumptions are bugs.

- No reference to host-app classes (e.g. `\App\...`) from package code — make it
  config-driven instead.
- Every class imported is backed by a package in `composer.json` `require`, not
  merely present transitively via `laravel/framework`.
- New publishable files are registered under both their own tag and the umbrella
  `psgc` tag in the provider.
- Migrations name indexed string columns with an explicit length — unbounded
  `string()` is 255, and utf8mb4 composite indexes approach InnoDB's 3072-byte
  key limit.
- Config keys read via `config('psgc.x', $default)` must also exist in
  `config/psgc.php`; a host app that published the config before the key existed
  will not have it.
- Adding a route or endpoint means updating `README.md` and, for dataset changes,
  noting the PSA source per `AGENTS.md`. A breaking change also needs an
  `UPGRADE.md` entry with the migration path.
- Altering an existing column uses Laravel's native `->change()` — **not**
  `doctrine/dbal`, which has been unnecessary since Laravel 11. Two traps:
  `->change()` restates the *whole* definition, so nullability must be repeated
  or a nullable column silently becomes `NOT NULL`; and narrowing a column must
  check for oversized values first, since MySQL in non-strict mode truncates
  silently, which on a primary key loses rows to duplicate keys.

### Phase 5 — Report

Group findings as:

1. **Verified bugs** — you ran it and saw the wrong output. Include the request
   and the actual response.
2. **Cross-layer mismatches** — cite `file:line` on both sides.
3. **Portability risks** — behavior that differs between SQLite (tests) and
   MySQL (production).
4. **Suggestions** — clearly separated from defects.

Prefer a small number of confirmed findings over a long list of speculation. If
you could not verify something, say so rather than asserting it.

Leave the tree clean: `git status --porcelain` must be empty of scratch files, and
`composer test` must still pass.
