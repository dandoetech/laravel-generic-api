<?php

declare(strict_types=1);

use DanDoeTech\LaravelGenericApi\Http\Controllers\GenericController;
use DanDoeTech\LaravelGenericApi\Http\Middleware\AuthorizeResource;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Support\Facades\Route;

$prefix = config('ddt_api.prefix', 'api');

/** @var list<string> $globalMiddleware */
$globalMiddleware = config('ddt_api.middleware', ['api']);

/** @var Registry|null $registry */
$registry = app()->bound(Registry::class) ? app(Registry::class) : null;

if ($registry === null) {
    return;
}

Route::prefix($prefix)->group(function () use ($registry, $globalMiddleware): void {
    foreach ($registry->all() as $resource) {
        $segment = $resource->getRouteSegment() ?? $resource->getKey();
        $key = $resource->getKey();

        $meta = $resource->getMeta();

        /** @var list<string> $resourceMiddleware */
        $resourceMiddleware = isset($meta['middleware']) && \is_array($meta['middleware'])
            ? $meta['middleware']
            : $globalMiddleware;

        // AuthorizeResource is always appended, never user-configurable
        $resourceMiddleware[] = AuthorizeResource::class;

        Route::middleware($resourceMiddleware)->group(function () use ($segment, $key): void {
            Route::get($segment, [GenericController::class, 'index'])
                ->defaults('resource', $key);
            Route::post($segment, [GenericController::class, 'store'])
                ->defaults('resource', $key);
            Route::get($segment . '/{id}', [GenericController::class, 'show'])
                ->defaults('resource', $key);
            Route::patch($segment . '/{id}', [GenericController::class, 'update'])
                ->defaults('resource', $key);
            Route::delete($segment . '/{id}', [GenericController::class, 'destroy'])
                ->defaults('resource', $key);
            Route::post($segment . '/actions/{action}', [GenericController::class, 'action'])
                ->defaults('resource', $key);
        });
    }
});
