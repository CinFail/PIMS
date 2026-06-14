<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PIMS @hasSection('title') - @yield('title') @endif</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
@php
    $user = auth()->user();
    $role = $user?->primaryRole();
@endphp

<div class="topbar">
    <div class="brand">
        <img src="{{ asset('images/logo.jpg') }}" alt="EGBC Diagnostic and Medical Services" class="brand-logo">
    </div>
    <div class="user-box">
        {{ $user?->fullName() }}
        <span class="role-badge">{{ $user?->roles->first()->display_name ?? 'User' }}</span>
        <form action="{{ route('logout') }}" method="POST" class="inline-form">
            @csrf
            <button type="submit" class="btn btn-small btn-outline">Logout</button>
        </form>
    </div>
</div>

<div class="wrapper">
    <div class="sidebar">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>

        {{-- Patient section: patient-only items require the role; booking is cross-role --}}
        @if($user->hasPermission('update-profile') || $user->hasPermission('book-appointment') || $user->hasPermission('request-lab-result'))
            <div class="group-label">Patient</div>
            @if($user->hasRole('patient') && $user->hasPermission('update-profile'))
                <a href="{{ route('patient.profile.edit') }}" class="{{ request()->routeIs('patient.profile.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i> Update Information
                </a>
            @endif
            @if($user->hasPermission('book-appointment'))
                <a href="{{ route('patient.appointments.create') }}" class="{{ request()->routeIs('patient.appointments.create') ? 'active' : '' }}">
                    <i class="bi bi-calendar-plus"></i> Book a Doctor Appointment
                </a>
            @endif
            @if($user->hasRole('patient') && $user->hasPermission('request-lab-result'))
                <a href="{{ route('patient.lab.request.create') }}" class="{{ request()->routeIs('patient.lab.request.*') ? 'active' : '' }}">
                    <i class="bi bi-droplet"></i> Request a Lab Test
                </a>
            @endif
            @if($user->hasRole('patient') && $user->hasPermission('book-appointment'))
                <a href="{{ route('patient.appointments.index') }}" class="{{ request()->routeIs('patient.appointments.index') ? 'active' : '' }}">
                    <i class="bi bi-calendar3"></i> My Appointments
                </a>
            @endif
            @if($user->hasRole('patient') && $user->hasPermission('request-lab-result'))
                <a href="{{ route('patient.lab.index') }}" class="{{ request()->routeIs('patient.lab.index') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-medical"></i> My Lab Results
                </a>
            @endif
        @endif

        @if($user->hasPermission('manage-consultation') || $user->hasPermission('view-patient-records'))
            <div class="group-label">Doctor</div>
            @if($user->hasPermission('manage-consultation'))
                <a href="{{ route('doctor.appointments.index') }}" class="{{ request()->routeIs('doctor.appointments.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-check"></i> Scheduled Check-ups
                </a>
            @endif
            @if($user->hasPermission('view-patient-records'))
                <a href="{{ route('doctor.patients.index') }}" class="{{ request()->routeIs('doctor.patients.*') || request()->routeIs('doctor.consultation.*') ? 'active' : '' }}">
                    <i class="bi bi-person-vcard"></i> Patient Records
                </a>
            @endif
        @endif

        @if($user->hasPermission('process-lab-request') || $user->hasPermission('upload-lab-result') || $user->hasPermission('release-lab-result'))
            <div class="group-label">MedTech</div>
            @if($user->hasPermission('process-lab-request') || $user->hasPermission('upload-lab-result'))
                <a href="{{ route('medtech.lab.index') }}" class="{{ request()->routeIs('medtech.lab.*') ? 'active' : '' }}">
                    <i class="bi bi-eyedropper"></i> Scheduled Lab Tests
                </a>
            @endif
            @if($user->hasPermission('release-lab-result'))
                <a href="{{ route('medtech.softcopy.index') }}" class="{{ request()->routeIs('medtech.softcopy.*') ? 'active' : '' }}">
                    <i class="bi bi-cloud-download"></i> Soft Copy Requests
                </a>
            @endif
        @endif

        @if($user->hasPermission('manage-patients') || $user->hasPermission('view-patient-info'))
            <div class="group-label">Receptionist</div>
            <a href="{{ route('receptionist.patients.index') }}" class="{{ request()->routeIs('receptionist.patients.index') || request()->routeIs('receptionist.patients.show') ? 'active' : '' }}">
                <i class="bi bi-person-lines-fill"></i> Patient Information
            </a>
            @if($user->hasPermission('manage-patients'))
                <a href="{{ route('receptionist.patients.create') }}" class="{{ request()->routeIs('receptionist.patients.create') ? 'active' : '' }}">
                    <i class="bi bi-person-plus"></i> Add New Patient
                </a>
            @endif
        @endif

        @if($user->hasPermission('view-audit-logs'))
            <div class="group-label">Dashboards</div>
            <a href="{{ route('admin.audit.dashboard', 'patient') }}" class="{{ request()->routeIs('admin.audit.dashboard') && request()->route('role')=='patient' ? 'active' : '' }}">
                <i class="bi bi-person-heart"></i> Patient Dashboard
            </a>
            <a href="{{ route('admin.audit.dashboard', 'medtech') }}" class="{{ request()->routeIs('admin.audit.dashboard') && request()->route('role')=='medtech' ? 'active' : '' }}">
                <i class="bi bi-eyedropper"></i> MedTech Dashboard
            </a>
            <a href="{{ route('admin.audit.dashboard', 'doctor') }}" class="{{ request()->routeIs('admin.audit.dashboard') && request()->route('role')=='doctor' ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Doctors Dashboard
            </a>
            <a href="{{ route('admin.audit.dashboard', 'receptionist') }}" class="{{ request()->routeIs('admin.audit.dashboard') && request()->route('role')=='receptionist' ? 'active' : '' }}">
                <i class="bi bi-person-workspace"></i> Receptionist Dashboard
            </a>
            <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.index') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data"></i> Full Audit Trail
            </a>
        @endif

        @if($user->hasPermission('manage-roles') || $user->hasPermission('manage-users'))
            <div class="group-label">Administration</div>
            @if($user->hasPermission('manage-roles'))
                <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock"></i> Role Permissions
                </a>
            @endif
            @if($user->hasPermission('manage-users'))
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Users
                </a>
            @endif
        @endif

        @if($user->hasPermission('manage-maintenance'))
            <div class="group-label">Maintenance</div>
            <a href="{{ route('admin.doctor-schedules.index') }}" class="{{ request()->routeIs('admin.doctor-schedules.*') ? 'active' : '' }}">
                <i class="bi bi-calendar2-week"></i> Doctor Schedules
            </a>
            <a href="{{ route('admin.lab-categories.index') }}" class="{{ request()->routeIs('admin.lab-categories.*') ? 'active' : '' }}">
                <i class="bi bi-folder2-open"></i> Lab Categories
            </a>
            <a href="{{ route('admin.lab-tests.index') }}" class="{{ request()->routeIs('admin.lab-tests.*') ? 'active' : '' }}">
                <i class="bi bi-journal-medical"></i> Lab Tests
            </a>
        @endif
    </div>

    <div class="content">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
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

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* 1 — Wrap bare tables in card containers for rounded-corner styling */
    document.querySelectorAll('.content > table').forEach(function (t) {
        var d = document.createElement('div');
        d.className = 'table-card';
        t.parentNode.insertBefore(d, t);
        d.appendChild(t);
    });

    /* 2 — Mobile number mask: formats as 09XX-XXX-XXXX while typing */
    function fmtMobile(raw) {
        var d = raw.replace(/\D/g, '').substring(0, 11);
        if (d.length > 7) return d.substring(0, 4) + '-' + d.substring(4, 7) + '-' + d.substring(7);
        if (d.length > 4) return d.substring(0, 4) + '-' + d.substring(4);
        return d;
    }
    document.querySelectorAll('[data-mobile]').forEach(function (el) {
        if (el.value) el.value = fmtMobile(el.value);
        el.addEventListener('input', function () { this.value = fmtMobile(this.value); });
        el.addEventListener('keydown', function (e) {
            if (e.key.length === 1 && !/\d/.test(e.key) && !e.ctrlKey && !e.metaKey) e.preventDefault();
        });
    });
    /* Strip formatting dashes before submit so backend receives 11 plain digits */
    document.querySelectorAll('form').forEach(function (f) {
        f.addEventListener('submit', function () {
            f.querySelectorAll('[data-mobile]').forEach(function (el) {
                el.value = el.value.replace(/\D/g, '');
            });
        });
    });

    /* 3 — Admin create-user: show role-specific fields based on selected role */
    var roleSelect    = document.getElementById('role');
    var doctorFields  = document.getElementById('doctor-fields');
    var patientFields = document.getElementById('patient-fields');
    if (roleSelect && (doctorFields || patientFields)) {
        function syncRoleFields() {
            var v = roleSelect.value;
            if (doctorFields)  doctorFields.style.display  = (v === 'doctor' || v === 'med_tech') ? '' : 'none';
            if (patientFields) patientFields.style.display = (v === 'patient') ? '' : 'none';
        }
        roleSelect.addEventListener('change', syncRoleFields);
        syncRoleFields();
    }

});
</script>
@stack('scripts')
</body>
</html>
