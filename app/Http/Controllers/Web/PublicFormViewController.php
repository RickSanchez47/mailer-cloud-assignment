<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

/**
 * Renders the page shell for a public form. All the actual schema
 * fetching and dynamic rendering happens client-side in
 * public/js/public-form.js against the existing JSON API — this
 * controller just needs to hand the account key + slug to the page.
 */
class PublicFormViewController extends Controller
{
    public function show(string $accountApiKey, string $slug)
    {
        return view('public.show', compact('accountApiKey', 'slug'));
    }
}
