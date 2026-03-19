<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Domain\Actions;

use DanDoeTech\LaravelResourceRegistry\Contracts\HasEloquentModel;
use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Built-in handler for the 'clone' action.
 * Duplicates records, copying all fields except id and timestamps.
 */
final class CloneActionHandler implements ActionHandlerInterface
{
    public function __construct(
        private readonly Registry $registry,
    ) {
    }

    public function handle(string $resource, array $ids, array $payload, ?Authenticatable $user): array
    {
        $res = $this->registry->getResource($resource);
        if ($res === null) {
            throw new \InvalidArgumentException("Unknown resource '{$resource}'");
        }

        if (!$res instanceof HasEloquentModel) {
            throw new \InvalidArgumentException("Resource '{$resource}' does not implement HasEloquentModel");
        }

        $modelClass = $res->model();

        /** @var list<array<string, mixed>> $cloned */
        $cloned = DB::transaction(static function () use ($modelClass, $ids): array {
            $results = [];

            foreach ($ids as $id) {
                /** @var Model $original */
                $original = $modelClass::query()->findOrFail($id);
                /** @var Model $clone */
                $clone = $original->replicate();
                $clone->save();
                /** @var array<string, mixed> $row */
                $row = $clone->toArray();
                $results[] = $row;
            }

            return $results;
        });

        return ['cloned' => $cloned];
    }
}
