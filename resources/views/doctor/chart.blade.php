@extends('layouts.app')
@section('title', 'Patient Chart')
@section('content')
    <h1>{{ $patient->user?->fullName() }}</h1>
    <p class="page-subtitle">Patient digital chart</p>

    <div class="btn-row">
        <form action="{{ route('doctor.patients.consultation', $patient->patient_id) }}" method="POST" class="inline-form">
            @csrf
            <button type="submit" class="btn"><i class="bi bi-clipboard2-pulse"></i> Start New Consultation</button>
        </form>
        <a href="{{ route('doctor.patients.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Back to Records</a>
    </div>

    <h2>Basic Information</h2>
    <div class="box">
        <table>
            <tr><th>Email</th><td>{{ $patient->user?->email }}</td><th>Mobile</th><td>{{ $patient->user?->mobile_number ?? '—' }}</td></tr>
            <tr><th>Sex</th><td>{{ $patient->sex ?? '—' }}</td><th>Blood Type</th><td>{{ $patient->blood_type ?? '—' }}</td></tr>
            <tr><th>Date of Birth</th><td>{{ $patient->user?->date_of_birth?->format('M d, Y') }}</td><th>Age</th><td>{{ $patient->user?->age() !== null ? $patient->user->age().' yrs' : '—' }}</td></tr>
            <tr><th>Address</th><td colspan="3">{{ $patient->address ?? '—' }}</td></tr>
        </table>
    </div>

    <h2>Medical History</h2>
    @if($patient->medicalHistory)
        <div class="box">
            <p><strong>Allergies:</strong> {{ $patient->medicalHistory->allergies ?? '—' }}</p>
            <p><strong>Chronic Conditions:</strong> {{ $patient->medicalHistory->chronic_conditions ?? '—' }}</p>
            <p><strong>Past Surgeries:</strong> {{ $patient->medicalHistory->past_surgeries ?? '—' }}</p>
            <p><strong>Current Medications:</strong> {{ $patient->medicalHistory->current_medications ?? '—' }}</p>
            <p><strong>Family History:</strong> {{ $patient->medicalHistory->family_history ?? '—' }}</p>
        </div>
    @else
        <p class="muted">No medical history recorded.</p>
    @endif

    {{-- ── Past Consultations ──────────────────────────────────────── --}}
    <h2>Past Consultations</h2>
    @if($consultations->isEmpty())
        <p class="muted">No consultations recorded.</p>
    @else
        <div class="table-card">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Doctor</th>
                    <th>Chief Complaint</th>
                    <th>Follow-up</th>
                    <th></th>
                </tr>
                @foreach($consultations as $c)
                    @php $cvPending = isset($pendingVoids['consultations:' . $c->consultation_id]); @endphp
                    <tr>
                        <td>{{ $c->consultation_at?->format('M d, Y') }}</td>
                        <td>{{ $c->doctor?->user?->fullName() }}</td>
                        <td>{{ $c->chief_complaint ?? '—' }}</td>
                        <td>{{ $c->follow_up_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="row-actions">
                            <a href="{{ route('doctor.consultation.show', $c->consultation_id) }}" class="btn btn-small btn-outline">View</a>
                            @if($cvPending)
                                <span class="tag" style="background:#e67e22;color:#fff;">Pending Void</span>
                                <button class="btn btn-small" disabled>Update</button>
                            @else
                                <button type="button" class="btn btn-small" onclick="toggleChartForm('upd-con-{{ $c->consultation_id }}')">Update</button>
                                <button type="button" class="btn btn-small btn-outline" onclick="toggleChartForm('void-con-{{ $c->consultation_id }}')">Request Void</button>
                            @endif
                        </td>
                    </tr>
                    @unless($cvPending)
                    <tr id="upd-con-{{ $c->consultation_id }}" style="display:none;">
                        <td colspan="5" style="padding:14px 16px;background:#fafafa;">
                            <form action="{{ route('doctor.consultation.update', $c->consultation_id) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="form-group">
                                    <label>Chief Complaint</label>
                                    <textarea name="chief_complaint">{{ $c->chief_complaint }}</textarea>
                                </div>
                                <div class="form-grid-2">
                                    <div class="form-group">
                                        <label>Weight (kg)</label>
                                        <input type="number" step="0.1" name="weight_kg" value="{{ $c->weight_kg }}" min="0" max="500">
                                    </div>
                                    <div class="form-group">
                                        <label>Height (cm)</label>
                                        <input type="number" step="0.1" name="height_cm" value="{{ $c->height_cm }}" min="0" max="300">
                                    </div>
                                    <div class="form-group">
                                        <label>Temperature (°C)</label>
                                        <input type="number" step="0.1" name="temp_c" value="{{ $c->temp_c }}" min="30" max="45">
                                    </div>
                                    <div class="form-group">
                                        <label>Heart Rate (bpm)</label>
                                        <input type="number" name="heart_rate" value="{{ $c->heart_rate }}" min="20" max="300">
                                    </div>
                                    <div class="form-group">
                                        <label>BP Systolic</label>
                                        <input type="number" name="bp_systolic" value="{{ $c->bp_systolic }}" min="50" max="300">
                                    </div>
                                    <div class="form-group">
                                        <label>BP Diastolic</label>
                                        <input type="number" name="bp_diastolic" value="{{ $c->bp_diastolic }}" min="30" max="200">
                                    </div>
                                    <div class="form-group">
                                        <label>Respiratory Rate</label>
                                        <input type="number" name="respiratory_rate" value="{{ $c->respiratory_rate }}" min="5" max="80">
                                    </div>
                                    <div class="form-group">
                                        <label>Follow-up Date</label>
                                        <input type="date" name="follow_up_at" value="{{ $c->follow_up_at?->format('Y-m-d') }}">
                                    </div>
                                    <div class="form-group span-2">
                                        <label>Clinical Notes</label>
                                        <textarea name="clinical_notes">{{ $c->clinical_notes }}</textarea>
                                    </div>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn btn-small"><i class="bi bi-save"></i> Save Changes</button>
                                    <button type="button" class="btn btn-small btn-outline" onclick="toggleChartForm('upd-con-{{ $c->consultation_id }}')">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <tr id="void-con-{{ $c->consultation_id }}" style="display:none;">
                        <td colspan="5" style="padding:10px 16px;background:#fafafa;">
                            <form action="{{ route('void.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="table_name" value="consultations">
                                <input type="hidden" name="record_id" value="{{ $c->consultation_id }}">
                                <div class="form-group" style="margin-bottom:6px;">
                                    <textarea name="reason" placeholder="Reason for void request (min 10 characters)" style="width:100%;min-height:60px;" required minlength="10"></textarea>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn btn-small">Submit Request</button>
                                    <button type="button" class="btn btn-small btn-outline" onclick="toggleChartForm('void-con-{{ $c->consultation_id }}')">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endunless
                @endforeach
            </table>
        </div>
    @endif

    {{-- ── Past Diagnoses ──────────────────────────────────────────── --}}
    <h2>Past Diagnoses</h2>
    @if($diagnoses->isEmpty())
        <p class="muted">No diagnoses recorded.</p>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date</th><th>Diagnosis</th><th>Type</th><th>ICD</th><th>Doctor</th><th></th></tr>
                @foreach($diagnoses as $d)
                    @php $dvPending = isset($pendingVoids['diagnoses:' . $d->diagnosis_id]); @endphp
                    <tr>
                        <td>{{ $d->diagnosed_at?->format('M d, Y') }}</td>
                        <td>{{ $d->description }}</td>
                        <td>{{ $d->diagnosis_type }}</td>
                        <td>{{ $d->icd_code ?? '—' }}</td>
                        <td>{{ $d->doctor?->user?->fullName() }}</td>
                        <td class="row-actions">
                            @if($dvPending)
                                <span class="tag" style="background:#e67e22;color:#fff;">Pending Void</span>
                                <button class="btn btn-small" disabled>Update</button>
                            @else
                                <button type="button" class="btn btn-small" onclick="toggleChartForm('upd-diag-{{ $d->diagnosis_id }}')">Update</button>
                                <button type="button" class="btn btn-small btn-outline" onclick="toggleChartForm('void-diag-{{ $d->diagnosis_id }}')">Request Void</button>
                            @endif
                        </td>
                    </tr>
                    @unless($dvPending)
                    <tr id="upd-diag-{{ $d->diagnosis_id }}" style="display:none;">
                        <td colspan="6" style="padding:14px 16px;background:#fafafa;">
                            <form action="{{ route('doctor.diagnosis.update', $d->diagnosis_id) }}" method="POST">
                                @csrf @method('PATCH')
                                <div class="form-grid-2">
                                    <div class="form-group">
                                        <label>Type</label>
                                        <select name="diagnosis_type">
                                            @foreach(['Primary','Secondary','Differential'] as $t)
                                                <option value="{{ $t }}" @selected($d->diagnosis_type === $t)>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>ICD-10 Code</label>
                                        <input type="text" name="icd_code" value="{{ $d->icd_code }}" maxlength="20" placeholder="Optional">
                                    </div>
                                    <div class="form-group span-2">
                                        <label>Clinical Findings <span class="req">*</span></label>
                                        <textarea name="description" required>{{ $d->description }}</textarea>
                                    </div>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn btn-small"><i class="bi bi-save"></i> Save</button>
                                    <button type="button" class="btn btn-small btn-outline" onclick="toggleChartForm('upd-diag-{{ $d->diagnosis_id }}')">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <tr id="void-diag-{{ $d->diagnosis_id }}" style="display:none;">
                        <td colspan="6" style="padding:10px 16px;background:#fafafa;">
                            <form action="{{ route('void.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="table_name" value="diagnoses">
                                <input type="hidden" name="record_id" value="{{ $d->diagnosis_id }}">
                                <div class="form-group" style="margin-bottom:6px;">
                                    <textarea name="reason" placeholder="Reason for void request (min 10 characters)" style="width:100%;min-height:60px;" required minlength="10"></textarea>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn btn-small">Submit Request</button>
                                    <button type="button" class="btn btn-small btn-outline" onclick="toggleChartForm('void-diag-{{ $d->diagnosis_id }}')">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endunless
                @endforeach
            </table>
        </div>
    @endif

    {{-- ── Laboratory Results ──────────────────────────────────────── --}}
    <h2>Laboratory Results</h2>
    @if($results->isEmpty())
        <p class="muted">No results.</p>
    @else
        <div class="table-card">
            <table>
                <tr><th>Test</th><th>Result</th><th>Flag</th><th>Status</th><th>File</th></tr>
                @foreach($results as $r)
                    <tr>
                        <td>{{ $r->requestItem?->test?->test_name }}</td>
                        <td>{{ $r->result_value ?? '—' }} {{ $r->unit }}</td>
                        <td>{{ $r->abnormal_flag }}</td>
                        <td>{{ $r->workflow_status }}</td>
                        <td>
                            @if($r->result_file_path)
                                <a href="{{ asset('storage/'.$r->result_file_path) }}" target="_blank">View</a>
                            @else — @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- ── Previous Prescriptions ──────────────────────────────────── --}}
    <h2>Previous Prescriptions</h2>
    @if($prescriptions->isEmpty())
        <p class="muted">No prescriptions.</p>
    @else
        <div class="table-card" style="overflow-x:auto;">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Drug Name</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Form</th>
                    <th>Duration</th>
                    <th>Qty</th>
                    <th>Instructions</th>
                    <th></th>
                </tr>
                @foreach($prescriptions as $presc)
                    @php $pvPending = isset($pendingVoids['prescriptions:' . $presc->prescription_id]); @endphp
                    @foreach($presc->items as $item)
                        <tr>
                            <td style="white-space:nowrap;">{{ $presc->prescribed_at?->format('M d, Y') }}</td>
                            <td>{{ $item->medicine_name }}</td>
                            <td>{{ $item->dosage ?? '—' }}</td>
                            <td>{{ $item->frequency ?? '—' }}</td>
                            <td>{{ $item->form ?? '—' }}</td>
                            <td>{{ $item->duration ?? '—' }}</td>
                            <td>{{ $item->quantity ?? '—' }}</td>
                            <td>{{ $item->instructions ?? '—' }}</td>
                            <td class="row-actions" style="white-space:nowrap;">
                                @if($pvPending)
                                    <button class="btn btn-small" disabled>Update</button>
                                @else
                                    <button type="button" class="btn btn-small"
                                        data-item-id="{{ $item->prescription_item_id }}"
                                        data-dosage="{{ $item->dosage }}"
                                        data-form="{{ $item->form }}"
                                        data-frequency="{{ $item->frequency }}"
                                        data-duration="{{ $item->duration }}"
                                        data-quantity="{{ $item->quantity }}"
                                        data-instructions="{{ $item->instructions }}"
                                        onclick="openRxUpdateModal(this)">Update</button>
                                    <button type="button" class="btn btn-small btn-outline"
                                        data-presc-id="{{ $presc->prescription_id }}"
                                        onclick="openRxVoidModal(this)">Request Void</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </table>
        </div>
    @endif

    {{-- ── Prescription Update Modal ───────────────────────────────── --}}
    <div id="rx-update-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:8px;padding:24px 28px;max-width:540px;width:calc(100% - 40px);max-height:90vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <h3 style="margin-top:0;margin-bottom:16px;">Update Prescription Item</h3>
            <form id="rx-update-form" method="POST" action="">
                @csrf @method('PATCH')
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Dosage</label>
                        <input type="text" id="rx-dosage" name="dosage" maxlength="50" placeholder="e.g. 500mg">
                    </div>
                    <div class="form-group">
                        <label>Frequency</label>
                        <input type="text" id="rx-frequency" name="frequency" maxlength="50" placeholder="e.g. Twice a day">
                    </div>
                    <div class="form-group">
                        <label>Form</label>
                        <input type="text" id="rx-form" name="form" maxlength="50" placeholder="e.g. Tablet, Capsule, Syrup">
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <input type="text" id="rx-duration" name="duration" maxlength="50" placeholder="e.g. 7 Days">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" id="rx-quantity" name="quantity" min="1" max="9999" placeholder="e.g. 14">
                    </div>
                    <div class="form-group span-2">
                        <label>Instructions / Remarks</label>
                        <textarea id="rx-instructions" name="instructions" placeholder="e.g. Take after meals"></textarea>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:6px;">
                    <button type="submit" class="btn btn-small"><i class="bi bi-save"></i> Save Changes</button>
                    <button type="button" class="btn btn-small btn-outline" onclick="closeRxModal('rx-update-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Prescription Void Request Modal ────────────────────────── --}}
    <div id="rx-void-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:8px;padding:24px 28px;max-width:480px;width:calc(100% - 40px);box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <h3 style="margin-top:0;margin-bottom:6px;">Request Void — Prescription</h3>
            <p class="muted" style="margin-bottom:14px;font-size:0.9em;">This will void the entire prescription record.</p>
            <form action="{{ route('void.store') }}" method="POST">
                @csrf
                <input type="hidden" name="table_name" value="prescriptions">
                <input type="hidden" id="rx-void-record-id" name="record_id" value="">
                <div class="form-group">
                    <label>Reason <span class="req">*</span></label>
                    <textarea name="reason" placeholder="Reason for void request (min 10 characters)" required minlength="10" style="min-height:70px;"></textarea>
                </div>
                <div style="display:flex;gap:8px;margin-top:6px;">
                    <button type="submit" class="btn btn-small">Submit Request</button>
                    <button type="button" class="btn btn-small btn-outline" onclick="closeRxModal('rx-void-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
<script>
function toggleChartForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

var prescItemUpdateBase = '{{ url("doctor/prescription-items") }}';

function openRxUpdateModal(btn) {
    var form = document.getElementById('rx-update-form');
    form.action = prescItemUpdateBase + '/' + btn.dataset.itemId;
    document.getElementById('rx-dosage').value       = btn.dataset.dosage       || '';
    document.getElementById('rx-form').value         = btn.dataset.form         || '';
    document.getElementById('rx-frequency').value    = btn.dataset.frequency    || '';
    document.getElementById('rx-duration').value     = btn.dataset.duration     || '';
    document.getElementById('rx-quantity').value     = btn.dataset.quantity     || '';
    document.getElementById('rx-instructions').value = btn.dataset.instructions || '';
    document.getElementById('rx-update-modal').style.display = 'flex';
}

function openRxVoidModal(btn) {
    document.getElementById('rx-void-record-id').value = btn.dataset.prescId;
    document.getElementById('rx-void-modal').style.display = 'flex';
}

function closeRxModal(id) {
    document.getElementById(id).style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    ['rx-update-modal', 'rx-void-modal'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', function (e) { if (e.target === el) closeRxModal(id); });
    });
});
</script>
@endpush

@endsection
