<?php

namespace App\Http\Requests\Admin;

use App\Models\Agency;
use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    public function rules(): array
    {
        return [
            'incident_id' => [
                'required',
                'integer',
                Rule::exists(Incident::class, 'id'),
            ],
            'agency_id' => [
                'required',
                'integer',
                Rule::exists(Agency::class, 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'incident_id.required' => 'Incident ID is required.',
            'incident_id.exists' => 'Selected incident does not exist.',
            'agency_id.required' => 'Please select an agency.',
            'agency_id.exists' => 'Selected agency does not exist or is inactive.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }
}
