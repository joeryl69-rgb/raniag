<?php

namespace App\Http\Requests\Admin;

use App\Models\Agency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in('approve', 'reject')],
            'notes' => ['nullable', 'string', 'max:1000'],
            'assigned_agency_id' => [
                'required_if:action,approve',
                'nullable',
                Rule::exists(Agency::class, 'id')->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Please specify an action: approve or reject.',
            'action.in' => 'Action must be either approve or reject.',
            'assigned_agency_id.required_if' => 'Please select an agency to assign this incident to.',
            'assigned_agency_id.exists' => 'Selected agency does not exist or is inactive.',
        ];
    }
}
