<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain\MassAction;

use Illuminate\Contracts\Auth\Authenticatable;

interface MassActionHandlerInterface
{
    /**
     * @return array<string,mixed> Result summary (e.g., { affected: 42 })
     */
    public function handle(MassActionRequest $request, ?Authenticatable $user = null): array;
}
