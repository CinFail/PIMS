@extends('layouts.app')
@section('title', 'Patient Records')
@section('content')
    <h1>Patient Records</h1>
    <p class="page-subtitle">The patient database. Search by name or email.</p>

    <form action="{{ route('doctor.patients.index') }}" method="GET" class="search-form">
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
                <tr><th>Name</th><th>Email</th><th>Age</th><th>Sex</th><th>Blood Type</th><th></th></tr>
                @foreach($patients as $p)
                    <tr>
                        <td>{{ $p->user?->fullName() }}</td>
                        <td>{{ $p->user?->email }}</td>
                        <td>{{ $p->user?->age() !== null ? $p->user->age() : '—' }}</td>
                        <td>{{ $p->sex ?? '—' }}</td>
                        <td>{{ $p->blood_type ?? '—' }}</td>
                        <td class="row-actions">
                            <a href="{{ route('doctor.patients.show', $p->patient_id) }}" class="btn btn-small">Open Chart</a>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="pagination-wrap">{{ $patients->links() }}</div>
    @endif
@endsection
