@extends('layouts.app')
@section('title', 'Scheduled Lab Tests')
@section('content')
    <h1>Scheduled Laboratory Tests</h1>
    <p class="page-subtitle">Pending and in-progress lab requests. Upload a result for each test item.</p>

    @if($requests->isEmpty())
        <div class="empty-state">
            <i class="bi bi-eyedropper"></i>
            <p>No pending lab requests.</p>
        </div>
    @else
        @foreach($requests as $req)
            <div class="box">
                <p>
                    <strong>Request #{{ $req->lab_request_id }}</strong> —
                    {{ $req->patient?->user?->fullName() }} —
                    <span class="tag">{{ $req->status }}</span>
                    <span class="muted">({{ $req->request_at?->format('M d, Y g:i A') }})</span>
                </p>
                <p class="muted" style="margin-bottom:10px;">
                    @if($req->doctor)
                        Referred by Dr. {{ $req->doctor?->user?->fullName() }}
                    @else
                        Patient self-requested (no doctor consultation)
                    @endif
                    @if($req->labAppointment)
                        &mdash; Lab appointment: {{ $req->labAppointment->scheduled_at?->format('M d, Y g:i A') }}
                    @endif
                </p>
                <table>
                    <tr><th>Test</th><th>Status</th><th>Result</th><th></th></tr>
                    @foreach($req->items as $item)
                        <tr>
                            <td>{{ $item->test?->test_name }}</td>
                            <td>{{ $item->status }}</td>
                            <td>
                                @if($item->result)
                                    {{ $item->result->result_value ?? '—' }} {{ $item->result->unit }}
                                @else
                                    <span class="muted">Pending</span>
                                @endif
                            </td>
                            <td class="row-actions">
                                <a href="{{ route('medtech.lab.result.create', $item->request_item_id) }}" class="btn btn-small">
                                    {{ $item->result ? 'Update' : 'Encode' }} Result
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif
@endsection
