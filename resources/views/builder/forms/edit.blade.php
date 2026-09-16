@extends('layout')

@section('title', $form ? 'Edit ' . $form->name : 'New form')

@section('content')
    <h1>{{ $form ? $form->name : 'New form' }}</h1>
    @if($form && $form->published_version_id)
        <p class="lead">
            Published as v{{ $form->publishedVersion->version_number }} at
            <code>{{ url('/forms/' . $form->account->api_key . '/' . $form->slug) }}</code>.
            Editing below only changes the draft — the published version and its submissions are untouched until you publish again.
        </p>
    @else
        <p class="lead">Add fields below, then save the draft. Publish when you're ready to make it public.</p>
    @endif

    <div class="panel">
        @if(!$form)
            <label for="form-name">Form name</label>
            <input type="text" id="form-name" placeholder="e.g. Newsletter Signup" required>
        @endif

        <h2 style="margin-top: 20px;">Fields</h2>
        <div id="field-editor"></div>
        <button type="button" id="add-field-btn" class="secondary">+ Add field</button>

        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border);">

        <div style="display:flex; gap:12px; align-items:center;">
            <button type="button" id="save-draft-btn" class="secondary">Save draft</button>
            <button type="button" id="publish-btn" class="primary">Publish</button>
            <span id="status-message" class="lead" style="margin:0;"></span>
        </div>
    </div>

    <div class="panel">
        <p class="lead" style="margin:0;">
            Field <span class="field-key">key</span> becomes the JSON key submissions are stored under — keep it short and stable, since renaming it after publishing changes what future submissions look like (past submissions keep whatever key was live when they were submitted).
        </p>
    </div>
@endsection

@section('scripts')
@endsection

@push('scripts')
@endpush

<script>
    window.__CSRF__ = document.querySelector('meta[name="csrf-token"]').content;
    window.__FORM_ID__ = @json($form->id ?? null);
    window.__IS_NEW__ = @json($form === null);
    window.__INITIAL_FIELDS__ = @json(optional($draft)->schema['fields'] ?? []);
</script>
<script src="{{ asset('js/form-editor.js') }}"></script>
