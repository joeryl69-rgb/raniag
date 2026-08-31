<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FeedbackSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /** Support Center — category picker + message form (public/anonymous). */
    public function create(): View
    {
        return view('public.support.create', [
            'categories' => FeedbackSubmission::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(FeedbackSubmission::CATEGORIES))],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'submitter_name' => ['nullable', 'string', 'max:150'],
            'submitter_email' => ['nullable', 'email', 'max:150'],
            // Honeypot — a hidden field real users never fill in.
            'website' => ['prohibited'],
        ]);

        unset($data['website']);
        $data['submitted_via'] = 'public';

        FeedbackSubmission::create($data);

        return back()->with('sent', true);
    }
}
