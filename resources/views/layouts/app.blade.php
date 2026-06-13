<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PIMS @hasSection('title') - @yield('title') @endif</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $user = auth()->user();
    $role = $user?->primaryRole();
@endphp

<div class="topbar">
    <div class="brand">PIMS</div>
    <div class="user-box">
        {{ $user?->fullName() }}
        <span class="tag">{{ $user?->roles->first()->display_name ?? 'User' }}</span>
        &nbsp;
        <form action="{{ route('logout') }}" method="POST" class="inline-form">
            @csrf
            <button type="submit" class="btn btn-small">Logout</button>
        </form>
    </div>
</div>

<div class="wrapper">
    <div class="sidebar">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>

        @if($role === 'patient')
            <div class="group-label">Patient</div>
            <a href="{{ route('patient.profile.edit') }}" class="{{ request()->routeIs('patient.profile.*') ? 'active' : '' }}">Update Information</a>
            <a href="{{ route('patient.appointments.create') }}" class="{{ request()->routeIs('patient.appointments.create') ? 'active' : '' }}">Book a Doctor Appointment</a>
            <a href="{{ route('patient.lab.request.create') }}" class="{{ request()->routeIs('patient.lab.request.*') ? 'active' : '' }}">Request a Lab Test</a>
            <a href="{{ route('patient.appointments.index') }}" class="{{ request()->routeIs('patient.appointments.index') ? 'active' : '' }}">My Appointments</a>
            <a href="{{ route('patient.lab.index') }}" class="{{ request()->routeIs('patient.lab.index') ? 'active' : '' }}">My Lab Results</a>
        @endif

        @if($role === 'doctor')
            <div class="group-label">Doctor</div>
            <a href="{{ route('doctor.appointments.index') }}" class="{{ request()->routeIs('doctor.appointments.*') ? 'active' : '' }}">Scheduled Check-ups</a>
            <a href="{{ route('doctor.patients.index') }}" class="{{ request()->routeIs('doctor.patients.*') || request()->routeIs('doctor.consultation.*') ? 'active' : '' }}">Patient Records</a>
        @endif

        @if($role === 'med_tech')
            <div class="group-label">MedTech</div>
            <a href="{{ route('medtech.lab.index') }}" class="{{ request()->routeIs('medtech.lab.*') ? 'active' : '' }}">Scheduled Lab Tests</a>
            <a href="{{ route('medtech.softcopy.index') }}" class="{{ request()->routeIs('medtech.softcopy.*') ? 'active' : '' }}">Soft Copy Requests</a>
        @endif

        @if($role === 'receptionist')
            <div class="group-label">Receptionist</div>
            <a href="{{ route('receptionist.patients.index') }}" class="{{ request()->routeIs('receptionist.patients.index') || request()->routeIs('receptionist.patients.show') ? 'active' : '' }}">Patient Information</a>
            <a href="{{ route('receptionist.patients.create') }}" class="{{ request()->routeIs('receptionist.patients.create') ? 'active' : '' }}">Add New Patient</a>
        @endif

        @if($role === 'super_admin')
            <div class="group-label">Dashboards</div>
            <a href="{{ route('admin.audit.dashboard', 'patient') }}" class="{{ request()->routeIs('admin.audit.dashboard') && request()->route('role')=='patient' ? 'active' : '' }}">Patient Dashboard</a>
            <a href="{{ route('admin.audit.dashboard', 'medtech') }}" class="{{ request()->routeIs('admin.audit.dashboard') && request()->route('role')=='medtech' ? 'active' : '' }}">MedTech Dashboard</a>
            <a href="{{ route('admin.audit.dashboard', 'doctor') }}" class="{{ request()->routeIs('admin.audit.dashboard') && request()->route('role')=='doctor' ? 'active' : '' }}">Doctors Dashboard</a>
            <a href="{{ route('admin.audit.dashboard', 'receptionist') }}" class="{{ request()->routeIs('admin.audit.dashboard') && request()->route('role')=='receptionist' ? 'active' : '' }}">Receptionist Dashboard</a>
            <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.index') ? 'active' : '' }}">Full Audit Trail</a>

            <div class="group-label">Administration</div>
            <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Role Permissions</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>

            <div class="group-label">Maintenance</div>
            <a href="{{ route('admin.doctor-schedules.index') }}" class="{{ request()->routeIs('admin.doctor-schedules.*') ? 'active' : '' }}">Doctor Schedules</a>
            <a href="{{ route('admin.lab-categories.index') }}" class="{{ request()->routeIs('admin.lab-categories.*') ? 'active' : '' }}">Lab Categories</a>
            <a href="{{ route('admin.lab-tests.index') }}" class="{{ request()->routeIs('admin.lab-tests.*') ? 'active' : '' }}">Lab Tests</a>
        @endif
    </div>

    <div class="content">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <strong>Please fix the following:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</div>
</body>
</html>
