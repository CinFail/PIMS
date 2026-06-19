@extends('layouts.app')
@section('title', 'Request a Lab Test')
@section('content')
    <h1>Request a Lab Test</h1>
    <p class="page-subtitle">No doctor consultation required. Select tests and a preferred visit date.</p>

    <form action="{{ route('patient.lab.request.store') }}" method="POST">
        @csrf

        <h2>Laboratory Tests <span class="req">*</span></h2>
        @error('lab_tests')
            <span class="field-error" style="display:block;margin-bottom:8px;">{{ $message }}</span>
        @enderror
        <div class="form-group" style="max-width:none;">
            @forelse($labTests as $t)
                <div class="checkbox-row">
                    <input type="checkbox" name="lab_tests[]" id="t{{ $t->lab_test_id }}" value="{{ $t->lab_test_id }}"
                           @checked(collect(old('lab_tests'))->contains($t->lab_test_id))>
                    <label for="t{{ $t->lab_test_id }}">
                        {{ $t->test_name }}
                        <span class="muted">({{ $t->category?->category_name }})</span>
                    </label>
                </div>
            @empty
                <div class="empty-state" style="padding:32px 24px;">
                    <i class="bi bi-eyedropper"></i>
                    <p>No lab tests are configured yet. Please check back later.</p>
                </div>
            @endforelse
        </div>

        @if($labTests->isNotEmpty())
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="scheduled_date">Preferred Date <span class="req">*</span></label>
                    <input type="date" name="scheduled_date" id="scheduled_date"
                           value="{{ old('scheduled_date') }}"
                           min="{{ date('Y-m-d') }}" required>
                    @error('scheduled_date')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="scheduled_time">Preferred Time <span class="req">*</span></label>
                    <input type="time" name="scheduled_time" id="scheduled_time"
                           value="{{ old('scheduled_time') }}" required>
                    @error('scheduled_time')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group span-2">
                    <label for="clinical_notes">Notes</label>
                    <textarea name="clinical_notes" id="clinical_notes">{{ old('clinical_notes') }}</textarea>
                </div>
            </div>

            <button type="submit" class="btn"><i class="bi bi-send"></i> Submit Lab Request</button>
        @endif
    </form>
@endsection

