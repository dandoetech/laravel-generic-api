<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Requests;

use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        /** @var string $resource */
        $resource = $this->route('resource', '');
        /** @var Registry $registry */
        $registry = app(Registry::class);
        $res = $registry->getResource($resource);

        if (!$res) {
            return [];
        }

        $rules = [];
        foreach ($res->getFields() as $f) {
            // make rules nullable for PATCH semantics unless explicitly required
            if ($f->getRules() !== []) {
                $rules[$f->getName()] = 'sometimes|' . \implode('|', \array_filter($f->getRules(), fn ($r) => $r !== 'required'));
            } else {
                $rules[$f->getName()] = 'sometimes';
            }
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }
}
