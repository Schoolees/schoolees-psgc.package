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

Probe each of these against the endpoint under review:

- **`?page=2`** — does it actually return page 2? The controller reads `offset`,
  not `page`; confirm the paginator's own `links.next` is followable.
- **`?limit=N&offset=M` where `M` is not a multiple of `N`** — `pageFromOffset()`
  quantizes to page boundaries, so a partial offset is silently rounded down.
- **Boolean columns** (`is_city`) — `?is_city=true` sends the *string* `"true"`.
  Check the row count is non-zero and correct. This behaves differently on SQLite
  (matches nothing) and MySQL (`'true'` casts to `0`, matching the opposite).
- **Two `query_like` filters at once** — they are grouped in a single `orWhere`,
  so they OR rather than AND. Confirm the intended semantics.
- **Array input**: `?name[]=a&name[]=b` — must not 500.
- **Empty input**: `?code=` — an empty exact filter matches no rows.
- **Unknown params**: `?bogus=1` — must be ignored, not crash.
- **Hostile `order_by`**: `?order_by=code;DROP TABLE x` — must fall back to the
  configured default, never reach SQL.
- **`?limit=` above `max_limit`** — must clamp.
- **Both envelopes**: run once with `psgc.response_format=datatable` and once with
  `pagination`; both must be well-formed for the same query.

Tests run on SQLite in-memory (`tests/TestCase.php`) but the package targets
MySQL/MariaDB. For anything touching booleans, `LIKE`, or collation, state
explicitly how the behavior differs on MySQL and PostgreSQL — `LIKE` is
case-insensitive on MySQL and case-sensitive on PostgreSQL.

### Phase 3 — Security and input handling

- Column identifiers must only ever come from a whitelist, never from request
  input. `orWhereRaw()` in `SearchablePsgcService` interpolates `$column`
  directly — verify the whitelist path is airtight for any new column source.
- LIKE values must go through `QueryOptions::escapeLike()`; `%` and `_` are
  matched literally by contract (documented in README).
- The `filters` key echoes request input back to the client — confirm nothing
  unvalidated or sensitive is reflected.
- `catch (Throwable)` in controllers converts everything to JSON. Confirm
  non-HTTP exceptions are logged, or they vanish without a trace.
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
  noting the PSA source per `AGENTS.md`.

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
