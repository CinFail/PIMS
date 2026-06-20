@extends('layouts.app')
@section('title', 'Add Duty Session')
@section('content')
    <h1>Add Duty Session</h1>

    <div class="btn-row">
        <a href="{{ route('admin.doctor-schedules.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.doctor-schedules.store') }}" method="POST">
        @csrf
        @include('doctor-schedules._form')
        <button type="submit" class="btn"><i class="bi bi-save"></i> Save</button>
    </form>
@endsection
