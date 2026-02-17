# Repository Guidelines

## Project Structure & Module Organization
- `src/`: Package source (PSR-4: `Schoolees\\Psgc\\`).
- `src/Providers/PsgcServiceProvider.php`: Laravel service provider (publishes assets, loads migrations, registers commands, wraps API routes with configurable prefix).
- `src/Http/Controllers`, `src/Http/Resources`: API layer (controllers + JSON resources).
- `src/Models`, `src/Services`: Eloquent models and service layer used by controllers.
- `routes/api.php`: Package API routes (intentionally unprefixed; the provider applies the `psgc` prefix).
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
- Preserve compatibility: PHP `^8.1` and Laravel `10|11|12` (see `composer.json`).
- Prefer explicit braces for control flow; avoid one-line `foreach` bodies in new code.

## Testing Guidelines
- Run package tests with:
```bash
composer test
```
- Use `tests/Feature/*Test.php` for endpoint behavior and add regression tests for bugs you fix.
- If a change touches migrations/seeders/routes, also smoke-test in a Laravel host app.

## Commit & Pull Request Guidelines
- Use Conventional Commits for all new commits.
- Format: `type(scope): short summary` (examples: `feat(test): add regions endpoint test`, `fix(seeder): honor resources_path config`).
- Common types: `feat`, `fix`, `test`, `docs`, `refactor`, `chore`.
- PRs should include:
  - What changed and why (especially for migrations, routes, and config defaults).
  - Notes for dataset updates (file(s) under `resources/psgc/` plus the PSA source/version, also update `README.md`).
  - Steps to validate in a host app (commands run, endpoints exercised).
