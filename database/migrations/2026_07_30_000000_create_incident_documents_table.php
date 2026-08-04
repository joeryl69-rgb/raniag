<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // One of App\Enums\IncidentDocumentType (call_taker_form, dispatch_form,
            // narrative_report, endorsement_sheet). Kept as a plain string column
            // (not a DB enum) so future document types can be added without a migration.
            $table->string('document_type', 32);

            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // True when captured live via the device camera in the browser, false when
            // picked from an existing file on disk — kept only for admin reference.
            $table->boolean('is_camera_capture')->default(false);

            $table->string('notes', 500)->nullable();

            $table->timestamps();

            $table->index(['incident_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_documents');
    }
};
