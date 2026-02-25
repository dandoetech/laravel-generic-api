<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain\MassAction;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

final class ConfigMassActionExecutor implements MassActionExecutorInterface
{
    public function execute(MassActionRequest $request, ?Authenticatable $user = null): array
    {
        /** @var array<string, class-string<MassActionHandlerInterface>> $map */
        $map = (array) config("generic_api.actions.{$request->resource}", []);
        $handlerClass = $map[$request->action] ?? null;
        if (!$handlerClass) {
            throw new InvalidArgumentException("Unknown mass action '{$request->action}' for resource '{$request->resource}'");
        }
        /** @var MassActionHandlerInterface $handler */
        $handler = app($handlerClass);

        return $handler->handle($request, $user);
    }
}
