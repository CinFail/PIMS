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

    {{-- Edit Consultation (vitals / notes) --}}
    @if($doctor && $consultation->doctor_id === $doctor->doctor_id)
    <details class="form-card" style="margin-bottom:16px;">
        <summary style="font-weight:600;cursor:pointer;list-style:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-pencil"></i> Edit Consultation Details
        </summary>
        <div style="margin-top:14px;">
            <form action="{{ route('doctor.consultation.update', $consultation->consultation_id) }}" method="POST">
                @csrf @method('PUT')
                <div class="form-group">
                    <label for="chief_complaint">Chief Complaint</label>
                    <textarea name="chief_complaint" id="chief_complaint">{{ old('chief_complaint', $consultation->chief_complaint) }}</textarea>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="weight_kg">Weight (kg)</label>
                        <input type="number" step="0.1" name="weight_kg" id="weight_kg"
                               value="{{ old('weight_kg', $consultation->weight_kg) }}" min="0" max="500">
                    </div>
                    <div class="form-group">
                        <label for="height_cm">Height (cm)</label>
                        <input type="number" step="0.1" name="height_cm" id="height_cm"
                               value="{{ old('height_cm', $consultation->height_cm) }}" min="0" max="300">
                    </div>
                    <div class="form-group">
                        <label for="temp_c">Temperature (°C)</label>
                        <input type="number" step="0.1" name="temp_c" id="temp_c"
                               value="{{ old('temp_c', $consultation->temp_c) }}" min="30" max="45">
                    </div>
                    <div class="form-group">
                        <label for="heart_rate">Heart Rate (bpm)</label>
                        <input type="number" name="heart_rate" id="heart_rate"
                               value="{{ old('heart_rate', $consultation->heart_rate) }}" min="20" max="300">
                    </div>
                    <div class="form-group">
                        <label for="bp_systolic">BP Systolic</label>
                        <input type="number" name="bp_systolic" id="bp_systolic"
                               value="{{ old('bp_systolic', $consultation->bp_systolic) }}" min="50" max="300">
                    </div>
                    <div class="form-group">
                        <label for="bp_diastolic">BP Diastolic</label>
                        <input type="number" name="bp_diastolic" id="bp_diastolic"
                               value="{{ old('bp_diastolic', $consultation->bp_diastolic) }}" min="30" max="200">
                    </div>
                    <div class="form-group">
                        <label for="respiratory_rate">Respiratory Rate</label>
                        <input type="number" name="respiratory_rate" id="respiratory_rate"
                               value="{{ old('respiratory_rate', $consultation->respiratory_rate) }}" min="5" max="80">
                    </div>
                    <div class="form-group">
                        <label for="follow_up_at">Follow-up Date</label>
                        <input type="date" name="follow_up_at" id="follow_up_at"
                               value="{{ old('follow_up_at', $consultation->follow_up_at?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group span-2">
                        <label for="clinical_notes">Clinical Notes</label>
                        <textarea name="clinical_notes" id="clinical_notes">{{ old('clinical_notes', $consultation->clinical_notes) }}</textarea>
                    </div>
                </div>
                <div class="btn-row">
                    <button type="submit" class="btn"><i class="bi bi-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </details>
    @endif

    {{-- Diagnoses --}}
    <h2>Diagnoses</h2>
    @if($consultation->diagnoses->where('is_voided', 0)->isEmpty())
        <p class="muted">No diagnoses added yet.</p>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date</th><th>Description</th><th>Type</th><th>ICD</th></tr>
                @foreach($consultation->diagnoses->where('is_voided', 0) as $d)
                    <tr>
                        <td>{{ $d->diagnosed_at?->format('M d, Y') }}</td>
                        <td>{{ $d->description }}</td>
                        <td>{{ $d->diagnosis_type }}</td>
                        <td>{{ $d->icd_code ?? '—' }}</td>
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
                <tr><th>Medicine</th><th>Dosage</th><th>Form</th><th>Frequency</th><th>Duration</th><th>Qty</th>
                    @if($doctor && $consultation->doctor_id === $doctor->doctor_id)<th></th>@endif
                </tr>
                @foreach($consultation->prescription->items as $item)
                    <tr>
                        <td>{{ $item->medicine_name }}</td>
                        <td>{{ $item->dosage ?? '—' }}</td>
                        <td>{{ $item->form ?? '—' }}</td>
                        <td>{{ $item->frequency ?? '—' }}</td>
                        <td>{{ $item->duration ?? '—' }}</td>
                        <td>{{ $item->quantity ?? '—' }}</td>
                        @if($doctor && $consultation->doctor_id === $doctor->doctor_id)
                        <td class="row-actions">
                            <form action="{{ route('doctor.prescription.item.destroy', $item->prescription_item_id) }}" method="POST" class="inline-form">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-small btn-outline"
                                        onclick="return confirm('Remove {{ $item->medicine_name }} from this prescription?')">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </form>
                        </td>
                        @endif
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
