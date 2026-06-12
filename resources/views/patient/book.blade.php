@extends('layouts.app')
@section('title', 'Book an Appointment')
@section('content')
    <h1>Book an Appointment</h1>
    <p class="page-subtitle">Choose an available doctor schedule. Once taken, a slot cannot be booked again.</p>

    @if($sessions->isEmpty())
        <p class="muted">There are no open schedules right now. Please check back later.</p>
    @else
    <form action="{{ route('patient.appointments.store') }}" method="POST">
        @csrf

        <h2>Available Schedules</h2>
        <div class="form-group" style="max-width:none;">
            @foreach($sessions as $s)
                <div class="checkbox-row">
                    <input type="radio" name="duty_session_id" id="s{{ $s->duty_session_id }}" value="{{ $s->duty_session_id }}" @checked(old('duty_session_id')==$s->duty_session_id) required>
                    <label for="s{{ $s->duty_session_id }}">
                        {{ \Illuminate\Support\Carbon::parse($s->duty_date)->format('M d, Y') }},
                        {{ \Illuminate\Support\Carbon::parse($s->start_time)->format('g:i A') }} -
                        {{ \Illuminate\Support\Carbon::parse($s->end_time)->format('g:i A') }}
                        &mdash; Dr. {{ $s->doctor?->user?->fullName() }}
                        @if($s->doctor?->specialization) ({{ $s->doctor->specialization }}) @endif
                        @if($s->doctor?->short_bio)
                            <div class="help">{{ $s->doctor->short_bio }}</div>
                        @endif
                    </label>
                </div>
            @endforeach
        </div>

        <div class="form-group" style="max-width:520px;">
            <label for="reason_for_visit">Reason for Visit (optional)</label>
            <textarea name="reason_for_visit" id="reason_for_visit">{{ old('reason_for_visit') }}</textarea>
        </div>

        <h2>Laboratory Tests (optional)</h2>
        <p class="help">Tick any tests you would like requested along with this appointment.</p>
        <div class="form-group" style="max-width:none;">
            @forelse($labTests as $t)
                <div class="checkbox-row">
                    <input type="checkbox" name="lab_tests[]" id="t{{ $t->lab_test_id }}" value="{{ $t->lab_test_id }}">
                    <label for="t{{ $t->lab_test_id }}">{{ $t->test_name }} <span class="muted">({{ $t->category?->category_name }})</span></label>
                </div>
            @empty
                <p class="muted">No lab tests are configured yet.</p>
            @endforelse
        </div>

        <button type="submit" class="btn">Confirm Booking</button>
    </form>
    @endif
@endsection
