@extends('layouts.app')
@section('title', 'Consultation')
@section('content')
    <h1>Consultation</h1>
    <p class="page-subtitle">
        Patient: <strong>{{ $consultation->patient?->user?->fullName() }}</strong>
        @if($consultation->patient?->user?->age() !== null)
            &bull; {{ $consultation->patient->user->age() }} yrs
        @endif
        &bull; {{ $consultation->consultation_at?->format('M d, Y g:i A') }}
    </p>

    <div class="btn-row">
        <a href="{{ route('doctor.patients.show', $consultation->patient_id) }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back to Chart
        </a>
    </div>

    {{-- Diagnoses --}}
    <h2>Diagnoses</h2>
    @if($consultation->diagnoses->where('is_voided', 0)->isEmpty())
        <p class="muted">No diagnoses added yet.</p>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date</th><th>Description</th><th>Type</th><th>ICD</th><th></th></tr>
                @foreach($consultation->diagnoses->where('is_voided', 0) as $d)
                    <tr>
                        <td>{{ $d->diagnosed_at?->format('M d, Y') }}</td>
                        <td>{{ $d->description }}</td>
                        <td>{{ $d->diagnosis_type }}</td>
                        <td>{{ $d->icd_code ?? '—' }}</td>
                        <td class="row-actions">
                            <button type="button" class="btn btn-small btn-outline"
                                    onclick="toggleVoidForm('void-diag-{{ $d->diagnosis_id }}')">
                                Request Void
                            </button>
                        </td>
                    </tr>
                    <tr id="void-diag-{{ $d->diagnosis_id }}" style="display:none;">
                        <td colspan="5" style="padding:10px 12px;background:#fafafa;">
                            <form action="{{ route('void.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="table_name" value="diagnoses">
                                <input type="hidden" name="record_id" value="{{ $d->diagnosis_id }}">
                                <div class="form-group" style="margin-bottom:6px;">
                                    <textarea name="reason" placeholder="Reason for void request (min 10 characters)" style="width:100%;min-height:60px;" required minlength="10"></textarea>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn btn-small">Submit Request</button>
                                    <button type="button" class="btn btn-small btn-outline"
                                            onclick="toggleVoidForm('void-diag-{{ $d->diagnosis_id }}')">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @if($doctor)
    <div class="form-card">
        <div class="form-section-title">Add Diagnosis</div>
        <form action="{{ route('doctor.consultation.diagnosis', $consultation->consultation_id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="description">Clinical Findings <span class="req">*</span></label>
                <textarea name="description" id="description" required>{{ old('description') }}</textarea>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="diagnosis_type">Type</label>
                    <select name="diagnosis_type" id="diagnosis_type">
                        <option value="Primary">Primary</option>
                        <option value="Secondary">Secondary</option>
                        <option value="Differential">Differential</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="icd_code">ICD-10 Code</label>
                    <input type="text" name="icd_code" id="icd_code"
                           value="{{ old('icd_code') }}" placeholder="Optional">
                </div>
            </div>
            <div class="btn-row">
                <button type="submit" class="btn">Save Diagnosis</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Prescription --}}
    <h2>Prescription</h2>
    @if($consultation->prescription && !$consultation->prescription->is_voided && $consultation->prescription->items->count())
        <div class="table-card">
            <table>
                <tr><th>Medicine</th><th>Dosage</th><th>Form</th><th>Frequency</th><th>Duration</th><th>Qty</th></tr>
                @foreach($consultation->prescription->items as $item)
                    <tr>
                        <td>{{ $item->medicine_name }}</td>
                        <td>{{ $item->dosage ?? '—' }}</td>
                        <td>{{ $item->form ?? '—' }}</td>
                        <td>{{ $item->frequency ?? '—' }}</td>
                        <td>{{ $item->duration ?? '—' }}</td>
                        <td>{{ $item->quantity ?? '—' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @else
        <p class="muted">No medicines prescribed yet.</p>
    @endif

    @if($doctor)
    <div class="form-card">
        <div class="form-section-title">Add Medicine</div>
        <div class="form-info">
            <i class="bi bi-person-badge"></i>
            Dr. {{ $doctor->user?->fullName() }} &bull; License: {{ $doctor->license_number ?? 'N/A' }}
        </div>
        <form action="{{ route('doctor.consultation.prescription', $consultation->consultation_id) }}" method="POST">
            @csrf
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="medicine_name">Medicine Name <span class="req">*</span></label>
                    <input type="text" name="medicine_name" id="medicine_name"
                           value="{{ old('medicine_name') }}" required>
                </div>
                <div class="form-group">
                    <label for="dosage">Dosage</label>
                    <input type="text" name="dosage" id="dosage"
                           value="{{ old('dosage') }}" placeholder="e.g. 500 mg">
                </div>
                <div class="form-group">
                    <label for="form">Form</label>
                    <input type="text" name="form" id="form"
                           value="{{ old('form') }}" placeholder="e.g. tablet">
                </div>
                <div class="form-group">
                    <label for="frequency">Frequency</label>
                    <input type="text" name="frequency" id="frequency"
                           value="{{ old('frequency') }}" placeholder="e.g. twice daily">
                </div>
                <div class="form-group">
                    <label for="duration">Duration</label>
                    <input type="text" name="duration" id="duration"
                           value="{{ old('duration') }}" placeholder="e.g. 7 days">
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity"
                           value="{{ old('quantity') }}" min="1">
                </div>
                <div class="form-group span-2">
                    <label for="instructions">Instructions</label>
                    <textarea name="instructions" id="instructions">{{ old('instructions') }}</textarea>
                </div>
            </div>
            <div class="btn-row">
                <button type="submit" class="btn">Add Medicine</button>
            </div>
        </form>
    </div>
    @endif

@push('scripts')
<script>
function toggleVoidForm(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush

    {{-- Previous Prescriptions --}}
    @if($previousPrescriptions->isNotEmpty())
        <h2>Medication History</h2>
        @foreach($previousPrescriptions as $presc)
            <div class="box">
                <strong>{{ $presc->prescribed_at?->format('M d, Y') }}</strong>
                <span class="muted">&bull; Dr. {{ $presc->doctor?->user?->fullName() }}</span>
                <table style="margin-top:10px;">
                    <tr><th>Medicine</th><th>Dosage</th><th>Frequency</th></tr>
                    @foreach($presc->items as $item)
                        <tr>
                            <td>{{ $item->medicine_name }}</td>
                            <td>{{ $item->dosage ?? '—' }}</td>
                            <td>{{ $item->frequency ?? '—' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif
@endsection
