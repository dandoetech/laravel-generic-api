<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Requests;

use DanDoeTech\ResourceRegistry\Registry\Registry;
use Illuminate\Foundation\Http\FormRequest;

final class StoreRequest extends FormRequest
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
            if ($f->rules !== []) {
                $rules[$f->name] = implode('|', $f->rules);
            } elseif ($f->nullable === false) {
                $rules[$f->name] = 'required';
            }
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true; // route/controller authorizes per action via policies
    }
}
