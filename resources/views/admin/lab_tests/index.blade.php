@extends('layouts.app')
@section('title', 'Lab Tests')
@section('content')
    <h1>Lab Tests</h1>
    <p class="page-subtitle">Maintenance: reusable laboratory test definitions.</p>

    <div class="btn-row">
        <a href="{{ route('admin.lab-tests.create') }}" class="btn">
            <i class="bi bi-plus-lg"></i> Add Lab Test
        </a>
    </div>

    <div class="table-card">
        <table>
            <tr><th>Test Name</th><th>Category</th><th>Unit</th><th>Reference Range</th><th>Active</th><th></th></tr>
            @foreach($tests as $t)
                <tr>
                    <td>{{ $t->test_name }}</td>
                    <td>{{ $t->category?->category_name }}</td>
                    <td>{{ $t->default_unit ?? '—' }}</td>
                    <td>{{ $t->default_reference_range ?? '—' }}</td>
                    <td>
                        <span class="tag {{ $t->is_active ? 'tag-green' : 'tag-red' }}">
                            {{ $t->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="row-actions">
                        <a href="{{ route('admin.lab-tests.edit', $t->lab_test_id) }}" class="btn btn-small">Edit</a>
                        <form action="{{ route('admin.lab-tests.toggle', $t->lab_test_id) }}" method="POST" class="inline-form">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-small btn-outline">
                                {{ $t->is_active ? 'Deactivate' : 'Reactivate' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="pagination-wrap">{{ $tests->links() }}</div>
@endsection
