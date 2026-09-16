<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Form;
use App\Services\FormSchema\SchemaSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Session-scoped dashboard controller behind the demo frontend.
 *
 * This intentionally stands in for real account authentication, which is
 * out of scope for this assessment slice (see ARCHITECTURE.md /
 * README_INTEGRATION.md "Built vs. Designed"). "Logging in" here just
 * means picking or creating a demo Account and storing its id in
 * session — the EnsureAccountSelected middleware enforces that a demo
 * account is selected before any of these routes run.
 *
 * The Sanctum-protected JSON API in App\Http\Controllers\Api performs
 * the same core operations (create/update draft, publish, list/export
 * submissions) in the shape a real production client would call it.
 */
class BuilderController extends Controller
{
    public function __construct(protected SchemaSanitizer $sanitizer)
    {
    }

    public function chooseAccount()
    {
        return view('builder.choose-account', [
            'accounts' => Account::orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function createAccount(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);

        $account = Account::create([
            'name' => $validated['name'],
            'api_key' => Str::random(32),
        ]);

        session(['account_id' => $account->id]);

        return redirect()->route('builder.forms.index');
    }

    public function useAccount(Request $request, Account $account)
    {
        session(['account_id' => $account->id]);

        return redirect()->route('builder.forms.index');
    }

    public function index(Request $request)
    {
        $account = $this->currentAccount();

        return view('builder.forms.index', [
            'account' => $account,
            'forms' => $account->forms()->with('publishedVersion')->latest()->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->currentAccount();

        return view('builder.forms.edit', [
            'form' => null,
            'draft' => null,
        ]);
    }

    public function store(Request $request)
    {
        $account = $this->currentAccount();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'schema' => 'required|array',
        ]);

        $form = $account->forms()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
        ]);

        $form->versions()->create([
            'version_number' => 1,
            'schema' => $this->sanitizer->sanitize($validated['schema']),
            'status' => 'draft',
        ]);

        return response()->json(['id' => $form->id]);
    }

    public function edit(Request $request, Form $form)
    {
        $this->authorizeForm($form);

        return view('builder.forms.edit', [
            'form' => $form,
            'draft' => $form->draftVersion(),
        ]);
    }

    public function updateDraft(Request $request, Form $form)
    {
        $this->authorizeForm($form);

        $validated = $request->validate(['schema' => 'required|array']);
        $sanitized = $this->sanitizer->sanitize($validated['schema']);

        $draft = $form->draftVersion();

        if ($draft) {
            // In-place draft edits never touch the published version or
            // any submission tied to it.
            $draft->update(['schema' => $sanitized]);
        } else {
            $nextVersionNumber = ($form->versions()->max('version_number') ?? 0) + 1;
            $draft = $form->versions()->create([
                'version_number' => $nextVersionNumber,
                'schema' => $sanitized,
                'status' => 'draft',
            ]);
        }

        return response()->json(['id' => $draft->id]);
    }

    public function publish(Request $request, Form $form)
    {
        $this->authorizeForm($form);

        $draft = $form->draftVersion();
        abort_if(!$draft, 422, 'No draft version to publish.');

        $draft->update(['status' => 'published', 'published_at' => now()]);

        // Publishing only repoints the form's pointer to an immutable,
        // already-created version — it never rewrites a past version, so
        // existing submissions stay correctly tied to their schema.
        $form->update(['published_version_id' => $draft->id]);

        return response()->json(['status' => 'published', 'version' => $draft->version_number]);
    }

    public function submissions(Request $request, Form $form)
    {
        $this->authorizeForm($form);

        return view('builder.forms.submissions', [
            'form' => $form,
            'submissions' => $form->submissions()->orderByDesc('created_at')->paginate(20),
        ]);
    }

    public function export(Request $request, Form $form)
    {
        $this->authorizeForm($form);

        return response()->streamDownload(function () use ($form) {
            $out = fopen('php://output', 'w');
            $headerWritten = false;

            $form->submissions()->orderBy('id')->chunk(500, function ($chunk) use ($out, &$headerWritten) {
                foreach ($chunk as $submission) {
                    if (!$headerWritten) {
                        fputcsv($out, array_merge(['id', 'created_at'], array_keys($submission->data)));
                        $headerWritten = true;
                    }
                    fputcsv($out, array_merge([$submission->id, $submission->created_at], array_values($submission->data)));
                }
            });

            fclose($out);
        }, "form-{$form->id}-submissions.csv");
    }

    protected function currentAccount(): Account
    {
        return Account::findOrFail(session('account_id'));
    }

    protected function authorizeForm(Form $form): void
    {
        abort_if($form->account_id !== $this->currentAccount()->id, 403);
    }
}
