<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSubmissionSideEffects;
use App\Models\Form;
use App\Models\Submission;
use App\Services\FormSchema\DynamicValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Fully public, anonymous, cross-origin endpoints — this is the surface
 * that takes bursty traffic from embedded forms across the open internet.
 */
class PublicFormController extends Controller
{
    public function show(string $accountApiKey, string $slug)
    {
        $form = $this->findPublishedForm($accountApiKey, $slug);

        // Only the sanitized, published schema is ever exposed here —
        // draft edits are invisible to the public until explicitly published.
        return response()->json([
            'form_id' => $form->id,
            'version' => $form->publishedVersion->version_number,
            'schema' => $form->publishedVersion->schema,
        ]);
    }

    public function submit(Request $request, string $accountApiKey, string $slug, DynamicValidator $validator)
    {
        $form = $this->findPublishedForm($accountApiKey, $slug);
        $version = $form->publishedVersion;

        // Per-form, per-IP rate limiting so one noisy/abusive source can't
        // degrade the form for everyone else.
        $rateLimitKey = "submit:{$form->id}:{$request->ip()}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            return response()->json(['message' => 'Too many submissions, please slow down.'], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Honeypot: a field real users never see or fill in. Bots that
        // fill every field trip this; we accept-and-drop rather than
        // reveal the check by rejecting.
        if ($request->filled('website')) {
            return response()->json(['message' => 'Submission received.'], 201);
        }

        $validated = $validator->validate($version->schema, $request->all())->validate();

        // The transaction + synchronous write inside the request is the
        // durability boundary for the working slice: the HTTP response
        // only succeeds once the row is committed, so nothing is lost
        // between "clicked submit" and "durably stored". ARCHITECTURE.md
        // covers how this is fronted by a buffering layer at higher scale.
        $submission = DB::transaction(function () use ($form, $version, $validated, $request) {
            return Submission::create([
                'account_id' => $form->account_id,
                'form_id' => $form->id,
                'form_version_id' => $version->id,
                'data' => $this->sanitizeData($validated['data']),
                'ip_hash' => hash('sha256', (string) $request->ip()),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
                'created_at' => now(),
            ]);
        });

        // Non-critical side effects (webhooks, notifications, search
        // indexing) are queued so they can never slow down or block the
        // public response path, and can retry independently on failure.
        ProcessSubmissionSideEffects::dispatch($submission->id);

        return response()->json(['message' => 'Submission received.', 'id' => $submission->id], 201);
    }

    protected function findPublishedForm(string $accountApiKey, string $slug): Form
    {
        $form = Form::query()
            ->whereHas('account', fn ($q) => $q->where('api_key', $accountApiKey))
            ->where('slug', $slug)
            ->with('publishedVersion')
            ->firstOrFail();

        abort_if(!$form->published_version_id, 404);

        return $form;
    }

    protected function sanitizeData(array $data): array
    {
        return collect($data)->map(function ($value) {
            if (is_string($value)) {
                return strip_tags($value);
            }
            if (is_array($value)) {
                return array_map(fn ($v) => is_string($v) ? strip_tags($v) : $v, $value);
            }
            return $value;
        })->all();
    }
}
