<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests;

use DanDoeTech\LaravelGenericApi\Providers\GenericApiServiceProvider;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\CategoryResource;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\OwnerScopedResource;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\PolicyWithHasModelResource;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\ProductResource;
use DanDoeTech\ResourceRegistry\Contracts\RegistryDriverInterface;
use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            GenericApiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        /** @var ConfigRepository $config */
        $config = $app['config'];

        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $config->set('ddt_api.prefix', 'api/v1');
        $config->set('ddt_api.pagination.per_page', 25);
        $config->set('ddt_api.pagination.max_per_page', 200);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    /**
     * Register test resources. Must be called before route registration
     * (i.e., in defineEnvironment or setUp before making HTTP requests).
     * After calling this, routes are re-loaded so explicit per-resource
     * routes pick up the registered resources.
     */
    protected function registerTestResources(): void
    {
        $driver = $this->buildDriver(new ProductResource(), new CategoryResource());

        \assert($this->app !== null);
        $this->app->singleton(Registry::class, static fn () => new Registry($driver));
        $this->reloadRoutes();
    }

    protected function registerTestResourcesWithOwnerScope(): void
    {
        $driver = $this->buildDriver(new ProductResource(), new CategoryResource(), new OwnerScopedResource());

        \assert($this->app !== null);
        $this->app->singleton(Registry::class, static fn () => new Registry($driver));
        $this->reloadRoutes();
    }

    protected function registerTestResourcesWithBroken(): void
    {
        $driver = $this->buildDriver(new ProductResource(), new CategoryResource(), new PolicyWithHasModelResource());

        \assert($this->app !== null);
        $this->app->singleton(Registry::class, static fn () => new Registry($driver));
        $this->reloadRoutes();
    }

    /**
     * Re-load the generic API routes so explicit per-resource routes
     * are registered with the current Registry contents.
     */
    private function reloadRoutes(): void
    {
        \assert($this->app !== null);
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $router->getRoutes()->refreshNameLookups();
        $routeFile = \dirname(__DIR__) . '/routes/generic.php';
        require $routeFile;
    }

    private function buildDriver(ResourceDefinitionInterface ...$resources): RegistryDriverInterface
    {
        $map = [];
        foreach ($resources as $r) {
            $map[$r->getKey()] = $r;
        }

        return new class ($map) implements RegistryDriverInterface {
            /** @param array<string, ResourceDefinitionInterface> $resources */
            public function __construct(private readonly array $resources)
            {
            }

            /** @return list<ResourceDefinitionInterface> */
            public function all(): array
            {
                return \array_values($this->resources);
            }

            public function find(string $key): ?ResourceDefinitionInterface
            {
                return $this->resources[$key] ?? null;
            }
        };
    }
}
