@extends('layouts.app')
@section('title', 'Soft Copy Requests')
@section('content')
    <h1>Soft Copy Requests</h1>
    <p class="page-subtitle">Requests from patients and doctors. Upload the file to fulfill each one.</p>

    @if($requests->isEmpty())
        <p class="muted">No pending soft copy requests.</p>
    @else
        @foreach($requests as $req)
            <div class="box">
                <strong>Request #{{ $req->result_request_id }}</strong> —
                @if($req->patient_id)
                    Patient: {{ $req->patient?->user?->fullName() }}
                @else
                    Doctor: {{ $req->doctor?->user?->fullName() }}
                @endif
                <br>
                Test: {{ $req->result?->requestItem?->test?->test_name ?? 'N/A' }}
                <span class="muted">({{ $req->requested_at?->format('M d, Y g:i A') }})</span>

                <form action="{{ route('medtech.softcopy.fulfill', $req->result_request_id) }}" method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                    @csrf
                    <div class="form-group">
                        <label for="file{{ $req->result_request_id }}">Upload File (PDF or image)</label>
                        <input type="file" name="result_file" id="file{{ $req->result_request_id }}" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <button type="submit" class="btn btn-small">Upload &amp; Fulfill</button>
                </form>
            </div>
        @endforeach
    @endif
@endsection
