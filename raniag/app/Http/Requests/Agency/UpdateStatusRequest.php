<?php

namespace App\Http\Requests\Agency;

use App\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAgency() || $this->user()->isPersonnel();
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(IncidentStatus::class),
                Rule::in(
                    IncidentStatus::InProgress->value,
                    IncidentStatus::PendingInfo->value,
                ),
            ],
            'comment' => ['nullable', 'string', 'max:1000'],
            'needs_info' => ['nullable', 'required_if:status,'.IncidentStatus::PendingInfo->value, 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please select a status.',
            'status.enum' => 'Invalid status selected.',
            'status.in' => 'Status can only be: In Progress or Pending Information.',
            'needs_info.required_if' => 'Please explain what information is needed.',
            'needs_info.max' => 'Information request cannot exceed 5000 characters.',
        ];
    }
}
