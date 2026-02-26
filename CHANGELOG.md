# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-02-26

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
