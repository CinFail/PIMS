@extends('layouts.app')
@section('title', 'Patient Information')
@section('content')
    <h1>Patient Information</h1>
    <p class="page-subtitle">Look up patients during check-in.</p>

    <div class="btn-row">
        <a href="{{ route('receptionist.patients.create') }}" class="btn">
            <i class="bi bi-person-plus"></i> Add New Patient
        </a>
    </div>

    <form action="{{ route('receptionist.patients.index') }}" method="GET" class="search-form">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search patients...">
        <button type="submit" class="btn"><i class="bi bi-search"></i> Search</button>
    </form>

    @if($patients->isEmpty())
        <div class="empty-state">
            <i class="bi bi-person-x"></i>
            <p>No patients found.</p>
        </div>
    @else
        <div class="table-card">
            <table>
                <tr><th>Name</th><th>Email</th><th>Mobile</th><th></th></tr>
                @foreach($patients as $p)
                    <tr>
                        <td>{{ $p->user?->fullName() }}</td>
                        <td>{{ $p->user?->email }}</td>
                        <td>{{ $p->user?->mobile_number ?? '—' }}</td>
                        <td class="row-actions">
                            <a href="{{ route('receptionist.patients.show', $p->patient_id) }}" class="btn btn-small">View</a>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="pagination-wrap">{{ $patients->links() }}</div>
    @endif
@endsection
