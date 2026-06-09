<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class SubmitResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAgency();
    }

    public function rules(): array
    {
        $maxFiles = (int) config('raniag.evidence.max_files', 5);
        $maxSize = (int) config('raniag.evidence.max_size_kb', 5120);
        $mimes = config('raniag.evidence.allowed_mimes', []);

        return [
            'summary' => ['required', 'string', 'min:20', 'max:2000'],
            'actions_taken' => ['required', 'string', 'min:20', 'max:5000'],
            'evidence' => ['sometimes', 'array', 'max:' . $maxFiles],
            'evidence.*' => ['file', 'max:' . $maxSize, 'mimes:' . implode(',', $mimes)],
        ];
    }

    public function messages(): array
    {
        return [
            'summary.required' => 'Please provide a summary of the resolution.',
            'summary.min' => 'Summary must be at least 20 characters.',
            'summary.max' => 'Summary cannot exceed 2000 characters.',
            'actions_taken.required' => 'Please describe the actions taken.',
            'actions_taken.min' => 'Actions description must be at least 20 characters.',
            'actions_taken.max' => 'Actions description cannot exceed 5000 characters.',
            'evidence.max' => 'Maximum ' . config('raniag.evidence.max_files', 5) . ' files allowed.',
            'evidence.*.file' => 'Each evidence must be a valid file.',
            'evidence.*.max' => 'Each file cannot exceed ' . config('raniag.evidence.max_size_kb', 5120) . 'KB.',
            'evidence.*.mimes' => 'Invalid file type. Allowed types: ' . implode(', ', config('raniag.evidence.allowed_mimes', [])),
        ];
    }
}
