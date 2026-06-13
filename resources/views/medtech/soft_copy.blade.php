@extends('layouts.app')
@section('title', 'Soft Copy Requests')
@section('content')
    <h1>Soft Copy Requests</h1>
    <p class="page-subtitle">Requests from patients and doctors. Upload the file to fulfill each one.</p>

    @if($requests->isEmpty())
        <div class="empty-state">
            <i class="bi bi-cloud-arrow-down"></i>
            <p>No pending soft copy requests.</p>
        </div>
    @else
        @foreach($requests as $req)
            <div class="box">
                <p>
                    <strong>Request #{{ $req->result_request_id }}</strong> —
                    @if($req->patient_id)
                        Patient: {{ $req->patient?->user?->fullName() }}
                    @else
                        Doctor: {{ $req->doctor?->user?->fullName() }}
                    @endif
                </p>
                <p class="muted" style="margin-bottom:12px;">
                    Test: {{ $req->result?->requestItem?->test?->test_name ?? 'N/A' }}
                    <span>({{ $req->requested_at?->format('M d, Y g:i A') }})</span>
                </p>
                <form action="{{ route('medtech.softcopy.fulfill', $req->result_request_id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="file{{ $req->result_request_id }}">Upload File (PDF or image)</label>
                        <input type="file" name="result_file" id="file{{ $req->result_request_id }}" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <button type="submit" class="btn btn-small"><i class="bi bi-cloud-upload"></i> Upload &amp; Fulfill</button>
                </form>
            </div>
        @endforeach
    @endif
@endsection
