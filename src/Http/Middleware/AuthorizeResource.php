<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Middleware;

use Closure;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\LaravelResourceRegistry\Contracts\HasPolicy;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizeResource
{
    public function __construct(
        private readonly Registry $registry,
        private readonly Gate $gate,
    ) {
    }

    /**
     * @param \Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resourceKey = $request->route('resource');
        if (!\is_string($resourceKey)) {
            return $next($request);
        }

        $resource = $this->registry->getResource($resourceKey);
        if ($resource === null) {
            return $next($request);
        }

        if (!$resource instanceof HasPolicy) {
            return $next($request);
        }

        if (!$resource instanceof HasEloquentModel) {
            throw new \RuntimeException(
                "Resource '{$resourceKey}' has a policy but no model binding. Implement HasEloquentModel to use authorization.",
            );
        }

        $modelClass = $resource->model();
        $this->gate->policy($modelClass, $resource->policy());

        $method = $request->method();
        $id = $request->route('id');
        $action = $request->route('action');

        if (\is_string($action)) {
            $this->gate->authorize('action', [$modelClass, $action]);
        } elseif (\is_string($id)) {
            $model = $modelClass::query()->findOrFail($id);
            $ability = match ($method) {
                'GET' => 'view',
                'PUT', 'PATCH' => 'update',
                'DELETE' => 'delete',
                default  => 'view',
            };
            $this->gate->authorize($ability, $model);
        } else {
            $ability = match ($method) {
                'GET'   => 'viewAny',
                'POST'  => 'create',
                default => 'viewAny',
            };
            $this->gate->authorize($ability, $modelClass);
        }

        return $next($request);
    }
}
