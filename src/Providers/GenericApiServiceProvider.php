<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Providers;

use DanDoeTech\LaravelGenericApi\Domain\EloquentRepositoryAdapter;
use DanDoeTech\LaravelGenericApi\Domain\MassAction\ConfigMassActionExecutor;
use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionExecutorInterface;
use DanDoeTech\LaravelGenericApi\Domain\QueryApplier;
use DanDoeTech\LaravelGenericApi\Domain\RepositoryAdapterInterface;
use DanDoeTech\LaravelResourceRegistry\Resolvers\ViaResolverFactory;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class GenericApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/ddt_api.php', 'ddt_api');

        $this->app->singleton(
            QueryApplier::class,
            static fn (Application $app) => new QueryApplier(
                $app->make(ViaResolverFactory::class),
                $app,
            ),
        );

        $this->app->bind(
            RepositoryAdapterInterface::class,
            static fn (Application $app) => new EloquentRepositoryAdapter(
                $app->make(Registry::class),
                $app->make(QueryApplier::class),
                $app,
            ),
        );

        $this->app->bind(MassActionExecutorInterface::class, fn () => new ConfigMassActionExecutor());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/ddt_api.php' => $this->app->configPath('ddt_api.php'),
        ], 'ddt-api-config');

        $this->loadRoutesFrom(__DIR__ . '/../../routes/generic.php');
    }
}
