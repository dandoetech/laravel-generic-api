<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Requests;

use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $resource = (string) $this->route('resource');
        /** @var Registry $registry */
        $registry = app(Registry::class);
        $res = $registry->getResource($resource);

        if (!$res) {
            return [];
        }

        $rules = [];
        foreach ($res->fields as $f) {
            // make rules nullable for PATCH semantics unless explicitly required
            if ($f->rules !== []) {
                $rules[$f->name] = 'sometimes|' . implode('|', array_filter($f->rules, fn ($r) => $r !== 'required'));
            } else {
                $rules[$f->name] = 'sometimes';
            }
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }
}
