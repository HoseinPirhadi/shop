<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['sometimes', 'nullable', 'string', 'max:160'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ];
    }
}
