@extends('layouts.app')
@section('title', 'Void Requests')
@section('content')
    <h1>Void Requests</h1>
    <p class="page-subtitle">Pending void requests submitted by doctors and medical technologists. Approve to permanently void the record, or reject to leave it unchanged.</p>

    @php
        $labels = [
            'diagnoses'         => 'Diagnosis',
            'consultations'     => 'Consultation',
            'lab_results'       => 'Lab Result',
            'lab_requests'      => 'Lab Request',
            'lab_request_items' => 'Lab Request Item',
            'appointments'      => 'Appointment',
            'prescriptions'     => 'Prescription',
        ];
    @endphp

    @if($requests->isEmpty())
        <div class="empty-state">
            <i class="bi bi-x-octagon"></i>
            <p>No void requests found.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr>
                    <th>Record</th>
                    <th>Requested By</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
                @foreach($requests as $vr)
                    <tr>
                        <td>
                            <strong>{{ $labels[$vr->table_name] ?? $vr->table_name }}</strong>
                            <span class="muted">#{{ $vr->record_id }}</span>
                        </td>
                        <td>{{ $vr->requester?->fullName() ?? 'Unknown' }}</td>
                        <td>{{ $vr->reason }}</td>
                        <td>
                            @if($vr->status === 'Pending')
                                <span class="tag" style="background:#e67e22;color:#fff;">Pending</span>
                            @elseif($vr->status === 'Approved')
                                <span class="tag" style="background:#27ae60;color:#fff;">Approved</span>
                                <div class="muted" style="font-size:0.8em;">by {{ $vr->reviewer?->fullName() }} &bull; {{ $vr->reviewed_at?->format('M d, Y') }}</div>
                            @else
                                <span class="tag" style="background:#7f8c8d;color:#fff;">Rejected</span>
                                <div class="muted" style="font-size:0.8em;">by {{ $vr->reviewer?->fullName() }} &bull; {{ $vr->reviewed_at?->format('M d, Y') }}</div>
                            @endif
                        </td>
                        <td class="muted">{{ $vr->created_at?->format('M d, Y g:i A') }}</td>
                        <td class="row-actions">
                            @if($vr->status === 'Pending')
                                <form action="{{ route('admin.void.approve', $vr->id) }}" method="POST" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-small"
                                            onclick="return confirm('Approve void? This will permanently mark the record as voided.')">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
                                <form action="{{ route('admin.void.reject', $vr->id) }}" method="POST" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-small btn-outline">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $requests->links() }}
        </div>
    @endif
@endsection
