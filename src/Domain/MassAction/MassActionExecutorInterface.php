<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain\MassAction;

use Illuminate\Contracts\Auth\Authenticatable;

interface MassActionExecutorInterface
{
    /**
     * Resolve the handler from config and execute.
     * @return array<string,mixed>
     */
    public function execute(MassActionRequest $request, ?Authenticatable $user = null): array;
}
