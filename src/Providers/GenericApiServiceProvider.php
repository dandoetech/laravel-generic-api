<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Providers;

use DanDoeTech\LaravelGenericApi\Domain\EloquentRepositoryAdapter;
use DanDoeTech\LaravelGenericApi\Domain\MassAction\ConfigMassActionExecutor;
use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionExecutorInterface;
use DanDoeTech\LaravelGenericApi\Domain\RepositoryAdapterInterface;
use DanDoeTech\LaravelResourceRegistry\Resolvers\ViaResolverFactory;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class GenericApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/generic_api.php', 'generic_api');

        $this->app->bind(
            RepositoryAdapterInterface::class,
            static fn (Application $app) => new EloquentRepositoryAdapter(
                $app->make(Registry::class),
                $app->make(ViaResolverFactory::class),
                $app,
            ),
        );

        $this->app->bind(MassActionExecutorInterface::class, fn () => new ConfigMassActionExecutor());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/generic_api.php' => $this->app->configPath('generic_api.php'),
        ], 'generic-api-config');

        $this->loadRoutesFrom(__DIR__ . '/../../routes/generic.php');
    }
}
