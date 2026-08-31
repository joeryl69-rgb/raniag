<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\FeedbackSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /**
     * Support Center for signed-in staff (agency + personnel) — same
     * category-card form as the public one, but the submission is tagged
     * "Support Center" and auto-linked to the agency and staff account so
     * admins can see who filed it.
     */
    public function create(Request $request): View
    {
        return view('agency.support.create', [
            'categories' => FeedbackSubmission::CATEGORIES,
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(FeedbackSubmission::CATEGORIES))],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        FeedbackSubmission::create($data + [
            'submitted_via' => 'agency',
            'agency_id' => $user->agency_id,
            'submitted_by' => $user->id,
            'submitter_name' => $user->name,
            'submitter_email' => $user->email,
        ]);

        return redirect()->route('agency.support.create')->with('sent', true);
    }
}
