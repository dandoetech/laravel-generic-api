# Laravel Generic API

> **Pre-release** — Architecture by senior tech lead, implementation largely AI-assisted with human review. Not fully reviewed. Architecture may change before v1.0.0.

Registry-driven CRUD controller for Laravel. One controller handles all resources — validation, authorization, filtering, sorting, pagination, and computed fields are derived from the Resource Registry.

## Installation

```bash
composer require dandoetech/laravel-generic-api
```

The service provider is auto-discovered. Publish the config:

```bash
php artisan vendor:publish --tag=ddt-api-config
```

Requires [`dandoetech/laravel-resource-registry`](https://github.com/dandoetech/laravel-resource-registry).

## Quick Start

Define a resource with `HasEloquentModel` (and optionally `HasPolicy`):

```php
class ProductResource extends Resource implements HasEloquentModel, HasPolicy
{
    public function model(): string { return \App\Models\Product::class; }
    public function policy(): string { return \App\Policies\ProductPolicy::class; }

    protected function define(ResourceBuilder $b): void
    {
        $b->key('product')
          ->label('Product')
          ->timestamps()
          ->field('name', FieldType::String, nullable: false, rules: ['required', 'max:120'])
          ->field('price', FieldType::Float, nullable: false, rules: ['required', 'numeric', 'min:0'])
          ->field('category_id', FieldType::Integer, nullable: false)
          ->belongsTo('category', foreignKey: 'category_id')
          ->computed('category_name', FieldType::String, via: 'category.name')
          ->computed('orders_count', FieldType::Integer, via: 'count:orders')
          ->filterable(['name', 'price', 'category_id', 'category_name'])
          ->sortable(['name', 'price', 'created_at', 'orders_count'])
          ->action('create')
          ->action('update')
          ->action('delete');
    }
}
```

Routes are registered automatically. The API is ready:

```
GET    /api/v1/product                          → list (paginated)
GET    /api/v1/product?filter[name]=Phone       → filter
GET    /api/v1/product?sort=-price,name         → sort (- = desc)
GET    /api/v1/product?page=2&perPage=50        → paginate
GET    /api/v1/product/123                      → show
POST   /api/v1/product                          → create
PATCH  /api/v1/product/123                      → update
DELETE /api/v1/product/123                      → delete
POST   /api/v1/product/actions/bulk-delete      → mass action
```

## Routes

All routes are prefixed with `config('ddt_api.prefix')` (default: `api/v1`) and use the `api` middleware plus `AuthorizeResource`.

| Method | Route | Action |
|---|---|---|
| GET | `{resource}` | List with pagination, filtering, sorting |
| POST | `{resource}` | Create |
| GET | `{resource}/{id}` | Show |
| PATCH | `{resource}/{id}` | Update (partial) |
| DELETE | `{resource}/{id}` | Delete |
| POST | `{resource}/actions/{action}` | Execute mass action |

## Responses

List:
```json
{
  "data": [
    { "id": 1, "name": "Phone", "price": 599.99, "category_name": "Electronics" }
  ],
  "meta": { "page": 1, "perPage": 25, "total": 42, "lastPage": 2 }
}
```

Single item:
```json
{
  "data": { "id": 1, "name": "Phone", "price": 599.99, "category_name": "Electronics" }
}
```

## Query Syntax

Only fields listed in `filterable()` and `sortable()` on the resource are accepted. Unknown fields return a `422 Unprocessable Entity`. This includes computed fields — filtering and sorting on `category_name` or `orders_count` works out of the box.

### Filtering

```
?filter[name]=Widget                           String fields: auto LIKE match
?filter[category_id]=3                         Non-string fields: exact match
?filter[price][gte]=10&filter[price][lte]=100  Operator syntax
?filter[price][between]=10,100                 Between shorthand (comma-separated)
```

**Operators:** `eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `like`, `between`

### Sorting

```
?sort=name              Ascending
?sort=-price            Descending (prefix with -)
?sort=-created_at,name  Multiple fields (comma-separated)
```

### Search

```
?search=widget          OR LIKE across all searchable() fields
```

Search applies `LIKE %term%` for string fields and exact match for non-string fields.

### Pagination

```
?page=2&perPage=50      Default: 25, max: 200 (configurable)
```

### Query Profiles

Override filterable/sortable/searchable per context. Profiles can be defined on the Resource class:

```php
$b->queryProfile('admin',
    filterable: ['name', 'price', 'category_id', 'created_at'],
    sortable: ['name', 'price', 'created_at', 'orders_count'],
);

// With preFilter — automatically applied WHERE conditions:
$b->queryProfile('active',
    filterable: ['name', 'price'],
    preFilter: ['status' => 'active'],
);
```

Activate with `?profile=admin` or `?profile=active`.

## Authorization

The `AuthorizeResource` middleware handles authorization automatically for resources that implement `HasPolicy`:

| Request | Gate ability | Subject |
|---|---|---|
| GET (list) | `viewAny` | Model class |
| GET (single) | `view` | Model instance |
| POST | `create` | Model class |
| PATCH | `update` | Model instance |
| DELETE | `delete` | Model instance |
| Mass action | `action` | Model class + action name |

Resources without `HasPolicy` skip authorization entirely.

## Validation

Validation rules are derived from the resource definition:

- **POST (create):** Field rules applied directly. Non-nullable fields without explicit rules get `required`.
- **PATCH (update):** All rules wrapped with `sometimes` (PATCH semantics — only validate provided fields).

## Configuration

`config/ddt_api.php`:

```php
return [
    // Route prefix for all endpoints
    'prefix' => env('GENERIC_API_PREFIX', 'api/v1'),

    // Pagination defaults
    'pagination' => [
        'per_page' => 25,
        'max_per_page' => 200,
    ],

    // Named query profiles per resource
    'query_profiles' => [
        // 'product' => [
        //     'admin' => ['filterable' => [...], 'sortable' => [...]],
        // ],
    ],

    // Mass action handlers
    'actions' => [
        // 'product' => [
        //     'bulk-delete' => App\Actions\ProductBulkDelete::class,
        // ],
    ],
];
```

## Testing

```bash
composer install
composer test        # PHPUnit (Orchestra Testbench)
composer qa          # cs:check + phpstan + test
```

## License

MIT
