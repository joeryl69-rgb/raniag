<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\User;
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
        $isApprove = $this->input('action') === 'approve';

        $rules = [
            'action' => ['required', Rule::in('approve', 'reject')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // Only require assignment selections when approving the report
        if ($isApprove) {
            $rules['assigned_agency_id'] = [
                'nullable',
                'array',
                'min:1',
                'required_without_all:assigned_personnel_id',
            ];
            $rules['assigned_agency_id.*'] = [
                'required',
                Rule::exists(Agency::class, 'id')->where('is_active', true),
            ];

            $rules['assigned_personnel_id'] = [
                'nullable',
                'array',
                'min:1',
                'required_without_all:assigned_agency_id',
            ];
            $rules['assigned_personnel_id.*'] = [
                'required',
                Rule::exists(User::class, 'id')
                    ->where('role', UserRole::Personnel)
                    ->where('is_active', true),
            ];
        } else {
            // When rejecting, assignment fields should not be required; keep them nullable arrays if present
            $rules['assigned_agency_id'] = ['nullable', 'array'];
            $rules['assigned_agency_id.*'] = [Rule::exists(Agency::class, 'id')->where('is_active', true)];

            $rules['assigned_personnel_id'] = ['nullable', 'array'];
            $rules['assigned_personnel_id.*'] = [Rule::exists(User::class, 'id')->where('role', UserRole::Personnel)->where('is_active', true)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Please specify an action: approve or reject.',
            'action.in' => 'Action must be either approve or reject.',
            'assigned_agency_id.required_without_all' => 'Please select at least one agency or personnel to assign this incident to.',
            'assigned_agency_id.array' => 'Agency selection must be an array.',
            'assigned_agency_id.min' => 'Please select at least one agency.',
            'assigned_agency_id.*.exists' => 'One or more selected agencies do not exist or are inactive.',
            'assigned_personnel_id.required_without_all' => 'Please select at least one agency or personnel to assign this incident to.',
            'assigned_personnel_id.array' => 'Personnel selection must be an array.',
            'assigned_personnel_id.min' => 'Please select at least one personnel assignee.',
            'assigned_personnel_id.*.exists' => 'One or more selected personnel accounts do not exist or are inactive.',
        ];
    }
}
