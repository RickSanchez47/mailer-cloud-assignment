@extends('layout')

@section('title', 'Your forms')

@section('content')
    <h1>{{ $account->name }}</h1>
    <p class="lead">Forms are private drafts until you publish them. Publishing never changes a form's past submissions, even if you edit and republish later.</p>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
            <h2 style="margin:0;">Forms</h2>
            <a href="{{ route('builder.forms.create') }}"><button class="primary">New form</button></a>
        </div>

        @if($forms->isEmpty())
            <p class="lead">No forms yet. Create your first one.</p>
        @else
            <table>
                <thead><tr><th>Name</th><th>Status</th><th>Public URL</th><th></th></tr></thead>
                <tbody>
                    @foreach($forms as $form)
                        <tr>
                            <td>{{ $form->name }}</td>
                            <td>
                                @if($form->published_version_id)
                                    <span class="badge published">Published · v{{ $form->publishedVersion->version_number }}</span>
                                @else
                                    <span class="badge draft">Draft</span>
                                @endif
                            </td>
                            <td>
                                @if($form->published_version_id)
                                    <code>{{ url('/forms/' . $account->api_key . '/' . $form->slug) }}</code>
                                @else
                                    <span class="lead">Not published yet</span>
                                @endif
                            </td>
                            <td class="actions">
                                <a href="{{ route('builder.forms.edit', $form) }}">Edit</a>
                                <a href="{{ route('builder.forms.submissions', $form) }}">Submissions</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
