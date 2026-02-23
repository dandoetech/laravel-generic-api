# Laravel Generic API

Registry-driven CRUD proxy for Laravel: validation, policies, filtering, and consistent JSON responses.

## Install
```
composer require dandoetech/laravel-generic-api  
php artisan vendor:publish --tag=generic-api-config
```

## Configure
- Map resources to Eloquent models in `config/generic_api.php` (`resource_to_model`).
- Optionally whitelist `filterable` / `sortable` fields per resource.

## Use
- `GET    /api/{resource}`
- `GET    /api/{resource}/{id}`
- `POST   /api/{resource}`
- `PATCH  /api/{resource}/{id}`
- `DELETE /api/{resource}/{id}`

Responses:
- list: `{ data: [...], meta: { page, perPage, total, lastPage } }`
- item: `{ data: {...} }`

## Security & Validation
- Validation is derived from your `resource-registry` field rules and nullability.
- Mass-assignment protection via allowed field list from registry.
- Authorization via policies/gates (`viewAny`, `view`, `create`, `update`, `delete`).


## Development

```bash
composer install
composer qa         # runs cs:check, phpstan, tests
composer cs:fix     # auto-fix coding style
composer test       # run test suite
composer test:coverage
```

## Quality Gates

- PSR-12 via PHP-CS-Fixer (with strict types, imports, trailing commas)
- PHPStan level `max`
- PHPUnit 11 with coverage (Clover + HTML)
- GitHub Actions: tests (PHP 8.2 / 8.3 / 8.4 / 8.5), static analysis, cache
- **Codacy** coverage upload (needs `CODACY_PROJECT_TOKEN` secret)
- **SonarCloud** analysis (needs `SONAR_TOKEN` secret)

## Releasing

- Create a tag like `v0.1.0`
- Push to GitHub — Packagist auto-updates if hooked, or submit manually

## Rename This Skeleton

- Replace vendor & package in `composer.json` (`dandoetech/package-skeleton`)
- Replace namespace `DanDoeTech\PackageSkeleton\` in `/src` and `/tests`
- Search/replace badges in `README.md`
- Optional: adjust `LICENSE` owner
