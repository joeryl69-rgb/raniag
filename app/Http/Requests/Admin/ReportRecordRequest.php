<?php

namespace App\Http\Requests\Admin;

use App\Enums\IncidentDocumentType;
use App\Models\ActivityLog;
use App\Models\FeedbackSubmission;
use App\Models\ReportRecord;
use App\Support\ReportChapterCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdministrator();
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');
        $chapters = $this->input('chapters', []);

        if (is_array($chapters)) {
            $chapters = array_values($chapters);
            foreach ($chapters as $index => $chapter) {
                if (! is_array($chapter) || ! is_array($chapter['options'] ?? null)) {
                    continue;
                }
                $options = $chapter['options'];
                if (($chapter['type'] ?? null) === 'response' && ! array_key_exists('compare', $options)) {
                    $options['compare'] = false;
                }
                $chapters[$index]['options'] = $options;
            }
        }

        $this->merge([
            'title' => is_string($title) ? trim($title) : $title,
            'chapters' => $chapters,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'date_from' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from', 'before_or_equal:today'],
            'chapters' => ['required', 'array', 'min:1', 'max:12'],
            'chapters.*' => ['required', 'array:key,type,options'],
            'chapters.*.key' => ['required', 'uuid', 'distinct:strict'],
            'chapters.*.type' => ['required', 'string', Rule::in(ReportChapterCatalog::TYPES)],
            'chapters.*.options' => ['present', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Give the record a title.',
            'title.min' => 'The title needs at least 3 characters.',
            'title.max' => 'The title can be at most 120 characters.',
            'date_from.required' => 'Choose the first day.',
            'date_from.date_format' => 'Enter the first day as a valid calendar date.',
            'date_from.before_or_equal' => 'The first day cannot be in the future.',
            'date_to.required' => 'Choose the last day.',
            'date_to.date_format' => 'Enter the last day as a valid calendar date.',
            'date_to.after_or_equal' => 'The last day cannot be before the first day.',
            'date_to.before_or_equal' => 'The last day cannot be in the future.',
            'chapters.required' => 'Add at least one chapter.',
            'chapters.min' => 'Add at least one chapter.',
            'chapters.max' => 'A record can hold 12 chapters.',
            'chapters.*.key.required' => 'A chapter is missing its identity.',
            'chapters.*.key.uuid' => 'A chapter identity is not valid.',
            'chapters.*.key.distinct' => 'Two chapters are using the same identity.',
            'chapters.*.type.required' => 'Choose a chapter.',
            'chapters.*.type.in' => 'That chapter is not one this record can hold.',
            'chapters.*.options.present' => 'A chapter is missing its settings.',
            'chapters.*.options.array' => 'A chapter’s settings are not readable.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $title = $this->input('title');
            if (is_string($title) && mb_strlen($title) >= 3 && mb_strlen($title) <= 120 && ! preg_match('/\p{L}|\p{N}/u', $title)) {
                $validator->errors()->add('title', 'Give the record a title that includes a word.');
            }

            $from = null;
            if (! $validator->errors()->has('date_from') && ! $validator->errors()->has('date_to')) {
                $from = Carbon::createFromFormat('!Y-m-d', (string) $this->input('date_from'));
                $to = Carbon::createFromFormat('!Y-m-d', (string) $this->input('date_to'));
                if ($from->diffInDays($to) > 366) {
                    $validator->errors()->add('date_to', 'Choose a year or less.');
                }
            }

            $this->validateChapters($validator, $from);
        });
    }

    private function validateChapters(Validator $validator, ?Carbon $from): void
    {
        $chapters = $this->input('chapters', []);
        if (! is_array($chapters)) {
            return;
        }

        $seen = [];
        foreach ($chapters as $index => $chapter) {
            if (! is_array($chapter)) {
                continue;
            }
            $type = (string) ($chapter['type'] ?? '');
            $label = ReportChapterCatalog::title($type);
            $options = $chapter['options'] ?? [];
            if (! is_array($options)) {
                continue;
            }
            $allowed = ReportChapterCatalog::OPTIONS[$type] ?? [];

            foreach (array_keys($options) as $key) {
                if (! in_array($key, $allowed, true)) {
                    $validator->errors()->add(
                        "chapters.$index.options.$key",
                        $label.': that setting does not belong on this chapter.'
                    );
                }
            }

            match ($type) {
                'open_cases' => $this->validateOpenCases($validator, $index, $options, $label),
                'response' => $this->validateResponse($validator, $index, $options, $label, $from),
                'repeats', 'map' => $this->rejectFilled($validator, $index, $options, $label),
                'shelter' => $this->validateIn($validator, $index, $options, 'guest_scope', ['still_in', 'checked_in', 'outside'], $label.': choose who to include.'),
                'papers' => $this->validatePapers($validator, $index, $options, $label),
                'texts' => $this->validateIn($validator, $index, $options, 'status', ['failed', 'sent', 'pending', 'all'], $label.': choose which texts to include.'),
                'support' => $this->validateSupport($validator, $index, $options, $label),
                'notices' => $this->validateIn($validator, $index, $options, 'published', ['yes', 'no', 'all'], $label.': choose which notices to include.'),
                'activity' => $this->validateActivity($validator, $index, $options, $label),
                'one_case' => $this->validateCase($validator, $index, $options, $label),
                default => null,
            };

            $signature = $type.'|'.json_encode($this->signatureOptions($options));
            if (isset($seen[$signature])) {
                $validator->errors()->add("chapters.$index.type", 'That chapter is already in the record.');
            }
            $seen[$signature] = true;
        }
    }

    /** @param  array<string, mixed>  $options */
    private function signatureOptions(array $options): array
    {
        ksort($options);
        foreach ($options as $key => $value) {
            if ($value === '' || $value === null) {
                $options[$key] = null;
            } elseif ($key === 'compare') {
                $options[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_string($value)) {
                $options[$key] = trim($value);
            }
        }

        return $options;
    }

    /** @param  array<string, mixed>  $options */
    private function rejectFilled(Validator $validator, int $index, array $options, string $label): void
    {
        if ($options !== []) {
            $validator->errors()->add("chapters.$index.options", $label.': this chapter has no settings.');
        }
    }

    /** @param  array<string, mixed>  $options */
    private function validateOpenCases(Validator $validator, int $index, array $options, string $label): void
    {
        $this->validateIn($validator, $index, $options, 'aor_scope', ['aor_only', 'outside_aor_only', 'all'], $label.': choose which reports to include.');
        $barangay = $options['barangay'] ?? null;
        $places = array_merge(config('raniag.barangays'), ['inside', 'outside', 'all']);
        if ($barangay !== null && $barangay !== '' && ! in_array($barangay, $places, true)) {
            $validator->errors()->add("chapters.$index.options.barangay", $label.': choose all barangays, all inside Pamplona, or all outside Pamplona.');
        }
        $this->existsOrEmpty($validator, $index, $options, 'agency_id', 'agencies', 'id', $label.': that office is not on file.');
        $this->existsOrEmpty($validator, $index, $options, 'incident_type_id', 'incident_types', 'id', $label.': that incident type is not on file.');
    }

    /** @param  array<string, mixed>  $options */
    private function validateResponse(Validator $validator, int $index, array $options, string $label, ?Carbon $recordFrom): void
    {
        $this->validateIn($validator, $index, $options, 'view_mode', ['weekly', 'monthly', 'yearly', 'periodic'], $label.': choose how to group the days.');
        $compare = filter_var($options['compare'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($compare === null) {
            $validator->errors()->add("chapters.$index.options.compare", $label.': say whether to compare an earlier period.');

            return;
        }
        $compareFrom = $options['compare_from'] ?? null;
        $compareTo = $options['compare_to'] ?? null;
        $hasDates = filled($compareFrom) || filled($compareTo);
        if (! $compare && $hasDates) {
            $validator->errors()->add("chapters.$index.options.compare_from", $label.': leave the comparison dates empty unless the comparison is on.');

            return;
        }
        if (! $compare) {
            return;
        }
        if (! filled($compareFrom) || ! filled($compareTo)) {
            $validator->errors()->add("chapters.$index.options.compare_from", $label.': choose both days of the earlier period.');

            return;
        }
        try {
            if (! is_string($compareFrom) || ! $this->isCalendarDate($compareFrom) || ! is_string($compareTo) || ! $this->isCalendarDate($compareTo)) {
                throw new \InvalidArgumentException;
            }
            $start = Carbon::createFromFormat('!Y-m-d', $compareFrom);
            $end = Carbon::createFromFormat('!Y-m-d', $compareTo);
        } catch (\Throwable) {
            $validator->errors()->add("chapters.$index.options.compare_from", $label.': the earlier period is not a real date.');

            return;
        }
        if ($start->isAfter($end)) {
            $validator->errors()->add("chapters.$index.options.compare_to", $label.': the earlier period ends before it starts.');
        }
        if ($start->isAfter(today()) || $end->isAfter(today())) {
            $validator->errors()->add("chapters.$index.options.compare_to", $label.': the earlier period cannot be in the future.');
        }
        if ($recordFrom && $end->greaterThanOrEqualTo($recordFrom)) {
            $validator->errors()->add("chapters.$index.options.compare_to", $label.': the comparison has to end before this record starts.');
        }
    }

    private function isCalendarDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;
    }

    /** @param  array<string, mixed>  $options */
    private function validatePapers(Validator $validator, int $index, array $options, string $label): void
    {
        $types = array_merge(['all'], IncidentDocumentType::values());
        $this->validateIn($validator, $index, $options, 'document_type', $types, $label.': that paper is not one of the four forms.');
        $this->validateIn($validator, $index, $options, 'completion', ['any', 'complete', 'missing'], $label.': choose complete files, missing a form, or any.');
    }

    /** @param  array<string, mixed>  $options */
    private function validateSupport(Validator $validator, int $index, array $options, string $label): void
    {
        $this->validateIn($validator, $index, $options, 'status', ['new', 'reviewed', 'resolved', 'all'], $label.': choose a support status.');
        $categories = array_merge(['all'], array_keys(FeedbackSubmission::filterableCategories()));
        $this->validateIn($validator, $index, $options, 'category', $categories, $label.': that topic is not on the support desk.');
        $this->validateIn($validator, $index, $options, 'source', ['all', 'public', 'agency'], $label.': choose the public page or the staff desk.');
    }

    /** @param  array<string, mixed>  $options */
    private function validateActivity(Validator $validator, int $index, array $options, string $label): void
    {
        $name = $options['log_name'] ?? null;
        if (! is_string($name) || $name === '') {
            $validator->errors()->add("chapters.$index.options.log_name", $label.': choose a log, or all of them.');

            return;
        }
        if (strlen($name) > 64) {
            $validator->errors()->add("chapters.$index.options.log_name", $label.': that log name is too long.');

            return;
        }
        if ($name !== 'all' && ! ActivityLog::query()->where('log_name', $name)->exists()) {
            $validator->errors()->add("chapters.$index.options.log_name", $label.': that log is not in the audit trail.');
        }
    }

    /** @param  array<string, mixed>  $options */
    private function validateCase(Validator $validator, int $index, array $options, string $label): void
    {
        $value = $options['tracking_number'] ?? null;
        if (! is_string($value)) {
            $validator->errors()->add("chapters.$index.options.tracking_number", $label.': enter a valid tracking number.');

            return;
        }
        $tracking = trim($value);
        if ($tracking === '') {
            $validator->errors()->add("chapters.$index.options.tracking_number", $label.': enter a tracking number.');

            return;
        }
        if (strlen($tracking) > 32 || ! ReportRecord::query()->getConnection()->table('incidents')->where('tracking_number', $tracking)->exists()) {
            $validator->errors()->add("chapters.$index.options.tracking_number", 'No case uses that tracking number.');
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  list<string>  $allowed
     */
    private function validateIn(Validator $validator, int $index, array $options, string $key, array $allowed, string $message): void
    {
        $value = $options[$key] ?? null;
        if (! is_string($value) || ! in_array($value, $allowed, true)) {
            $validator->errors()->add("chapters.$index.options.$key", $message);
        }
    }

    /** @param  array<string, mixed>  $options */
    private function existsOrEmpty(Validator $validator, int $index, array $options, string $key, string $table, string $column, string $message): void
    {
        $value = $options[$key] ?? null;
        if ($value === null || $value === '') {
            return;
        }
        if ((! is_int($value) && ! is_string($value)) || ! ctype_digit((string) $value) || (int) $value < 1) {
            $validator->errors()->add("chapters.$index.options.$key", $message);

            return;
        }
        if (! ReportRecord::query()->getConnection()->table($table)->where($column, $value)->exists()) {
            $validator->errors()->add("chapters.$index.options.$key", $message);
        }
    }
}
