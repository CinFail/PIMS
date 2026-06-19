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
                    <th style="white-space:nowrap;">Date &amp; Time</th>
                    <th>Patient</th>
                    <th>Reason</th>
                    <th style="white-space:nowrap;">Status</th>
                    <th></th>
                </tr>
                @foreach($appointments as $a)
                    @php
                        $isTerminal = in_array($a->status_id, [4,5,6,7]);
                        $statusBadge = match($a->status_id) {
                            4 => 'tag-green',
                            5, 6 => 'tag-red',
                            7 => '',
                            default => '',
                        };
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;">
                            <span style="font-weight:600;">{{ $a->appointment_at?->format('M d, Y') }}</span>
                            <br><span class="muted" style="font-size:0.85em;">{{ $a->appointment_at?->format('g:i A') }}</span>
                        </td>
                        <td>
                            <span style="font-weight:500;">{{ $a->patient?->user?->fullName() ?? '—' }}</span>
                            @if($a->appointment_type)
                                <br><span class="muted" style="font-size:0.82em;">{{ $a->appointment_type }}</span>
                            @endif
                        </td>
                        <td style="max-width:200px;">
                            <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                  title="{{ $a->reason_for_visit }}">{{ $a->reason_for_visit ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="tag {{ $statusBadge }}" style="{{ (!$isTerminal || $a->status_id === 7) ? 'background:#eaf4ff;color:#1a6fad;border:1px solid #b3d4f0;' : '' }}">
                                {{ $a->status?->status_name ?? '—' }}
                            </span>
                        </td>
                        <td class="row-actions" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                            @if($a->patient)
                                <a href="{{ route('doctor.patients.show', $a->patient->patient_id) }}" class="btn btn-small">Open Chart</a>
                            @endif

                            @if(!$isTerminal || $a->status_id === 7)
                                {{-- Active or Rescheduled: status dropdown + Void --}}
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
                                    <span class="reschedule-fields" style="display:none;">
                                        <select name="duty_session_id" style="padding:3px 6px;font-size:0.82em;max-width:260px;">
                                            <option value="" disabled selected>— Select new duty session —</option>
                                            @foreach($availableSessions as $s)
                                                @if($s->duty_session_id !== $a->duty_session_id)
                                                    <option value="{{ $s->duty_session_id }}">
                                                        {{ $s->doctor?->user?->fullName() }} — {{ $s->duty_date->format('M d, Y') }} {{ \Carbon\Carbon::parse($s->start_time)->format('g:i A') }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </span>
                                    <button type="submit" class="btn btn-small appt-apply-btn" disabled>Apply</button>
                                </form>

                                <button type="button" class="btn btn-small btn-outline"
                                        data-appt-id="{{ $a->appointment_id }}"
                                        onclick="openApptVoidModal(this)">Void</button>
                            @endif
                            {{-- Completed / Cancelled / No Show: no further actions --}}
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
                    <th style="white-space:nowrap;">Date &amp; Time</th>
                    <th>Doctor</th>
                    <th>Patient</th>
                    <th>Reason</th>
                    <th style="white-space:nowrap;">Status</th>
                    <th></th>
                </tr>
                @foreach($appointments as $a)
                    @php
                        $isTerminal = in_array($a->status_id, [4,5,6,7]);
                        $statusBadge = match($a->status_id) {
                            4 => 'tag-green',
                            5, 6 => 'tag-red',
                            7 => '',
                            default => '',
                        };
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;">
                            <span style="font-weight:600;">{{ $a->appointment_at?->format('M d, Y') }}</span>
                            <br><span class="muted" style="font-size:0.85em;">{{ $a->appointment_at?->format('g:i A') }}</span>
                        </td>
                        <td>
                            <span style="font-weight:500;">{{ $a->doctor?->user?->fullName() ?? '—' }}</span>
                            @if($a->doctor?->specialization)
                                <br><span class="muted" style="font-size:0.82em;">{{ $a->doctor->specialization }}</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-weight:500;">{{ $a->patient?->user?->fullName() ?? '—' }}</span>
                            @if($a->appointment_type)
                                <br><span class="muted" style="font-size:0.82em;">{{ $a->appointment_type }}</span>
                            @endif
                        </td>
                        <td style="max-width:180px;">
                            <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                  title="{{ $a->reason_for_visit }}">{{ $a->reason_for_visit ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="tag {{ $statusBadge }}" style="{{ (!$isTerminal || $a->status_id === 7) ? 'background:#eaf4ff;color:#1a6fad;border:1px solid #b3d4f0;' : '' }}">
                                {{ $a->status?->status_name ?? '—' }}
                            </span>
                        </td>
                        <td class="row-actions" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                            @if($a->patient)
                                <a href="{{ route('doctor.patients.show', $a->patient->patient_id) }}" class="btn btn-small">Open Chart</a>
                            @endif

                            @if(!$isTerminal || $a->status_id === 7)
                                {{-- Active or Rescheduled: status dropdown + Void --}}
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
                                    <span class="reschedule-fields" style="display:none;">
                                        <select name="duty_session_id" style="padding:3px 6px;font-size:0.82em;max-width:260px;">
                                            <option value="" disabled selected>— Select new duty session —</option>
                                            @foreach($availableSessions as $s)
                                                @if($s->duty_session_id !== $a->duty_session_id)
                                                    <option value="{{ $s->duty_session_id }}">
                                                        {{ $s->doctor?->user?->fullName() }} — {{ $s->duty_date->format('M d, Y') }} {{ \Carbon\Carbon::parse($s->start_time)->format('g:i A') }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </span>
                                    <button type="submit" class="btn btn-small appt-apply-btn" disabled>Apply</button>
                                </form>

                                <button type="button" class="btn btn-small btn-outline"
                                        data-appt-id="{{ $a->appointment_id }}"
                                        onclick="openApptVoidModal(this)">Void</button>
                            @endif
                            {{-- Completed / Cancelled / No Show: no further actions --}}
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
    var form          = select.closest('form');
    var reschedFields = form.querySelector('.reschedule-fields');
    var applyBtn      = form.querySelector('.appt-apply-btn');

    reschedFields.style.display = (select.value === '7') ? 'inline-block' : 'none';
    if (select.value !== '7') {
        var sessionSelect = reschedFields.querySelector('select[name="duty_session_id"]');
        if (sessionSelect) sessionSelect.value = '';
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
