<?php

declare(strict_types=1);

use DanDoeTech\LaravelGenericApi\Http\Controllers\GenericController;
use DanDoeTech\LaravelGenericApi\Http\Middleware\AuthorizeResource;
use Illuminate\Support\Facades\Route;

Route::prefix(config('ddt_api.prefix', 'api'))
    ->middleware(['api', AuthorizeResource::class])
    ->group(function () {
        Route::get('{resource}', [GenericController::class, 'index']);
        Route::post('{resource}', [GenericController::class, 'store']);
        Route::get('{resource}/{id}', [GenericController::class, 'show']);
        Route::patch('{resource}/{id}', [GenericController::class, 'update']);
        Route::delete('{resource}/{id}', [GenericController::class, 'destroy']);

        // Mass actions
        Route::post('{resource}/actions/{action}', [GenericController::class, 'action']);
    });
