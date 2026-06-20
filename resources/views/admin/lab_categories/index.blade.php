@extends('layouts.app')
@section('title', 'Lab Categories')
@section('content')
    <h1>Lab Test Categories</h1>
    <p class="page-subtitle">Maintenance: groupings for laboratory tests.</p>

    <div class="btn-row">
        <a href="{{ route('lab-categories.create') }}" class="btn">
            <i class="bi bi-plus-lg"></i> Add Category
        </a>
    </div>

    <div class="table-card">
        <table>
            <tr><th>Name</th><th>Description</th><th>Tests</th><th>Status</th><th></th></tr>
            @foreach($categories as $c)
                <tr>
                    <td>{{ $c->category_name }}</td>
                    <td>{{ $c->description ?? '—' }}</td>
                    <td>{{ $c->tests_count }}</td>
                    <td>
                        <span class="tag {{ $c->is_active ? 'tag-green' : 'tag-red' }}">
                            {{ $c->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="row-actions">
                        <a href="{{ route('lab-categories.edit', $c->lab_category_id) }}" class="btn btn-small">Edit</a>
                        <form action="{{ route('lab-categories.toggle', $c->lab_category_id) }}" method="POST" class="inline-form">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-small btn-outline">
                                {{ $c->is_active ? 'Deactivate' : 'Reactivate' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection
