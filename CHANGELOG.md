# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Explicit route registration per resource (replaces dynamic route-for-all approach)
- `QueryApplier` with typed filter operators: `eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `like`, `between`
- Full-text search via `?search=term` across searchable fields
- `HasOwnerScope` query scoping — auto-applies `WHERE owner_key = auth()->id()` for owner-scoped resources
- `RegistryActionExecutor` resolving action handlers from resource definitions (replaces `ConfigMassActionExecutor`)
- `ActionHandlerInterface` contract for custom action handlers
- `CloneActionHandler` — built-in handler for the `clone` action (duplicates records)
- Configurable middleware via `ddt_api.middleware` config key
- `InvalidCriteriaException` returning structured 422 responses for unknown filter/sort fields and operators
- `DB::transaction` wrapping create, update, and delete operations
- Query profiles resolved from Resource class with `preFilter` support
- LIKE filtering for string fields, exact match for numeric fields
- Support for new FieldTypes (Text, Email, Url, Enum) in search and filtering

### Changed
- Uses `EloquentComputedResolverInterface` (renamed from `EloquentComputedResolver`)

### Deprecated
- Config-based `query_profiles` — define profiles on the Resource class via `queryProfile()` instead
- Config-based `scopes` — use `HasOwnerScope` on the Resource class instead (will be removed in v1.0)

## [0.1.0] - 2026-03-15

### Added
- `GenericController` handling index, show, store, update, destroy for all registered resources
- Auto-registered routes with configurable prefix (default: `api/v1`)
- `AuthorizeResource` middleware integrating with Laravel Gates/Policies via `HasPolicy`
- `StoreRequest` and `UpdateRequest` deriving validation rules from resource definitions (PATCH-aware)
- `EloquentRepositoryAdapter` with CRUD operations and computed field query resolution
- `QueryCriteria` parser for `?filter[field]=value&sort=-field&page=N&perPage=N` query parameters
- Filtering and sorting on both regular and computed fields
- Configurable query profiles per resource (`?profile=admin`)
- Pagination with per-page and max-per-page limits
- Mass action system: `ActionRequest`, `MassActionHandlerInterface`, `ConfigMassActionExecutor`
- `ResourceJson` response formatter with consistent `{data, meta}` envelope
- `ddt_api.php` config with prefix, pagination, query_profiles, scopes, and actions
