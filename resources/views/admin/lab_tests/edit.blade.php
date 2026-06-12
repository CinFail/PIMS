@extends('layouts.app')
@section('title', 'Edit Lab Test')
@section('content')
    <h1>Edit Lab Test</h1>
    <a href="{{ route('admin.lab-tests.index') }}" class="btn btn-outline">Back</a>
    <form action="{{ route('admin.lab-tests.update', $test->lab_test_id) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.lab_tests._form')
        <button type="submit" class="btn">Update</button>
    </form>
@endsection
