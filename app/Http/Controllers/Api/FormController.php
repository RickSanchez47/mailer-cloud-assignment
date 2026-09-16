<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\FormSchema\SchemaSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Authenticated, tenant-scoped form-building endpoints. Every query is
 * anchored on $request->user()->account so one account can never see or
 * mutate another's forms — see ARCHITECTURE.md for why this is enforced
 * per-query rather than via a global auth-context scope (public routes
 * need cross-account lookups by design).
 */
class FormController extends Controller
{
    public function __construct(protected SchemaSanitizer $sanitizer)
    {
    }

    public function index(Request $request)
    {
        return $request->user()->account->forms()
            ->with('publishedVersion')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'schema' => 'required|array',
            'schema.fields' => 'required|array|min:1',
        ]);

        $form = $request->user()->account->forms()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
        ]);

        $form->versions()->create([
            'version_number' => 1,
            'schema' => $this->sanitizer->sanitize($validated['schema']),
            'status' => 'draft',
        ]);

        return response()->json($form->load('versions'), 201);
    }

    public function updateDraft(Request $request, Form $form)
    {
        $this->authorizeAccount($request, $form);

        $validated = $request->validate([
            'schema' => 'required|array',
            'schema.fields' => 'required|array|min:1',
        ]);

        $sanitized = $this->sanitizer->sanitize($validated['schema']);
        $draft = $form->draftVersion();

        if ($draft) {
            // In-place draft edits never touch the published version or
            // any submission already tied to it.
            $draft->update(['schema' => $sanitized]);

            return $draft;
        }

        $nextVersionNumber = ($form->versions()->max('version_number') ?? 0) + 1;

        return $form->versions()->create([
            'version_number' => $nextVersionNumber,
            'schema' => $sanitized,
            'status' => 'draft',
        ]);
    }

    public function publish(Request $request, Form $form)
    {
        $this->authorizeAccount($request, $form);

        $draft = $form->draftVersion();
        abort_if(!$draft, 422, 'No draft version to publish.');

        $draft->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Publishing only repoints the form's pointer to an immutable,
        // already-created version row. It never rewrites a past version,
        // so submissions stay correctly tied to the schema that produced
        // them even after this form is edited again.
        $form->update(['published_version_id' => $draft->id]);

        return $form->fresh('publishedVersion');
    }

    protected function authorizeAccount(Request $request, Form $form): void
    {
        abort_if($form->account_id !== $request->user()->account_id, 403);
    }
}
