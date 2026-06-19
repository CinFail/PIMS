@extends('layouts.app')
@section('title', 'My Appointments')
@section('content')
    <h1>My Appointments</h1>
    <p class="page-subtitle">Your doctor and laboratory appointments.</p>

    <div class="btn-row">
        <a href="{{ route('patient.appointments.create') }}" class="btn">
            <i class="bi bi-calendar-plus"></i> Book a Doctor Appointment
        </a>
        <a href="{{ route('patient.lab.request.create') }}" class="btn btn-outline">
            <i class="bi bi-droplet"></i> Request a Lab Test
        </a>
    </div>

    {{-- Doctor Appointments --}}
    <h2>Doctor Appointments</h2>
    @if($appointments->isEmpty())
        <div class="empty-state">
            <i class="bi bi-calendar2-x"></i>
            <p>You have no doctor appointments yet.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date &amp; Time</th><th>Doctor</th><th>Type</th><th>Status</th><th style="width:1%;white-space:nowrap;"></th></tr>
                @foreach($appointments as $a)
                    <tr>
                        <td>{{ $a->appointment_at?->format('M d, Y g:i A') }}</td>
                        <td>{{ $a->doctor?->user?->fullName() }}</td>
                        <td>{{ $a->appointment_type }}</td>
                        <td>{{ $a->status?->status_name }}</td>
                        <td class="row-actions">
                            @if(!in_array($a->status?->status_name, ['Completed','Cancelled','No Show']))
                                <button type="button" class="btn btn-small btn-outline"
                                        style="margin-right:16px;"
                                        onclick="toggleRescheduleForm('resched-appt-{{ $a->appointment_id }}')">
                                    Reschedule
                                </button>
                                <button type="button" class="btn btn-small btn-outline"
                                        onclick="toggleVoidForm('void-appt-{{ $a->appointment_id }}')">
                                    Cancel Request
                                </button>
                            @endif
                        </td>
                    </tr>
                    <tr id="void-appt-{{ $a->appointment_id }}" style="display:none;">
                        <td colspan="5" style="padding:10px 12px;background:#fafafa;">
                            <form action="{{ route('void.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="table_name" value="appointments">
                                <input type="hidden" name="record_id" value="{{ $a->appointment_id }}">
                                <div class="form-group" style="margin-bottom:6px;">
                                    <textarea name="reason" placeholder="Reason for cancellation request (min 10 characters)" style="width:100%;min-height:60px;" required minlength="10"></textarea>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn btn-small">Submit Request</button>
                                    <button type="button" class="btn btn-small btn-outline"
                                            onclick="toggleVoidForm('void-appt-{{ $a->appointment_id }}')">Back</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <tr id="resched-appt-{{ $a->appointment_id }}" style="display:none;">
                        <td colspan="5" style="padding:10px 12px;background:#fafafa;">
                            <form action="{{ route('patient.appointments.reschedule', $a->appointment_id) }}" method="POST">
                                @csrf
                                <div class="form-group" style="margin-bottom:6px;">
                                    <label style="font-size:0.88em;font-weight:600;">Select new duty session</label>
                                    <select name="duty_session_id" required style="width:100%;margin-top:4px;">
                                        <option value="" disabled selected>— Choose a schedule —</option>
                                        @foreach($availableSessions as $s)
                                            @if($s->duty_session_id !== $a->duty_session_id)
                                                <option value="{{ $s->duty_session_id }}">
                                                    {{ $s->doctor?->user?->fullName() }} — {{ $s->duty_date->format('M d, Y') }} {{ \Carbon\Carbon::parse($s->start_time)->format('g:i A') }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn btn-small">Confirm Reschedule</button>
                                    <button type="button" class="btn btn-small btn-outline"
                                            onclick="toggleRescheduleForm('resched-appt-{{ $a->appointment_id }}')">Back</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- Laboratory Appointments --}}
    <h2>Laboratory Appointments</h2>
    @if($labAppointments->isEmpty())
        <div class="empty-state">
            <i class="bi bi-eyedropper"></i>
            <p>You have no laboratory appointments yet.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date &amp; Time</th><th>Tests</th><th>Status</th><th style="width:1%;white-space:nowrap;"></th></tr>
                @foreach($labAppointments as $la)
                    <tr>
                        <td>{{ $la->scheduled_at?->format('M d, Y g:i A') }}</td>
                        <td>
                            @if($la->labRequest && $la->labRequest->items->isNotEmpty())
                                {{ $la->labRequest->items->map(fn ($i) => $i->test?->test_name)->filter()->join(', ') }}
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $la->status }}</td>
                        <td class="row-actions">
                            @if($la->status === 'Scheduled' && $la->scheduled_at?->isFuture())
                                <button type="button" class="btn btn-small btn-outline"
                                        onclick="toggleLabCancelForm('lab-cancel-{{ $la->lab_appointment_id }}')">Cancel Request</button>
                            @endif
                        </td>
                    </tr>
                    @if($la->status === 'Scheduled' && $la->scheduled_at?->isFuture())
                        <tr id="lab-cancel-{{ $la->lab_appointment_id }}" style="display:none;">
                            <td colspan="4" style="padding:10px 12px;background:#fafafa;">
                                <form action="{{ route('void.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="table_name" value="lab_appointments">
                                    <input type="hidden" name="record_id" value="{{ $la->lab_appointment_id }}">
                                    <div class="form-group" style="margin-bottom:6px;">
                                        <textarea name="reason" placeholder="Reason for cancellation request (min 10 characters)" style="width:100%;min-height:60px;" required minlength="10"></textarea>
                                    </div>
                                    <div style="display:flex;gap:6px;">
                                        <button type="submit" class="btn btn-small">Submit Request</button>
                                        <button type="button" class="btn btn-small btn-outline"
                                                onclick="toggleLabCancelForm('lab-cancel-{{ $la->lab_appointment_id }}')">Back</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    @endif
@endsection
@push('scripts')
<script>
function toggleVoidForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function toggleLabCancelForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}
function toggleRescheduleForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}
</script>
@endpush
