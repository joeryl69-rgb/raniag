<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_anonymous' => $this->boolean('is_anonymous'),
        ]);

        if ($this->boolean('is_anonymous')) {
            $this->merge([
                'reporter_name' => null,
                'reporter_email' => null,
                'reporter_phone' => null,
            ]);
        }

        // Priority is always set to 'medium' by system, not user
        $this->merge([
            'priority' => 'medium',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxFiles = (int) config('raniag.evidence.max_files', 5);
        $maxSize = (int) config('raniag.evidence.max_size_kb', 5120);
        $mimes = config('raniag.evidence.allowed_mimes', []);

        return [
            'incident_type_id' => ['required', 'integer', Rule::exists('incident_types', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'title' => ['nullable', 'string', 'max:255'],
            'location_address' => ['nullable', 'string', 'max:500'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'reporter_name' => ['nullable', 'required_if:is_anonymous,0,false', 'string', 'max:255'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
            'reporter_phone' => ['nullable', 'string', 'max:32'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'evidence' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'evidence.*' => ['file', 'max:'.$maxSize, 'mimes:'.implode(',', $mimes)],
            'meta' => ['sometimes', 'array'],
            'meta.gps_captures' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'incident_type_id.required' => 'Please select an incident type.',
            'description.min' => 'Please provide at least 10 characters describing the incident.',
            'reporter_name.required_if' => 'Please provide your name or report anonymously.',
            'latitude.required' => 'Please pin the incident location on the map.',
            'longitude.required' => 'Please pin the incident location on the map.',
            'evidence.required' => 'Please attach at least one geotagged photo using the GPS camera.',
            'evidence.min' => 'Please attach at least one geotagged photo using the GPS camera.',
            'meta.gps_captures.required' => 'Please capture at least one geotagged photo using the GPS camera.',
        ];
    }
}
