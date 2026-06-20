@extends('layouts.app')
@section('title', 'Edit Lab Test')
@section('content')
    <h1>Edit Lab Test</h1>

    <div class="btn-row">
        <a href="{{ route('lab-tests.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('lab-tests.update', $test->lab_test_id) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.lab_tests._form')
        <button type="submit" class="btn"><i class="bi bi-save"></i> Update</button>
    </form>
@endsection
