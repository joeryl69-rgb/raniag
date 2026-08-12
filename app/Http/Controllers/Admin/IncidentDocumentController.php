<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentDocumentType;
use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIncidentDocumentRequest;
use App\Models\Incident;
use App\Models\IncidentDocument;
use App\Services\IncidentDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentDocumentController extends Controller
{
    public function __construct(
        private readonly IncidentDocumentService $incidentDocuments,
    ) {}

    public function index(Request $request): View
    {
        $statusFilter = $request->string('status')->value() ?: 'all';
        $validStatuses = [IncidentStatus::Resolved->value, IncidentStatus::Closed->value];
        $statuses = $statusFilter === 'all' ? $validStatuses : array_intersect([$statusFilter], $validStatuses);

        $query = Incident::query()
            ->whereIn('status', $statuses ?: $validStatuses)
            ->withCount('incidentDocuments')
            ->with(['incidentType']);

        $q = trim((string) $request->string('q'));
        if ($q !== '') {
            $query->where('tracking_number', 'like', "%{$q}%");
        }

        \App\Support\Filters::dateRange($query, 'created_at', [
            'date_from' => $request->string('date_from')->trim()->value(),
            'date_to' => $request->string('date_to')->trim()->value(),
        ]);

        $completionFilter = $request->string('completion')->value();
        if ($completionFilter === 'complete') {
            $query->has('incidentDocuments', '>=', count(IncidentDocumentType::cases()));
        } elseif ($completionFilter === 'missing') {
            $query->has('incidentDocuments', '<', count(IncidentDocumentType::cases()));
        }

        $incidents = $query->orderByDesc('created_at')->paginate(12)->appends($request->query());

        $documentTypes = IncidentDocumentType::cases();

        return view('admin.incident_documents.index', compact('incidents', 'documentTypes'));
    }

    public function store(StoreIncidentDocumentRequest $request, Incident $incident): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $document = $this->incidentDocuments->attachToIncident(
            incident: $incident,
            file: $request->file('file'),
            documentType: IncidentDocumentType::from($data['document_type']),
            isCameraCapture: (bool) ($data['is_camera_capture'] ?? false),
            uploadedBy: $request->user()->id,
            notes: $data['notes'] ?? null,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Document attached to incident.',
                'document' => $document,
            ], 201);
        }

        return redirect()
            ->route('admin.incidents.show', $incident->id)
            ->with('success', 'Document attached to incident.');
    }

    public function destroy(Request $request, Incident $incident, IncidentDocument $document): RedirectResponse|JsonResponse
    {
        abort_if($document->incident_id !== $incident->id, 404);

        $this->incidentDocuments->deleteFile($document);
        $document->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Document removed.']);
        }

        return redirect()
            ->route('admin.incidents.show', $incident->id)
            ->with('success', 'Document removed.');
    }
}
