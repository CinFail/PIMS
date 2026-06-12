@extends('layouts.app')
@section('title', 'Request a Lab Result')
@section('content')
    <h1>Request a Lab Result</h1>
    <p class="page-subtitle">Ask the MedTech for a soft copy of one of your results.</p>

    <h2>My Results</h2>
    @if($results->isEmpty())
        <p class="muted">You have no laboratory results yet.</p>
    @else
        <table>
            <tr><th>Test</th><th>Result</th><th>Status</th><th>Soft Copy</th><th></th></tr>
            @foreach($results as $r)
                <tr>
                    <td>{{ $r->requestItem?->test?->test_name }}</td>
                    <td>{{ $r->result_value ?? '—' }} {{ $r->unit }}</td>
                    <td>{{ $r->workflow_status }}</td>
                    <td>
                        @if($r->result_file_path)
                            <a href="{{ asset('storage/'.$r->result_file_path) }}" target="_blank">Download</a>
                        @else
                            <span class="muted">Not uploaded</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('patient.lab.store') }}" method="POST" class="inline-form">
                            @csrf
                            <input type="hidden" name="result_id" value="{{ $r->result_id }}">
                            <button type="submit" class="btn btn-small">Request Soft Copy</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>My Requests</h2>
    @if($myRequests->isEmpty())
        <p class="muted">You have not requested any soft copies yet.</p>
    @else
        <table>
            <tr><th>Requested</th><th>Test</th><th>Status</th></tr>
            @foreach($myRequests as $req)
                <tr>
                    <td>{{ $req->requested_at?->format('M d, Y g:i A') }}</td>
                    <td>{{ $req->result?->requestItem?->test?->test_name }}</td>
                    <td>{{ $req->status }}</td>
                </tr>
            @endforeach
        </table>
    @endif
@endsection
