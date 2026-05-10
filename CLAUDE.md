# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- PHP 8.2, Laravel 12, PHPUnit 11, Laravel Pint, Vite + TailwindCSS 4
- SQLite by default (switchable via `DB_CONNECTION` in `.env`)
- Database-backed sessions, cache, and queue out of the box

## Commands

```bash
# Start everything (Laravel server + queue listener + log streaming + Vite)
composer run dev

# Run all tests
php artisan test --compact

# Run a single test file
php artisan test --compact tests/Feature/ExampleTest.php

# Filter by test name
php artisan test --compact --filter=testName

# Format modified PHP files (required after any PHP change)
vendor/bin/pint --dirty --format agent

# List routes
php artisan route:list --except-vendor

# Inspect config
php artisan config:show database.default
```

## Architecture

**Laravel 12 streamlined structure** — no `app/Console/Kernel.php` or `app/Http/Kernel.php`:
- `bootstrap/app.php` — registers middleware, exceptions, and routing files
- `bootstrap/providers.php` — application service providers
- `routes/console.php` — scheduled tasks and console commands
- Console commands in `app/Console/Commands/` are auto-discovered

**APIs:** Use Eloquent API Resources and version routes (e.g. `routes/api/v1.php`) unless existing routes don't — then follow existing convention.

**Models:** Define casts via a `casts()` method, not the `$casts` property. When creating a new model, also create its factory and seeder.

**Column migrations:** Always include all previously-defined column attributes when modifying a column — omitted attributes are dropped.

## PHP Conventions

- PHP 8 constructor property promotion: `public function __construct(public MyService $svc) {}`
- Explicit return types and parameter type hints on all methods
- Curly braces on all control structures, even single-line bodies
- Descriptive names: `isRegisteredForDiscounts`, not `discount()`
- PHPDoc with array shape types over inline comments; inline comments only for genuinely complex logic
- Enum keys in TitleCase: `FavoritePerson`, `Monthly`

## Testing

- All tests are PHPUnit classes — never Pest. Create with `php artisan make:test --phpunit {Name}`
- Most tests should be feature tests; use `--unit` only for pure logic
- Use model factories (and their states) rather than manual model setup in tests
- Run the minimal test set after a change; ask the user before running the full suite
- Never delete test files without approval

## Workflow Rules

- Run `vendor/bin/pint --dirty --format agent` before finalizing any PHP changes
- Use `php artisan make:` commands to create files (controllers, models, migrations, etc.); pass `--no-interaction`
- Do not create new top-level directories without approval
- Do not change dependencies without approval
- Do not create documentation files unless explicitly asked
- If a frontend change isn't visible, the user likely needs to run `npm run dev` or `composer run dev`
- Use `route()` with named routes for URL generation; never hardcode paths
- Prefer existing Artisan commands over custom Tinker code; use single quotes with Tinker: `php artisan tinker --execute 'App\Models\User::count();'`
