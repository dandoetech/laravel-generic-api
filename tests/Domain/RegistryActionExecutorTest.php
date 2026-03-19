<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Domain;

use DanDoeTech\LaravelGenericApi\Domain\Actions\ActionHandlerInterface;
use DanDoeTech\LaravelGenericApi\Domain\Actions\RegistryActionExecutor;
use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionRequest;
use DanDoeTech\ResourceRegistry\Definition\ActionDefinition;
use DanDoeTech\ResourceRegistry\Registry\ArrayRegistryDriver;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegistryActionExecutorTest extends TestCase
{
    #[Test]
    public function calls_handler_for_custom_action(): void
    {
        $handler = new class () implements ActionHandlerInterface {
            /** @var array<string, mixed> */
            public array $lastCall = [];

            public function handle(string $resource, array $ids, array $payload, ?Authenticatable $user): array
            {
                $this->lastCall = [
                    'resource' => $resource,
                    'ids'      => $ids,
                    'payload'  => $payload,
                ];

                return ['affected' => \count($ids)];
            }
        };

        $registry = $this->buildRegistry('order', [
            new ActionDefinition(name: 'activate', handler: $handler::class),
        ]);

        $container = new Container();
        $container->instance($handler::class, $handler);

        $executor = new RegistryActionExecutor($registry, $container);

        $result = $executor->execute(
            new MassActionRequest('order', 'activate', [1, 2, 3], ['reason' => 'test']),
        );

        self::assertSame(['affected' => 3], $result);
        self::assertSame('order', $handler->lastCall['resource']);
        self::assertSame([1, 2, 3], $handler->lastCall['ids']);
        self::assertSame(['reason' => 'test'], $handler->lastCall['payload']);
    }

    #[Test]
    public function throws_for_unknown_resource(): void
    {
        $registry = $this->buildRegistry('order', []);

        $executor = new RegistryActionExecutor($registry, new Container());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown resource 'nonexistent'");

        $executor->execute(new MassActionRequest('nonexistent', 'activate', [1]));
    }

    #[Test]
    public function throws_for_unknown_action(): void
    {
        $registry = $this->buildRegistry('order', [
            new ActionDefinition(name: 'create'),
        ]);

        $executor = new RegistryActionExecutor($registry, new Container());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown action 'activate' on resource 'order'");

        $executor->execute(new MassActionRequest('order', 'activate', [1]));
    }

    #[Test]
    public function throws_for_action_without_handler(): void
    {
        $registry = $this->buildRegistry('order', [
            new ActionDefinition(name: 'create'),
        ]);

        $executor = new RegistryActionExecutor($registry, new Container());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Action 'create' on resource 'order' has no handler defined");

        $executor->execute(new MassActionRequest('order', 'create', [1]));
    }

    #[Test]
    public function clone_action_uses_builtin_handler_without_explicit_handler(): void
    {
        // 'clone' action without a handler class should NOT throw — it's a built-in
        $registry = $this->buildRegistry('order', [
            new ActionDefinition(name: 'clone'),
        ]);

        $executor = new RegistryActionExecutor($registry, new Container());

        // This will throw because CloneActionHandler needs a real model (integration test covers that),
        // but it should NOT throw "no handler defined" — it should attempt the built-in handler
        try {
            $executor->execute(new MassActionRequest('order', 'clone', [1]));
            self::fail('Expected an exception');
        } catch (\InvalidArgumentException $e) {
            // Should NOT be "has no handler defined"
            self::assertStringNotContainsString('has no handler defined', $e->getMessage());
            // It will fail because 'order' doesn't implement HasEloquentModel, which is expected
            self::assertStringContainsString('HasEloquentModel', $e->getMessage());
        }
    }

    #[Test]
    public function throws_for_handler_not_implementing_interface(): void
    {
        $badHandler = new class () {
            // Does not implement ActionHandlerInterface
        };

        $registry = $this->buildRegistry('order', [
            new ActionDefinition(name: 'activate', handler: $badHandler::class),
        ]);

        $container = new Container();
        $container->instance($badHandler::class, $badHandler);

        $executor = new RegistryActionExecutor($registry, $container);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $executor->execute(new MassActionRequest('order', 'activate', [1]));
    }

    /**
     * @param list<ActionDefinition> $actions
     */
    private function buildRegistry(string $key, array $actions): Registry
    {
        $driver = new ArrayRegistryDriver([
            $key => [
                'label'   => \ucfirst($key),
                'actions' => $actions,
            ],
        ]);

        return new Registry($driver);
    }
}
