<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FeedbackReplyMail;
use App\Models\FeedbackSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /** Tags allowed from the rich-text (Quill) editor response. */
    private const ALLOWED_HTML = '<p><br><strong><b><em><i><u><s><ul><ol><li><a><h1><h2><h3><blockquote>';

    public function index(Request $request): View
    {
        $query = FeedbackSubmission::with(['reviewer', 'replier'])->latest();

        if ($request->filled('status') && $request->string('status')->value() !== 'all') {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->filled('category') && $request->string('category')->value() !== 'all') {
            $query->where('category', $request->string('category')->value());
        }

        $submissions = $query->paginate(15)->withQueryString();

        $counts = [
            'new' => FeedbackSubmission::where('status', 'new')->count(),
            'reviewed' => FeedbackSubmission::where('status', 'reviewed')->count(),
            'resolved' => FeedbackSubmission::where('status', 'resolved')->count(),
        ];

        return view('admin.feedback.index', compact('submissions', 'counts'));
    }

    public function update(Request $request, FeedbackSubmission $feedback): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,reviewed,resolved'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['reviewed_by'] = $request->user()->id;
        $data['reviewed_at'] = now();

        $feedback->update($data);

        return back()->with('success', 'Feedback updated.');
    }

    /**
     * Send the admin's rich-text reply by email to the submitter (only
     * possible when they left an email address) and record it on the
     * submission so the reply history is visible in the admin list too.
     */
    public function reply(Request $request, FeedbackSubmission $feedback): RedirectResponse
    {
        abort_if(! $feedback->submitter_email, 422, 'This submission has no email address to reply to.');

        $data = $request->validate([
            'admin_reply' => ['required', 'string', 'max:20000'],
        ]);

        $safeHtml = strip_tags($data['admin_reply'], self::ALLOWED_HTML);

        $feedback->update([
            'admin_reply' => $safeHtml,
            'replied_by' => $request->user()->id,
            'replied_at' => now(),
            'status' => $feedback->status === 'new' ? 'reviewed' : $feedback->status,
        ]);

        Mail::to($feedback->submitter_email)->send(new FeedbackReplyMail($feedback->fresh()));

        return back()->with('success', 'Reply sent to '.$feedback->submitter_email.'.');
    }
}
