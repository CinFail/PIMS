@extends('layouts.app')
@section('title', 'Edit Duty Session')
@section('content')
    <h1>Edit Duty Session</h1>
    <a href="{{ route('admin.doctor-schedules.index') }}" class="btn btn-outline">Back</a>

    <form action="{{ route('admin.doctor-schedules.update', $session->duty_session_id) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.doctor-schedules._form')
        <button type="submit" class="btn">Update</button>
    </form>
@endsection
