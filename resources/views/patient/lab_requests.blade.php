@extends('layouts.app')
@section('title', 'My Lab Results')
@section('content')
    <h1>My Lab Results</h1>
    <p class="page-subtitle">Request a soft copy of a result. Once the MedTech uploads it, a Download button appears here.</p>

    <h2>My Results</h2>
    @if($results->isEmpty())
        <div class="empty-state">
            <i class="bi bi-file-earmark-medical"></i>
            <p>You have no laboratory results yet.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr><th>Test</th><th>Result</th><th>Status</th><th>Released</th><th>Soft Copy</th></tr>
                @foreach($results as $r)
                    @php($softCopy = $requestStatus[$r->result_id] ?? null)
                    <tr>
                        <td>{{ $r->requestItem?->test?->test_name }}</td>
                        <td>{{ $r->result_value ?? '—' }} {{ $r->unit }}</td>
                        <td>{{ $r->workflow_status }}</td>
                        <td>{{ $r->created_at?->format('M d, g:i A') ?? '—' }}</td>
                        <td>
                            @if($softCopy === 'Fulfilled' && $r->result_file_path)
                                <a href="{{ asset('storage/'.$r->result_file_path) }}" class="btn btn-small" target="_blank">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @elseif($softCopy === 'Pending')
                                <span class="muted">Requested — awaiting MedTech</span>
                            @else
                                <form action="{{ route('patient.lab.store') }}" method="POST" class="inline-form">
                                    @csrf
                                    <input type="hidden" name="result_id" value="{{ $r->result_id }}">
                                    <button type="submit" class="btn btn-small btn-outline">Request Soft Copy</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <h2>My Requests</h2>
    @if($myRequests->isEmpty())
        <p class="muted">You have not requested any soft copies yet.</p>
    @else
        <div class="table-card">
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
        </div>
    @endif
@endsection
