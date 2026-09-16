@extends('layout')

@section('title', 'Submissions — ' . $form->name)

@section('content')
    <h1>{{ $form->name }}</h1>
    <p class="lead"><a href="{{ route('builder.forms.edit', $form) }}">&larr; Back to editing</a></p>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 style="margin:0;">Submissions ({{ $submissions->total() }})</h2>
            <a href="{{ route('builder.forms.export', $form) }}"><button class="secondary">Export CSV</button></a>
        </div>

        @if($submissions->isEmpty())
            <p class="lead">No submissions yet.</p>
        @else
            @php $columns = array_keys($submissions->first()->data); @endphp
            <table>
                <thead>
                    <tr>
                        <th>Submitted</th>
                        @foreach($columns as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($submissions as $submission)
                        <tr>
                            <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
                            @foreach($columns as $col)
                                <td>{{ is_array($submission->data[$col] ?? null) ? implode(', ', $submission->data[$col]) : ($submission->data[$col] ?? '') }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:16px;">{{ $submissions->links() }}</div>
        @endif
    </div>
@endsection
