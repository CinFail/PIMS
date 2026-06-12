@extends('layouts.app')
@section('title', 'Add Duty Session')
@section('content')
    <h1>Add Duty Session</h1>
    <a href="{{ route('admin.doctor-schedules.index') }}" class="btn btn-outline">Back</a>

    <form action="{{ route('admin.doctor-schedules.store') }}" method="POST">
        @csrf
        @include('admin.doctor-schedules._form')
        <button type="submit" class="btn">Save</button>
    </form>
@endsection
