@extends('layout')

@section('title', 'Choose an account')

@section('content')
    <h1>Choose an account</h1>
    <p class="lead">This stands in for real login for the demo — pick an existing demo account or create a new one. Each account's forms and submissions are isolated from every other account's.</p>

    <div class="panel">
        <h2>Existing accounts</h2>
        @if($accounts->isEmpty())
            <p class="lead">No accounts yet — create one below.</p>
        @else
            <table>
                <thead><tr><th>Name</th><th>API key</th><th></th></tr></thead>
                <tbody>
                    @foreach($accounts as $account)
                        <tr>
                            <td>{{ $account->name }}</td>
                            <td><code>{{ $account->api_key }}</code></td>
                            <td>
                                <form method="POST" action="{{ route('builder.use-account', $account) }}">
                                    @csrf
                                    <button type="submit" class="secondary">Use this account</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="panel">
        <h2>Create a new account</h2>
        <form method="POST" action="{{ route('builder.create-account') }}">
            @csrf
            <label for="name">Account name</label>
            <input type="text" id="name" name="name" placeholder="e.g. Acme Marketing" required>
            <br><br>
            <button type="submit" class="primary">Create and use</button>
        </form>
    </div>
@endsection
