<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * "just like google can change the text into tagalog" — public-facing
     * pages (landing page, report form, tracker) can be read in Tagalog or
     * English. The choice is remembered in the session so it persists as
     * the visitor moves between pages, without needing an account.
     *
     * Scope: only resources/lang/tl.json is populated so far (landing page
     * strings). Any string not yet translated falls back to its English
     * source text automatically — that's how Laravel's __() works when a
     * key is missing from the active locale's file — so this is safe to
     * turn on globally even before every page has Tagalog copy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (in_array($locale, ['en', 'tl'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
