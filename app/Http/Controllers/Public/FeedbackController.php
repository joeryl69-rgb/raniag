<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FeedbackSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:feedback,concern,suggestion,bug'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'submitter_name' => ['nullable', 'string', 'max:150'],
            'submitter_email' => ['nullable', 'email', 'max:150'],
            // Honeypot — a hidden field real users never fill in.
            'website' => ['prohibited'],
        ]);

        unset($data['website']);

        FeedbackSubmission::create($data);

        return back()->with('success', 'Thank you — your message has been sent to the MDRRMO Pamplona team.');
    }
}
