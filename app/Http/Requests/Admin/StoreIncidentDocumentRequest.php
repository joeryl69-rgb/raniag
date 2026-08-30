<?php

namespace App\Http\Requests\Admin;

use App\Enums\IncidentDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(IncidentDocumentType::values())],
            // Photo capture (camera or gallery) is the primary use case; PDF is also
            // accepted for the rare case where the admin already has a digital scan.
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'],
            'is_camera_capture' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'extracted_text' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => 'Please specify which form this document is.',
            'document_type.in' => 'Invalid document type selected.',
            'file.required' => 'Please attach a photo or file of the document.',
            'file.mimes' => 'Only JPG, PNG, WEBP, or PDF files are accepted.',
            'file.max' => 'The file may not be larger than 15MB.',
        ];
    }
}
