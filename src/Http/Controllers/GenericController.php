<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Controllers;

use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionExecutorInterface;
use DanDoeTech\LaravelGenericApi\Domain\MassAction\MassActionRequest;
use DanDoeTech\LaravelGenericApi\Domain\RepositoryAdapterInterface;
use DanDoeTech\LaravelGenericApi\Exceptions\InvalidCriteriaException;
use DanDoeTech\LaravelGenericApi\Http\Requests\ActionRequest;
use DanDoeTech\LaravelGenericApi\Http\Requests\StoreRequest;
use DanDoeTech\LaravelGenericApi\Http\Requests\UpdateRequest;
use DanDoeTech\LaravelGenericApi\Http\Resources\ResourceJson;
use DanDoeTech\LaravelGenericApi\Support\CriteriaProfileResolver;
use DanDoeTech\LaravelGenericApi\Support\QueryCriteria;
use DanDoeTech\LaravelGenericApi\Support\RegistryUtils;
use DanDoeTech\ResourceRegistry\Contracts\ResourceDefinitionInterface;
use DanDoeTech\ResourceRegistry\Definition\QueryProfile;
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

    public function index(Request $request): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $res = $this->resolve($resource);

        $queryProfile = $this->resolveQueryProfile($resource, $res, $request);

        $defaultFilterable = $res->getFilterable() ?: RegistryUtils::fieldNames($res);
        $defaultSortable = $res->getSortable() ?: ['id', 'created_at'];
        $filterable = ($queryProfile !== null && $queryProfile->filterable !== null) ? $queryProfile->filterable : $defaultFilterable;
        $sortable = ($queryProfile !== null && $queryProfile->sortable !== null) ? $queryProfile->sortable : $defaultSortable;

        /** @var int $perDefault */
        $perDefault = config('ddt_api.pagination.per_page', 25);
        /** @var int $perMax */
        $perMax = config('ddt_api.pagination.max_per_page', 200);

        /** @var array<string, mixed> $query */
        $query = $request->query();

        $stringFields = RegistryUtils::stringFieldNames($res);

        try {
            $criteria = QueryCriteria::from($query, $filterable, $sortable, $perDefault, $perMax, $stringFields);
        } catch (InvalidCriteriaException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => $e->toErrors(),
            ], 422);
        }

        // Apply preFilter from query profile as additional WHERE conditions
        if ($queryProfile !== null && $queryProfile->preFilter !== []) {
            $preFilters = [];
            foreach ($queryProfile->preFilter as $field => $value) {
                $preFilters[] = ['field' => $field, 'operator' => 'eq', 'value' => $value];
            }
            $criteria['filters'] = \array_merge($preFilters, $criteria['filters']);
        }

        $out = $this->repo->paginate($resource, $criteria);

        return response()->json(ResourceJson::collection($out['data'], $out['meta']));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $this->resolve($resource);

        $item = $this->repo->find($resource, $id);
        abort_if(!$item, 404);

        /** @var array<string, mixed> $item */
        return response()->json(ResourceJson::item($item));
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $res = $this->resolve($resource);

        $allowed = RegistryUtils::fieldNames($res);
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        /** @var array<string, mixed> $data */
        $data = \array_intersect_key($validated, \array_flip($allowed));

        $created = $this->repo->create($resource, $data);

        return response()->json(ResourceJson::item($created), 201);
    }

    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $res = $this->resolve($resource);

        $allowed = RegistryUtils::fieldNames($res);
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        /** @var array<string, mixed> $data */
        $data = \array_intersect_key($validated, \array_flip($allowed));

        $updated = $this->repo->update($resource, $id, $data);

        return response()->json(ResourceJson::item($updated));
    }

    public function destroy(Request $request, string $id): Response
    {
        $resource = $this->resourceKey($request);
        $this->resolve($resource);

        $this->repo->delete($resource, $id);

        return response()->noContent();
    }

    public function action(ActionRequest $request, string $action): JsonResponse
    {
        if ($this->actions === null) {
            abort(404, 'Mass actions are not configured');
        }

        $resource = $this->resourceKey($request);
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
     * Extract the resource key from route defaults.
     */
    private function resourceKey(Request $request): string
    {
        /** @var string $key */
        $key = $request->route('resource', '');

        return $key;
    }

    /**
     * Resolve query profile: first from Resource class, then config fallback (deprecated).
     */
    private function resolveQueryProfile(string $resource, ResourceDefinitionInterface $res, Request $request): ?QueryProfile
    {
        $profile = $request->query('profile');
        if (!\is_string($profile) || $profile === '') {
            return null;
        }

        // 1. Try Resource class
        $resourceProfiles = $res->getQueryProfiles();
        if (isset($resourceProfiles[$profile])) {
            return $resourceProfiles[$profile];
        }

        // 2. Fallback: Config-based profiles (deprecated)
        $resolved = CriteriaProfileResolver::resolve($resource, $profile);
        if ($resolved !== null) {
            return new QueryProfile(
                filterable: $resolved['filterable'],
                sortable: $resolved['sortable'],
            );
        }

        return null;
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
