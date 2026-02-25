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
use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class GenericController extends Controller
{
    public function __construct(
        private readonly Registry $registry,
        private readonly RepositoryAdapterInterface $repo,
        private readonly ?MassActionExecutorInterface $actions = null,
    ) {
    }

    public function index(Request $request, string $resource): JsonResponse
    {
        $res = $this->resolve($resource);

        // Criteria profile override (only when ?profile= is present and profile exists)
        $profile = $request->query('profile');
        $resolved = \is_string($profile) ? CriteriaProfileResolver::resolve($resource, $profile) : null;

        $filterable = $resolved['filterable'] ?? ($res->getFilterable() ?: RegistryUtils::fieldNames($res));
        $sortable = $resolved['sortable'] ?? ($res->getSortable() ?: ['id', 'created_at']);

        /** @var int $perDefault */
        $perDefault = config('generic_api.pagination.per_page', 25);
        /** @var int $perMax */
        $perMax = config('generic_api.pagination.max_per_page', 200);

        /** @var array<string, mixed> $query */
        $query = $request->query();
        $criteria = QueryCriteria::from($query, $filterable, $sortable, $perDefault, $perMax);

        $out = $this->repo->paginate($resource, $criteria);

        return response()->json(ResourceJson::collection($out['data'], $out['meta']));
    }

    public function show(string $resource, string $id): JsonResponse
    {
        $this->resolve($resource);

        $item = $this->repo->find($resource, $id);
        abort_if(!$item, 404);

        /** @var array<string, mixed> $item */
        return response()->json(ResourceJson::item($item));
    }

    public function store(StoreRequest $request, string $resource): JsonResponse
    {
        $res = $this->resolve($resource);

        $allowed = RegistryUtils::fieldNames($res);
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        /** @var array<string, mixed> $data */
        $data = \array_intersect_key($validated, \array_flip($allowed));

        $created = $this->repo->create($resource, $data);

        return response()->json(ResourceJson::item($created), 201);
    }

    public function update(UpdateRequest $request, string $resource, string $id): JsonResponse
    {
        $res = $this->resolve($resource);

        $allowed = RegistryUtils::fieldNames($res);
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        /** @var array<string, mixed> $data */
        $data = \array_intersect_key($validated, \array_flip($allowed));

        $updated = $this->repo->update($resource, $id, $data);

        return response()->json(ResourceJson::item($updated));
    }

    public function destroy(string $resource, string $id): Response
    {
        $this->resolve($resource);

        $this->repo->delete($resource, $id);

        return response()->noContent();
    }

    public function action(ActionRequest $request, string $resource, string $action): JsonResponse
    {
        if ($this->actions === null) {
            abort(404, 'Mass actions are not configured');
        }

        $this->resolve($resource);

        /** @var list<string|int> $ids */
        $ids = $request->validated('ids');
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        /** @var array<string, mixed> $payload */
        $payload = (array) ($validated['payload'] ?? []);

        /** @var \Illuminate\Contracts\Auth\Authenticatable|null $user */
        $user = $request->user();

        $result = $this->actions->execute(
            new MassActionRequest($resource, $action, $ids, $payload),
            $user,
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Look up a resource by key, aborting with 404 if not found.
     */
    private function resolve(string $resource): ResourceDefinitionInterface
    {
        $res = $this->registry->getResource($resource);
        if ($res === null) {
            abort(404, "Unknown resource '$resource'");
        }

        return $res;
    }
}
