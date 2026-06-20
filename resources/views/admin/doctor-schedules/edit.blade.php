@extends('layouts.app')
@section('title', 'Edit Duty Session')
@section('content')
    <h1>Edit Duty Session</h1>

    <div class="btn-row">
        <a href="{{ route('doctor-schedules.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('doctor-schedules.update', $session->duty_session_id) }}" method="POST">
        @csrf @method('PUT')
        @include('doctor-schedules._form')
        <button type="submit" class="btn"><i class="bi bi-save"></i> Update</button>
    </form>
@endsection
