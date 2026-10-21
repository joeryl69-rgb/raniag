<?php

namespace App\Http\Requests\Public;

use App\Models\IncidentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
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

        // The column is not nullable. A report can be sent with no written
        // details, so a blank or missing description is stored as empty text.
        $description = $this->input('description');
        $this->merge([
            'description' => is_string($description) ? trim($description) : '',
        ]);

        if ($isAnonymous) {
            $this->merge([
                'reporter_name' => null,
                'reporter_email' => null,
                'reporter_phone' => null,
            ]);
        }

        $meta = $this->input('meta');
        if (is_array($meta) && is_string($meta['gps_captures'] ?? null)) {
            $meta['gps_captures'] = json_encode($this->validGpsCaptures(), JSON_INVALID_UTF8_SUBSTITUTE);
            $this->merge(['meta' => $meta]);
        }

        // A GPS photo or recording already carries coordinates. Copy them
        // onto the report when the location fields were left blank so a
        // successful, attached capture is not rejected as "no location".
        $capture = $this->firstGpsCapture();
        $latBlank = $this->input('latitude') === null || $this->input('latitude') === '';
        $lngBlank = $this->input('longitude') === null || $this->input('longitude') === '';
        if ($capture && ($latBlank || $lngBlank)) {
            $this->merge([
                'latitude' => $latBlank ? $capture['latitude'] : $this->input('latitude'),
                'longitude' => $lngBlank ? $capture['longitude'] : $this->input('longitude'),
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
            'description' => ['nullable', 'string', 'max:5000'],
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
            'reporter_name.required_if' => 'Please provide your name or report anonymously.',
            'reporter_email.required_without' => 'No photo or GPS capture was attached, so please leave a phone number or email so MDRRMO can verify this report.',
            'reporter_phone.required_without' => 'No photo or GPS capture was attached, so please leave a phone number or email so MDRRMO can verify this report.',
            'latitude.required' => 'No GPS photo or recording was attached, and your current location was not shared. Capture a GPS photo or share your location before submitting.',
            'longitude.required' => 'No GPS photo or recording was attached, and your current location was not shared. Capture a GPS photo or share your location before submitting.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $errors = $validator->errors();

            // Latitude and longitude share one sentence. Keep a single bullet
            // in "Please correct the following", and say so only when the
            // submission also has no GPS photo or recording.
            if ($errors->has('latitude') && $errors->has('longitude')) {
                $errors->forget('longitude');
            }

            if ($this->hasGpsCapture() && ($errors->has('latitude') || $errors->has('longitude'))) {
                $errors->forget('latitude');
                $errors->forget('longitude');
                $errors->add('latitude', 'The GPS capture is missing map coordinates. Share your current location or retake the GPS photo.');
            }

            if ($errors->has('reporter_email') && $errors->has('reporter_phone')) {
                $errors->forget('reporter_phone');
            }

            foreach ((array) $this->file('evidence', []) as $index => $file) {
                if (! $file instanceof UploadedFile || ! $this->isVideoEvidence($file)) {
                    continue;
                }

                if (! $this->hasGpsCaptureFor($file)) {
                    $validator->errors()->add(
                        "evidence.{$index}",
                        'Video evidence must be recorded with the GPS Camera while a GPS fix is available. Please record it again after GPS is ready.'
                    );
                }
            }
        });
    }

    /**
     * True when the report carries an uploaded file or a valid GPS capture
     * record matched to an attached photo or video.
     */
    public function hasEvidence(): bool
    {
        foreach ((array) $this->file('evidence', []) as $file) {
            if ($file) {
                return true;
            }
        }

        return $this->hasGpsCapture();
    }

    /**
     * True when the GPS camera log has at least one photo or recording
     * that includes coordinates.
     */
    public function hasGpsCapture(): bool
    {
        return $this->firstGpsCapture() !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function firstGpsCapture(): ?array
    {
        return $this->gpsCaptures()[0] ?? null;
    }

    /**
     * @return list<mixed>
     */
    private function gpsCaptures(): array
    {
        $captures = $this->input('meta.gps_captures');
        if (! is_string($captures) || $captures === '') {
            return [];
        }

        $decoded = json_decode($captures, true);

        if (! is_array($decoded)) {
            return [];
        }

        return $this->validGpsCaptures($decoded);
    }

    /**
     * @param  list<mixed>|null  $captures
     * @return list<array<string, mixed>>
     */
    private function validGpsCaptures(?array $captures = null): array
    {
        if ($captures === null) {
            $raw = $this->input('meta.gps_captures');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            $captures = is_array($decoded) ? array_values($decoded) : [];
        }

        $files = array_values(array_filter(
            (array) $this->file('evidence', []),
            fn ($file) => $file instanceof UploadedFile
        ));
        $valid = [];

        foreach ($captures as $capture) {
            if (! is_array($capture)
                || ($capture['location_source'] ?? null) !== 'gps'
                || ! is_string($capture['filename'] ?? null)
                || ! is_numeric($capture['latitude'] ?? null)
                || ! is_numeric($capture['longitude'] ?? null)
                || ! is_string($capture['captured_at'] ?? null)
                || strtotime($capture['captured_at']) === false) {
                continue;
            }

            $latitude = (float) $capture['latitude'];
            $longitude = (float) $capture['longitude'];
            if (! is_finite($latitude) || ! is_finite($longitude)
                || $latitude < -90 || $latitude > 90
                || $longitude < -180 || $longitude > 180) {
                continue;
            }

            foreach ($files as $file) {
                if ($file->getClientOriginalName() === $capture['filename']
                    && ($this->isVideoEvidence($file) || str_starts_with((string) $file->getClientMimeType(), 'image/'))) {
                    $valid[] = $capture;
                    break;
                }
            }
        }

        return $valid;
    }

    private function isVideoEvidence(UploadedFile $file): bool
    {
        return str_starts_with((string) $file->getClientMimeType(), 'video/')
            || in_array(strtolower($file->getClientOriginalExtension()), ['mp4', 'mov', 'webm'], true);
    }

    private function hasGpsCaptureFor(UploadedFile $file): bool
    {
        foreach ($this->gpsCaptures() as $capture) {
            if (($capture['filename'] ?? null) === $file->getClientOriginalName()) {
                return true;
            }
        }

        return false;
    }
}
