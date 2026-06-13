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

    <h2>Past Diagnoses</h2>
    @if($diagnoses->isEmpty())
        <p class="muted">No diagnoses recorded.</p>
    @else
        <div class="table-card">
            <table>
                <tr><th>Date</th><th>Diagnosis</th><th>Type</th><th>ICD</th><th>Doctor</th></tr>
                @foreach($diagnoses as $d)
                    <tr>
                        <td>{{ $d->diagnosed_at?->format('M d, Y') }}</td>
                        <td>{{ $d->description }}</td>
                        <td>{{ $d->diagnosis_type }}</td>
                        <td>{{ $d->icd_code ?? '—' }}</td>
                        <td>{{ $d->doctor?->user?->fullName() }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

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

    <h2>Previous Prescriptions</h2>
    @if($prescriptions->isEmpty())
        <p class="muted">No prescriptions.</p>
    @else
        @foreach($prescriptions as $presc)
            <div class="box">
                <strong>{{ $presc->prescribed_at?->format('M d, Y') }}</strong> —
                Dr. {{ $presc->doctor?->user?->fullName() }}
                (License: {{ $presc->doctor?->license_number ?? 'N/A' }})
                <table style="margin-top:10px;">
                    <tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th></tr>
                    @foreach($presc->items as $item)
                        <tr>
                            <td>{{ $item->medicine_name }}</td>
                            <td>{{ $item->dosage ?? '—' }}</td>
                            <td>{{ $item->frequency ?? '—' }}</td>
                            <td>{{ $item->duration ?? '—' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif
@endsection
