@extends('layouts.app')
@section('title', 'Book an Appointment')
@section('content')
    <h1>Book an Appointment</h1>
    <p class="page-subtitle">Choose an available doctor schedule.</p>

    @if($sessions->isEmpty())
        <div class="empty-state">
            <i class="bi bi-calendar2-x"></i>
            <p>There are no open schedules right now. Please check back later.</p>
        </div>
    @else
    <form action="{{ route('patient.appointments.store') }}" method="POST">
        @csrf

        <h2>Available Schedules <span class="req">*</span></h2>
        <div class="form-group" style="max-width:none;">
            @foreach($sessions as $s)
                <div class="checkbox-row">
                    <input type="radio" name="duty_session_id" id="s{{ $s->duty_session_id }}"
                           value="{{ $s->duty_session_id }}"
                           @checked(old('duty_session_id') == $s->duty_session_id) required>
                    <label for="s{{ $s->duty_session_id }}">
                        {{ \Illuminate\Support\Carbon::parse($s->duty_date)->format('M d, Y') }},
                        {{ \Illuminate\Support\Carbon::parse($s->start_time)->format('g:i A') }} –
                        {{ \Illuminate\Support\Carbon::parse($s->end_time)->format('g:i A') }}
                        — Dr. {{ $s->doctor?->user?->fullName() }}
                        @if($s->doctor?->specialization) ({{ $s->doctor->specialization }}) @endif
                        @if($s->doctor?->short_bio)
                            <span class="muted" style="display:block;font-size:12px;">{{ $s->doctor->short_bio }}</span>
                        @endif
                    </label>
                </div>
            @endforeach
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="preferred_time">Preferred Time</label>
                <input type="time" name="preferred_time" id="preferred_time"
                       value="{{ old('preferred_time') }}"
                       min="08:00" max="18:00" step="1800">
                @error('preferred_time')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label for="reason_for_visit">Reason for Visit</label>
                <textarea name="reason_for_visit" id="reason_for_visit">{{ old('reason_for_visit') }}</textarea>
            </div>
        </div>

        <h2>Laboratory Tests</h2>
        <div class="form-group" style="max-width:none;">
            @forelse($labTests as $t)
                <div class="checkbox-row">
                    <input type="checkbox" name="lab_tests[]" id="t{{ $t->lab_test_id }}" value="{{ $t->lab_test_id }}">
                    <label for="t{{ $t->lab_test_id }}">
                        {{ $t->test_name }}
                        <span class="muted">({{ $t->category?->category_name }})</span>
                    </label>
                </div>
            @empty
                <p class="muted">No lab tests configured.</p>
            @endforelse
        </div>

        <button type="submit" class="btn"><i class="bi bi-calendar-check"></i> Confirm Booking</button>
    </form>
    @endif
@endsection
