<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain\MassAction;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/**
 * @deprecated Use RegistryActionExecutor instead. Action handlers are now defined
 *             on the Resource class via ActionDefinition::$handler, not in config arrays.
 */
final class ConfigMassActionExecutor implements MassActionExecutorInterface
{
    public function execute(MassActionRequest $request, ?Authenticatable $user = null): array
    {
        /** @var array<string, class-string<MassActionHandlerInterface>> $map */
        $map = (array) config("ddt_api.actions.{$request->resource}", []);
        $handlerClass = $map[$request->action] ?? null;
        if (!$handlerClass) {
            throw new InvalidArgumentException("Unknown mass action '{$request->action}' for resource '{$request->resource}'");
        }
        /** @var MassActionHandlerInterface $handler */
        $handler = app($handlerClass);

        return $handler->handle($request, $user);
    }
}
