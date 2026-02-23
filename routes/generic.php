<?php

use DanDoeTech\LaravelGenericApi\Http\Controllers\GenericController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('generic_api.prefix', 'api'))
    ->middleware(['api'])
    ->group(function () {
        Route::get('{resource}', [GenericController::class, 'index']);
        Route::post('{resource}', [GenericController::class, 'store']);
        Route::get('{resource}/{id}', [GenericController::class, 'show']);
        Route::patch('{resource}/{id}', [GenericController::class, 'update']);
        Route::delete('{resource}/{id}', [GenericController::class, 'destroy']);

        // Mass actions
        Route::post('{resource}/actions/{action}', [GenericController::class, 'action']);
    });
