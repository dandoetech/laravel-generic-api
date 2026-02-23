<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Controllers;

use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionExecutorInterface;
use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionRequest;
use DanDoeTech\LaravelGenericApi\Domain\RepositoryAdapterInterface;
use DanDoeTech\LaravelGenericApi\Http\Requests\ActionRequest;
use DanDoeTech\LaravelGenericApi\Http\Requests\StoreRequest;
use DanDoeTech\LaravelGenericApi\Http\Requests\UpdateRequest;
use DanDoeTech\LaravelGenericApi\Http\Resources\ResourceJson;
use DanDoeTech\LaravelGenericApi\Support\CriteriaProfileResolver;
use DanDoeTech\LaravelGenericApi\Support\QueryCriteria;
use DanDoeTech\LaravelGenericApi\Support\RegistryUtils;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class GenericController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly Registry $registry,
        private readonly RepositoryAdapterInterface $repo,
        private readonly ?MassActionExecutorInterface $actions = null
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request, string $resource): JsonResponse
    {
        $res = $this->registry->getResource($resource);
        abort_if(!$res, 404, "Unknown resource '$resource'");

        //$this->authorize('viewAny', [$resource, $res]);

        // Criteria profile support
        $profile = $request->query('profile');
        $resolved = CriteriaProfileResolver::resolve($resource, is_string($profile) ? $profile : null);

        $filterable = $resolved['filterable'] ?: RegistryUtils::fieldNames($res);
        $sortable   = $resolved['sortable'] ?: ['id','created_at'];

        $perDefault = (int) config('generic_api.pagination.per_page', 25);
        $perMax     = (int) config('generic_api.pagination.max_per_page', 200);

        $criteria = QueryCriteria::from($request->query(), $filterable, $sortable, $perDefault, $perMax);

        // Apply resource scope if configured
        // (Only relevant for Eloquent adapter; scope is applied inside the adapter via a hook or here if you expose it)
        // Here we pass criteria; adapter can call ScopeApplier if it supports Builder access.
        $out = $this->repo->paginate($resource, $criteria);

        return response()->json(ResourceJson::collection($out['data'], $out['meta']));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(string $resource, string $id): JsonResponse
    {
        $res = $this->registry->getResource($resource);
        abort_if(!$res, 404);

        $this->authorize('view', [$resource, $id, $res]);

        $item = $this->repo->find($resource, $id);
        abort_if(!$item, 404);

        return response()->json(ResourceJson::item($item));
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, string $resource): JsonResponse
    {
        $res = $this->registry->getResource($resource);
        abort_if(!$res, 404);

        $this->authorize('create', [$resource, $res]);

        $allowed = RegistryUtils::fieldNames($res);
        $data = array_intersect_key($request->validated(), array_flip($allowed));

        $created = $this->repo->create($resource, $data);
        return response()->json(ResourceJson::item($created), 201);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, string $resource, string $id): JsonResponse
    {
        $res = $this->registry->getResource($resource);
        abort_if(!$res, 404);

        $this->authorize('update', [$resource, $id, $res]);

        $allowed = RegistryUtils::fieldNames($res);
        $data = array_intersect_key($request->validated(), array_flip($allowed));

        $updated = $this->repo->update($resource, $id, $data);
        return response()->json(ResourceJson::item($updated));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(string $resource, string $id): Response
    {
        $res = $this->registry->getResource($resource);
        abort_if(!$res, 404);

        $this->authorize('delete', [$resource, $id, $res]);

        $this->repo->delete($resource, $id);
        return response()->noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function action(ActionRequest $request, string $resource, string $action): JsonResponse
    {
        abort_if(!$this->actions, 404, 'Mass actions are not configured');
        $res = $this->registry->getResource($resource);
        abort_if(!$res, 404);

        $this->authorize('action', [$resource, $action, $res]); // add a policy method for actions

        $ids = $request->validated('ids');
        $payload = (array) ($request->validated()['payload'] ?? []);

        $result = $this->actions->execute(
            new MassActionRequest($resource, $action, $ids, $payload),
            $request->user()
        );

        return response()->json(['data' => $result]);
    }
}
