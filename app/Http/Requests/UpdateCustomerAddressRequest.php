<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerAddressRequest extends FormRequest
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
            'label' => ['sometimes', 'required', 'string', 'max:80'],
            'recipient_name' => ['sometimes', 'required', 'string', 'max:160'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'country_code' => ['sometimes', 'required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'state' => ['sometimes', 'nullable', 'string', 'max:120'],
            'city' => ['sometimes', 'required', 'string', 'max:120'],
            'address_line1' => ['sometimes', 'required', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
