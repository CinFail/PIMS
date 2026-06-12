@extends('layouts.app')
@section('title', 'Add Lab Test')
@section('content')
    <h1>Add Lab Test</h1>
    <a href="{{ route('admin.lab-tests.index') }}" class="btn btn-outline">Back</a>
    <form action="{{ route('admin.lab-tests.store') }}" method="POST">
        @csrf
        @include('admin.lab_tests._form', ['test' => null])
        <button type="submit" class="btn">Save</button>
    </form>
@endsection
