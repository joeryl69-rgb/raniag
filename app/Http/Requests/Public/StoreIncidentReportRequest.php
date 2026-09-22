<?php

namespace App\Http\Requests\Public;

use App\Models\IncidentType;
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
        $isAnonymous = $this->boolean('is_anonymous');

        // Anonymous is only a real option when there's something to verify
        // the report by. No uploaded file and no GPS-camera capture means
        // no evidence at all — force anonymous off and require contact info
        // (see rules()/hasEvidence()) instead of silently accepting a report
        // nobody can act on.
        if ($isAnonymous && ! $this->hasEvidence()) {
            $isAnonymous = false;
        }

        $this->merge(['is_anonymous' => $isAnonymous]);

        if ($isAnonymous) {
            $this->merge([
                'reporter_name' => null,
                'reporter_email' => null,
                'reporter_phone' => null,
            ]);
        }

        // Priority is derived from the selected incident type's admin-configured
        // default_priority (Admin > Incident Types), not a fixed system value and
        // not something the reporter picks. Falls back to 'medium' only if the
        // type can't be resolved (e.g. invalid incident_type_id — the separate
        // 'exists' rule below still catches that as a validation error).
        $incidentType = IncidentType::find($this->input('incident_type_id'));

        $this->merge([
            'priority' => $incidentType?->default_priority?->value ?? 'medium',
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
        $hasEvidence = $this->hasEvidence();

        return [
            'incident_type_id' => ['required', 'integer', Rule::exists('incident_types', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'title' => ['nullable', 'string', 'max:255'],
            'location_address' => ['nullable', 'string', 'max:500'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'reporter_name' => ['nullable', 'required_if:is_anonymous,0,false', 'string', 'max:255'],
            // Without a photo or a GPS capture, staff have nothing to verify the
            // report against — at least one way to reach the reporter becomes
            // mandatory. With evidence, both stay optional (anonymous is fine).
            'reporter_email' => array_values(array_filter([
                'nullable', 'email', 'max:255',
                $hasEvidence ? null : 'required_without:reporter_phone',
            ])),
            'reporter_phone' => array_values(array_filter([
                'nullable', 'string', 'max:32',
                $hasEvidence ? null : 'required_without:reporter_email',
            ])),
            'is_anonymous' => ['sometimes', 'boolean'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'evidence' => ['nullable', 'array', 'max:'.$maxFiles],
            'evidence.*' => ['file', 'max:'.$maxSize, 'mimes:'.implode(',', $mimes)],
            'meta' => ['sometimes', 'array'],
            'meta.gps_captures' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
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
            'reporter_email.required_without' => 'No photo or GPS capture was attached, so please leave a phone number or email so MDRRMO can verify this report.',
            'reporter_phone.required_without' => 'No photo or GPS capture was attached, so please leave a phone number or email so MDRRMO can verify this report.',
            'latitude.required' => 'Please share your current location or capture a GPS photo.',
            'longitude.required' => 'Please share your current location or capture a GPS photo.',
        ];
    }

    /**
     * True when the report carries a photo (manually chosen or captured via
     * the GPS camera — both end up in the `evidence` file array) or at least
     * one entry in the GPS-camera's capture log.
     */
    public function hasEvidence(): bool
    {
        foreach ((array) $this->file('evidence', []) as $file) {
            if ($file) {
                return true;
            }
        }

        $captures = $this->input('meta.gps_captures');
        if (is_string($captures) && $captures !== '') {
            $decoded = json_decode($captures, true);
            if (is_array($decoded) && $decoded !== []) {
                return true;
            }
        }

        return false;
    }
}
