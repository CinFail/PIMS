@extends('layouts.app')
@section('title', 'Request a Lab Test')
@section('content')
    <h1>Request a Lab Test</h1>
    <p class="page-subtitle">Request laboratory tests directly — no doctor consultation required. Choose the tests you need and a preferred date and time for your laboratory visit.</p>

    <form action="{{ route('patient.lab.request.store') }}" method="POST">
        @csrf

        <h2>Laboratory Tests</h2>
        <p class="help">Select one or more tests you would like performed.</p>
        <div class="form-group" style="max-width:none;">
            @forelse($labTests as $t)
                <div class="checkbox-row">
                    <input type="checkbox" name="lab_tests[]" id="t{{ $t->lab_test_id }}" value="{{ $t->lab_test_id }}"
                           @checked(collect(old('lab_tests'))->contains($t->lab_test_id))>
                    <label for="t{{ $t->lab_test_id }}">{{ $t->test_name }} <span class="muted">({{ $t->category?->category_name }})</span></label>
                </div>
            @empty
                <p class="muted">No lab tests are configured yet. Please check back later.</p>
            @endforelse
        </div>

        @if($labTests->isNotEmpty())
            <h2>Preferred Schedule</h2>
            <div class="form-group" style="max-width:320px;">
                <label for="scheduled_at">Date &amp; Time</label>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                       value="{{ old('scheduled_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required>
                <p class="help">Pick when you would like to come in for your laboratory visit.</p>
            </div>

            <div class="form-group" style="max-width:520px;">
                <label for="clinical_notes">Notes (optional)</label>
                <textarea name="clinical_notes" id="clinical_notes">{{ old('clinical_notes') }}</textarea>
                <p class="help">Anything the laboratory should know (e.g. fasting, symptoms).</p>
            </div>

            <button type="submit" class="btn">Submit Lab Request</button>
        @endif
    </form>
@endsection
