@extends('layouts.app')
@section('title', 'Scheduled Check-ups')
@section('content')

@if($doctor)
    {{-- ── Doctor view: own upcoming appointments ──────────────────── --}}
    <h1>Scheduled Check-ups</h1>
    <p class="page-subtitle">Your upcoming appointments. Update the status or open a patient chart below.</p>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    @if($appointments->isEmpty())
        <div class="empty-state">
            <i class="bi bi-calendar2-x"></i>
            <p>No upcoming scheduled check-ups.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Patient</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                @foreach($appointments as $a)
                    <tr>
                        <td style="white-space:nowrap;">{{ $a->appointment_at?->format('M d, Y g:i A') }}</td>
                        <td>{{ $a->patient?->user?->fullName() }}</td>
                        <td>{{ $a->reason_for_visit ?? '—' }}</td>
                        <td>{{ $a->status?->status_name }}</td>
                        <td class="row-actions">
                            @if($a->patient)
                                <a href="{{ route('doctor.patients.show', $a->patient->patient_id) }}" class="btn btn-small">Open Chart</a>
                            @endif

                            @if(in_array($a->status_id, [4,5,6,7]))
                                @php $badgeColor = $a->status_id === 4 ? 'tag-green' : ($a->status_id === 7 ? '' : 'tag-red'); @endphp
                                <span class="tag {{ $badgeColor }}">{{ $a->status?->status_name }}</span>
                            @else
                                {{-- Status dropdown (active appointment only) --}}
                                <form action="{{ route('doctor.appointments.status', $a->appointment_id) }}" method="POST"
                                      class="inline-form appt-status-form" style="display:inline-flex;align-items:center;gap:4px;flex-wrap:wrap;">
                                    @csrf
                                    <select name="status_id" onchange="handleApptStatusChange(this)"
                                            style="padding:3px 6px;font-size:0.82em;">
                                        <option value="" disabled selected>Scheduled</option>
                                        <option value="4">Completed</option>
                                        <option value="5">Cancelled</option>
                                        <option value="6">No Show</option>
                                        <option value="7">Rescheduled</option>
                                    </select>
                                    <span class="reschedule-fields" style="display:none;align-items:center;gap:4px;">
                                        <input type="date" name="reschedule_date" style="padding:3px;font-size:0.82em;">
                                        <input type="time" name="reschedule_time" style="padding:3px;font-size:0.82em;">
                                    </span>
                                    <button type="submit" class="btn btn-small appt-apply-btn" disabled>Apply</button>
                                </form>

                                {{-- Void button --}}
                                <button type="button" class="btn btn-small btn-outline"
                                        data-appt-id="{{ $a->appointment_id }}"
                                        onclick="openApptVoidModal(this)">Void</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

@else
    {{-- ── Admin view: ALL appointments across the system ─────────── --}}
    <h1>Scheduled Check-ups</h1>
    <p class="page-subtitle">All appointments across the clinic. Use the status dropdown to update or manage individual records.</p>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    @if($appointments->isEmpty())
        <div class="empty-state">
            <i class="bi bi-calendar2-x"></i>
            <p>No scheduled check-ups found.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Doctor</th>
                    <th>Patient</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                @foreach($appointments as $a)
                    <tr>
                        <td style="white-space:nowrap;">{{ $a->appointment_at?->format('M d, Y g:i A') }}</td>
                        <td>{{ $a->doctor?->user?->fullName() ?? '—' }}</td>
                        <td>{{ $a->patient?->user?->fullName() ?? '—' }}</td>
                        <td>{{ $a->reason_for_visit ?? '—' }}</td>
                        <td>{{ $a->status?->status_name }}</td>
                        <td class="row-actions">
                            @if($a->patient)
                                <a href="{{ route('doctor.patients.show', $a->patient->patient_id) }}" class="btn btn-small">Open Chart</a>
                            @endif

                            @if(in_array($a->status_id, [4,5,6,7]))
                                @php $badgeColor = $a->status_id === 4 ? 'tag-green' : ($a->status_id === 7 ? '' : 'tag-red'); @endphp
                                <span class="tag {{ $badgeColor }}">{{ $a->status?->status_name }}</span>
                            @else
                                {{-- Status dropdown (active appointment only) --}}
                                <form action="{{ route('doctor.appointments.status', $a->appointment_id) }}" method="POST"
                                      class="inline-form appt-status-form" style="display:inline-flex;align-items:center;gap:4px;flex-wrap:wrap;">
                                    @csrf
                                    <select name="status_id" onchange="handleApptStatusChange(this)"
                                            style="padding:3px 6px;font-size:0.82em;">
                                        <option value="" disabled selected>Scheduled</option>
                                        <option value="4">Completed</option>
                                        <option value="5">Cancelled</option>
                                        <option value="6">No Show</option>
                                        <option value="7">Rescheduled</option>
                                    </select>
                                    <span class="reschedule-fields" style="display:none;align-items:center;gap:4px;">
                                        <input type="date" name="reschedule_date" style="padding:3px;font-size:0.82em;">
                                        <input type="time" name="reschedule_time" style="padding:3px;font-size:0.82em;">
                                    </span>
                                    <button type="submit" class="btn btn-small appt-apply-btn" disabled>Apply</button>
                                </form>

                                {{-- Void button --}}
                                <button type="button" class="btn btn-small btn-outline"
                                        data-appt-id="{{ $a->appointment_id }}"
                                        onclick="openApptVoidModal(this)">Void</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $appointments->links() }}
        </div>
    @endif
@endif

{{-- ── Appointment Void Request Modal ──────────────────────────────── --}}
<div id="appt-void-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;padding:24px 28px;max-width:480px;width:calc(100% - 40px);box-shadow:0 8px 32px rgba(0,0,0,0.18);">
        <h3 style="margin-top:0;margin-bottom:6px;">Request Appointment Void</h3>
        <p class="muted" style="margin-bottom:14px;font-size:0.9em;">
            This submits a void request to the admin queue. The appointment remains active until an admin approves the request.
        </p>
        <form action="{{ route('void.store') }}" method="POST">
            @csrf
            <input type="hidden" name="table_name" value="appointments">
            <input type="hidden" id="appt-void-record-id" name="record_id" value="">
            <div class="form-group">
                <label>Reason <span class="req">*</span></label>
                <textarea name="reason" placeholder="Reason for void request (min 10 characters)"
                          required minlength="10" style="min-height:70px;"></textarea>
            </div>
            <div style="display:flex;gap:8px;margin-top:6px;">
                <button type="submit" class="btn btn-small">Submit Request</button>
                <button type="button" class="btn btn-small btn-outline"
                        onclick="document.getElementById('appt-void-modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function handleApptStatusChange(select) {
    var form         = select.closest('form');
    var reschedFields = form.querySelector('.reschedule-fields');
    var applyBtn     = form.querySelector('.appt-apply-btn');

    if (select.value === '7') {
        reschedFields.style.display = 'inline-flex';
    } else {
        reschedFields.style.display = 'none';
        reschedFields.querySelector('input[name="reschedule_date"]').value = '';
        reschedFields.querySelector('input[name="reschedule_time"]').value = '';
    }

    applyBtn.disabled = (select.value === '');
}

function openApptVoidModal(btn) {
    document.getElementById('appt-void-record-id').value = btn.dataset.apptId;
    document.getElementById('appt-void-modal').style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('appt-void-modal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.style.display = 'none';
        });
    }
});
</script>
@endpush

@endsection
