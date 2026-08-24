# Repository Guidelines

## Project Structure & Module Organization
- `src/`: Package source (PSR-4: `Schoolees\\Psgc\\`).
- `src/Providers/PsgcServiceProvider.php`: Laravel service provider (publishes assets, loads migrations, registers commands, wraps API routes with configurable prefix).
- `src/Http/Controllers`, `src/Http/Resources`: API layer (controllers + JSON resources).
- `src/Models`, `src/Services`: Eloquent models and service layer used by controllers.
- `routes/psgc.php`: Package API routes (intentionally unprefixed; the provider applies the `psgc` prefix).
- `config/psgc.php`: Package configuration (prefix, middleware, ordering, pagination).
- `database/migrations`, `database/seeders`: Schema + seeders for PSGC data.
- `resources/psgc/`: PSGC dataset (JSON).

## Build, Test, and Development Commands
This is a Laravel package (not a standalone app). Typical local checks:
```bash
composer install
composer dump-autoload
composer validate
```
Smoke-test in a Laravel app that requires this package:
```bash
php artisan psgc:install --seed
php artisan psgc:publish-routes --force
```

## Coding Style & Naming Conventions
- PHP: 4-space indentation, PSR-12 conventions, `PascalCase` classes, `camelCase` methods/vars.
- Keep namespaces under `Schoolees\\Psgc\\...` and autoloaded from `src/`.
- Preserve compatibility: PHP `^8.2` and Laravel `12|13` (see `composer.json`).
  2.0 dropped Laravel 10 and 11 deliberately: every release on those branches
  carries an unpatched security advisory, so Composer will not resolve them and
  CI cannot test against them. Do not widen the constraint back.
- Prefer explicit braces for control flow; avoid one-line `foreach` bodies in new code.

## Testing Guidelines
- Run package tests with:
```bash
composer test
```
- That runs against in-memory SQLite, which **skips 9 tests**: SQLite ignores
  `VARCHAR` lengths and has no native boolean, so column widths, InnoDB index
  headroom, boolean filtering and the shrink migration can only be proven on
  MySQL. To run those locally:
```bash
docker run -d --name psgc-mysql -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=psgc_test -p 33061:3306 mysql:8

DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=33061 DB_DATABASE=psgc_test \
DB_USERNAME=root DB_PASSWORD=root vendor/bin/phpunit
```
- CI (`.github/workflows/tests.yml`) covers PHP 8.2-8.4 x Laravel 12-13 on
  SQLite, plus one MySQL 8 job. A green local run is not proof CI will pass.
- Use `tests/Feature/*Test.php` for endpoint behavior and add regression tests for bugs you fix.
- If a change touches migrations/seeders/routes, also smoke-test in a Laravel host app.

## Commit & Branching Guidelines
- **Work lands directly on `main`.** This repo does not use feature branches or
  pull requests; commit to `main` and push. `main` is the only branch, aside from
  the `release-please--branches--main` branch Release Please creates for itself.
- Because nothing gates the push, run `composer test` **before** pushing. CI runs
  on `main` but reports after the fact.
- Use Conventional Commits for all new commits.
- Format: `type(scope): short summary` (examples: `feat(test): add regions endpoint test`, `fix(seeder): honor resources_path config`).
- Common types: `feat`, `fix`, `perf`, `refactor`, `test`, `docs`, `build`, `ci`, `chore`.
- `feat`, `fix`, `perf`, `refactor` and `build` cut a release and appear in the
  changelog. `docs`, `ci`, `test` and `chore` do neither — they are hidden in
  `.release-please-config.json`, because release-please cannot show a type in the
  changelog without also making it releasable
  (googleapis/release-please#2638), and none of them change what a consumer
  installs. If you want doc work in a release, land it alongside a code commit.
- Mark breaking changes with `!` or a `BREAKING CHANGE:` footer — that is what
  produces a major version.
- Automated releases are handled by Release Please via `.github/workflows/release.yml`; do not create manual version tags.
- Keep commit history clean and conventional so automated semantic versioning stays accurate.
- A commit should say, where relevant:
  - What changed and why (especially for migrations, routes, and config defaults).
  - Notes for dataset updates (file(s) under `resources/psgc/` plus the PSA source/version, also update `README.md`).
  - Steps to validate in a host app (commands run, endpoints exercised).
- Breaking changes also need an entry in `UPGRADE.md` with the migration path.

## Releasing
- A push to `main` makes Release Please open a `chore(main): release X.Y.Z` PR.
  Merging that PR tags the release and syncs Packagist. That PR is the one
  exception to the no-PR rule, and it is created automatically.
- Before merging it, check `CHANGELOG.md` for a **duplicated entry**: if a commit
  subject is repeated by a merge commit, Release Please records the work twice.
  Delete the duplicate on the release branch first.
- Never hand-tag, and never disable Composer's security-advisory policy to make a
  build resolve.
- An already-open release PR does **not** re-evaluate itself when
  `.release-please-config.json` changes: Release Please updates the existing PR
  rather than reconsidering whether it should exist. To force a clean
  evaluation, close the PR, delete the `release-please--branches--main` branch,
  then re-run the Release workflow (`gh workflow run release.yml`).
