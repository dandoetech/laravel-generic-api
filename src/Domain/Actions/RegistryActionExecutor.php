<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain\Actions;

use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionExecutorInterface;
use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionRequest;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves action handlers from the resource registry instead of config arrays.
 * Custom actions define their handler class on the ActionDefinition via getHandler().
 */
final class RegistryActionExecutor implements MassActionExecutorInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly Container $container,
    ) {
    }

    public function execute(MassActionRequest $request, ?Authenticatable $user = null): array
    {
        $resource = $this->registry->getResource($request->resource);
        if ($resource === null) {
            throw new \InvalidArgumentException("Unknown resource '{$request->resource}'");
        }

        $actionDef = null;
        foreach ($resource->getActions() as $action) {
            if ($action->getName() === $request->action) {
                $actionDef = $action;

                break;
            }
        }

        if ($actionDef === null) {
            throw new \InvalidArgumentException(
                "Unknown action '{$request->action}' on resource '{$request->resource}'",
            );
        }

        $handlerClass = $actionDef->getHandler();
        if ($handlerClass === null) {
            // Check for built-in actions before throwing
            $builtinHandler = $this->resolveBuiltinHandler($request->action);
            if ($builtinHandler !== null) {
                return $builtinHandler->handle($request->resource, $request->ids, $request->payload, $user);
            }

            throw new \InvalidArgumentException(
                "Action '{$request->action}' on resource '{$request->resource}' has no handler defined",
            );
        }

        /** @var object $handler */
        $handler = $this->container->make($handlerClass);
        if (!$handler instanceof ActionHandlerInterface) {
            throw new \InvalidArgumentException(
                "Handler '{$handlerClass}' must implement " . ActionHandlerInterface::class,
            );
        }

        return $handler->handle($request->resource, $request->ids, $request->payload, $user);
    }

    /**
     * Resolve a built-in action handler by name.
     * Built-in actions (e.g., 'clone') don't require a custom handler class on the action definition.
     */
    private function resolveBuiltinHandler(string $action): ?ActionHandlerInterface
    {
        return match ($action) {
            'clone' => new CloneActionHandler($this->registry),
            default => null,
        };
    }
}
