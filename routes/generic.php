<?php

declare(strict_types=1);

use DanDoeTech\LaravelGenericApi\Http\Controllers\GenericController;
use DanDoeTech\LaravelGenericApi\Http\Middleware\AuthorizeResource;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Support\Facades\Route;

$prefix = config('ddt_api.prefix', 'api');

/** @var Registry|null $registry */
$registry = app()->bound(Registry::class) ? app(Registry::class) : null;

if ($registry === null) {
    return;
}

Route::prefix($prefix)
    ->middleware(['api', AuthorizeResource::class])
    ->group(function () use ($registry): void {
        foreach ($registry->all() as $resource) {
            $segment = $resource->getRouteSegment() ?? $resource->getKey();
            $key = $resource->getKey();

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
        }
    });
