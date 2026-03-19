<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Http\Middleware;

use DanDoeTech\LaravelGenericApi\Http\Middleware\AuthorizeResource;
use DanDoeTech\LaravelGenericApi\Providers\GenericApiServiceProvider;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\AdminOnlyResource;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\CategoryResource;
use DanDoeTech\LaravelGenericApi\Tests\Fixtures\ProductResource;
use DanDoeTech\ResourceRegistry\Contracts\RegistryDriverInterface;
use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Config\Repository as ConfigRepository;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests that per-resource middleware configuration via meta works correctly:
 * - Resource with custom middleware gets those middleware
 * - Resource without middleware meta uses global default
 * - AuthorizeResource is always appended
 */
final class PerResourceMiddlewareTest extends OrchestraTestCase
{
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
        $config->set('ddt_api.middleware', ['api']);
    }

    private function registerResources(): void
    {
        $resources = [
            new ProductResource(),
            new CategoryResource(),
            new AdminOnlyResource(),
        ];

        $map = [];
        foreach ($resources as $r) {
            $map[$r->getKey()] = $r;
        }

        $driver = new class ($map) implements RegistryDriverInterface {
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

        \assert($this->app !== null);
        $this->app->singleton(Registry::class, static fn () => new Registry($driver));

        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $router->getRoutes()->refreshNameLookups();
        $routeFile = \dirname(__DIR__, 3) . '/routes/generic.php';
        require $routeFile;
    }

    /**
     * @return list<string>
     */
    private function getMiddlewareForRoute(string $uri, string $method = 'GET'): array
    {
        \assert($this->app !== null);

        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $routes = $router->getRoutes();

        $route = $routes->match(
            \Illuminate\Http\Request::create($uri, $method),
        );

        /** @var list<string> $middleware */
        $middleware = $route->gatherMiddleware();

        return $middleware;
    }

    #[Test]
    public function resource_with_custom_middleware_uses_meta_middleware(): void
    {
        $this->registerResources();

        $middleware = $this->getMiddlewareForRoute('/api/v1/admin-category');

        $this->assertContains('auth:sanctum', $middleware);
        $this->assertContains('can:admin', $middleware);
        // Global default 'api' should NOT be present — custom overrides it
        $this->assertNotContains('api', $middleware);
    }

    #[Test]
    public function resource_without_middleware_meta_uses_global_default(): void
    {
        $this->registerResources();

        $middleware = $this->getMiddlewareForRoute('/api/v1/category');

        $this->assertContains('api', $middleware);
        // Custom middleware should NOT be present on this resource
        $this->assertNotContains('auth:sanctum', $middleware);
        $this->assertNotContains('can:admin', $middleware);
    }

    #[Test]
    public function authorize_resource_is_always_appended(): void
    {
        $this->registerResources();

        // Check on resource with custom middleware
        $adminMiddleware = $this->getMiddlewareForRoute('/api/v1/admin-category');
        $this->assertContains(AuthorizeResource::class, $adminMiddleware);

        // Check on resource with default middleware
        $categoryMiddleware = $this->getMiddlewareForRoute('/api/v1/category');
        $this->assertContains(AuthorizeResource::class, $categoryMiddleware);

        // Check on product resource
        $productMiddleware = $this->getMiddlewareForRoute('/api/v1/product');
        $this->assertContains(AuthorizeResource::class, $productMiddleware);
    }

    #[Test]
    public function custom_middleware_applies_to_all_http_methods(): void
    {
        $this->registerResources();

        $getMiddleware = $this->getMiddlewareForRoute('/api/v1/admin-category', 'GET');
        $postMiddleware = $this->getMiddlewareForRoute('/api/v1/admin-category', 'POST');

        $this->assertContains('auth:sanctum', $getMiddleware);
        $this->assertContains('can:admin', $getMiddleware);
        $this->assertContains('auth:sanctum', $postMiddleware);
        $this->assertContains('can:admin', $postMiddleware);
    }

    #[Test]
    public function different_resources_can_have_different_middleware(): void
    {
        $this->registerResources();

        $adminMiddleware = $this->getMiddlewareForRoute('/api/v1/admin-category');
        $categoryMiddleware = $this->getMiddlewareForRoute('/api/v1/category');

        // admin-category has custom middleware
        $this->assertContains('auth:sanctum', $adminMiddleware);
        $this->assertContains('can:admin', $adminMiddleware);
        $this->assertNotContains('api', $adminMiddleware);

        // category uses global default
        $this->assertContains('api', $categoryMiddleware);
        $this->assertNotContains('auth:sanctum', $categoryMiddleware);

        // Both have AuthorizeResource
        $this->assertContains(AuthorizeResource::class, $adminMiddleware);
        $this->assertContains(AuthorizeResource::class, $categoryMiddleware);
    }
}
