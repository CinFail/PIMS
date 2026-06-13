@extends('layouts.app')
@section('title', 'Consultation')
@section('content')
    <h1>Consultation</h1>
    <p class="page-subtitle">
        Patient: {{ $consultation->patient?->user?->fullName() }}
        @if($consultation->patient?->user?->age() !== null)
            ({{ $consultation->patient->user->age() }} yrs)
        @endif
        &mdash;
        {{ $consultation->consultation_at?->format('M d, Y g:i A') }}
    </p>

    <a href="{{ route('doctor.patients.show', $consultation->patient_id) }}" class="btn btn-outline">Back to Chart</a>

    {{-- ---------------- Diagnoses ---------------- --}}
    <h2>Diagnoses</h2>
    @if($consultation->diagnoses->where('is_voided', 0)->isEmpty())
        <p class="muted">No diagnoses for this consultation yet.</p>
    @else
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
    @endif

    <h3>Add Diagnosis</h3>
    <form action="{{ route('doctor.consultation.diagnosis', $consultation->consultation_id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="description">Description (findings)</label>
            <textarea name="description" id="description" required>{{ old('description') }}</textarea>
        </div>
        <div class="form-group">
            <label for="diagnosis_type">Type</label>
            <select name="diagnosis_type" id="diagnosis_type">
                <option value="Primary">Primary</option>
                <option value="Secondary">Secondary</option>
                <option value="Differential">Differential</option>
            </select>
        </div>
        <div class="form-group">
            <label for="icd_code">ICD-10 Code (optional)</label>
            <input type="text" name="icd_code" id="icd_code" value="{{ old('icd_code') }}">
        </div>
        <button type="submit" class="btn">Save Diagnosis</button>
    </form>

    {{-- ---------------- Prescription ---------------- --}}
    <h2>Prescription</h2>
    <p class="help">
        Prescribing doctor: Dr. {{ $doctor?->user?->fullName() }} &mdash;
        License No: {{ $doctor?->license_number ?? 'N/A' }}
    </p>

    @if($consultation->prescription && $consultation->prescription->items->count())
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
    @else
        <p class="muted">No medicines prescribed yet.</p>
    @endif

    <h3>Add Medicine</h3>
    <form action="{{ route('doctor.consultation.prescription', $consultation->consultation_id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="medicine_name">Medicine Name</label>
            <input type="text" name="medicine_name" id="medicine_name" value="{{ old('medicine_name') }}" required>
        </div>
        <div class="form-group">
            <label for="dosage">Dosage</label>
            <input type="text" name="dosage" id="dosage" value="{{ old('dosage') }}" placeholder="e.g. 500mg">
        </div>
        <div class="form-group">
            <label for="form">Form</label>
            <input type="text" name="form" id="form" value="{{ old('form') }}" placeholder="e.g. tablet">
        </div>
        <div class="form-group">
            <label for="frequency">Frequency</label>
            <input type="text" name="frequency" id="frequency" value="{{ old('frequency') }}" placeholder="e.g. twice a day">
        </div>
        <div class="form-group">
            <label for="duration">Duration</label>
            <input type="text" name="duration" id="duration" value="{{ old('duration') }}" placeholder="e.g. 7 days">
        </div>
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}" min="1">
        </div>
        <div class="form-group">
            <label for="instructions">Instructions</label>
            <textarea name="instructions" id="instructions">{{ old('instructions') }}</textarea>
        </div>
        <button type="submit" class="btn">Add Medicine</button>
    </form>

    {{-- ---------------- Previous prescriptions ---------------- --}}
    <h2>Previous Prescriptions (Medication History)</h2>
    @if($previousPrescriptions->isEmpty())
        <p class="muted">No previous prescriptions.</p>
    @else
        @foreach($previousPrescriptions as $presc)
            <div class="box">
                <strong>{{ $presc->prescribed_at?->format('M d, Y') }}</strong> —
                Dr. {{ $presc->doctor?->user?->fullName() }}
                (License: {{ $presc->doctor?->license_number ?? 'N/A' }})
                <table>
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
