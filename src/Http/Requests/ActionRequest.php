<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer'], // adjust if string keys
            'payload' => ['sometimes','array'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
