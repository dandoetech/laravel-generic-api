<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Requests;

use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Foundation\Http\FormRequest;

final class StoreRequest extends FormRequest
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
            if ($f->getRules() !== []) {
                $rules[$f->getName()] = \implode('|', $f->getRules());
            } elseif ($f->isNullable() === false) {
                $rules[$f->getName()] = 'required';
            }
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true; // route/controller authorizes per action via policies
    }
}
