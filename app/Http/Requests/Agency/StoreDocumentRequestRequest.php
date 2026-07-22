<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by the controller route + incident access rules.
        // Keep it permissive here and validate the incident ownership in the service.
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', 'string', 'in:single,bulk'],
            'request_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.in' => 'Invalid request type.',
        ];
    }
}
