<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;

// Patient
use App\Http\Controllers\Patient\ProfileController as PatientProfileController;
use App\Http\Controllers\Patient\AppointmentController as PatientAppointmentController;
use App\Http\Controllers\Patient\LabResultRequestController as PatientLabRequestController;
use App\Http\Controllers\Patient\LabTestRequestController as PatientLabTestController;

// Doctor
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Doctor\PatientChartController;
use App\Http\Controllers\Doctor\ConsultationController;

// MedTech
use App\Http\Controllers\MedTech\LabController;
use App\Http\Controllers\MedTech\SoftCopyController;

// Receptionist
use App\Http\Controllers\Receptionist\PatientController as ReceptionistPatientController;

// Admin
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LabCategoryController;
use App\Http\Controllers\Admin\LabTestController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DoctorScheduleController;
use App\Http\Controllers\Admin\VoidRequestController;

/*
 | Route parameter names match controller method argument names so Laravel
 | injects them correctly by name.
 |
 | Middleware strategy:
 |   - Role-specific features (patient profile, doctor consultations, etc.)
 |     use ['role:X', 'permission:Y'] — role gates the section, permission
 |     controls whether the feature is enabled within that role.
 |   - Cross-role features (appointment booking, audit logs, admin pages)
 |     use 'permission:Y' only — any role granted that permission gains access.
 */

// Public routes
Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Authenticated routes
Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Patient profile (patient role + permission)
    Route::middleware(['role:patient', 'permission:update-profile'])->prefix('patient')->name('patient.')->group(function () {
        Route::get('profile', [PatientProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [PatientProfileController::class, 'update'])->name('profile.update');
    });

    // Patient — view own appointments
    Route::middleware(['role:patient', 'permission:book-appointment'])->prefix('patient')->name('patient.')->group(function () {
        Route::get('appointments', [PatientAppointmentController::class, 'index'])->name('appointments.index');
    });

    // Patient — lab result requests (patient role + permission)
    Route::middleware(['role:patient', 'permission:request-lab-result'])->prefix('patient')->name('patient.')->group(function () {
        Route::get('lab-results', [PatientLabRequestController::class, 'index'])->name('lab.index');
        Route::post('lab-results', [PatientLabRequestController::class, 'store'])->name('lab.store');
        Route::get('lab-tests/request', [PatientLabTestController::class, 'create'])->name('lab.request.create');
        Route::post('lab-tests/request', [PatientLabTestController::class, 'store'])->name('lab.request.store');
    });

    // Appointment booking — cross-role, any user with the permission
    Route::middleware('permission:book-appointment')->prefix('patient')->name('patient.')->group(function () {
        Route::get('appointments/book', [PatientAppointmentController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [PatientAppointmentController::class, 'store'])->name('appointments.store');
        Route::post('appointments/{id}/reschedule', [PatientAppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    });

    // Doctor — consultation management (doctor role + permission)
    Route::middleware(['role:doctor', 'permission:manage-consultation'])->prefix('doctor')->name('doctor.')->group(function () {
        Route::get('appointments', [DoctorAppointmentController::class, 'index'])->name('appointments.index');
        Route::post('appointments/{appointmentId}/status', [DoctorAppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::get('consultations/{consultationId}', [ConsultationController::class, 'show'])->name('consultation.show');
        Route::put('consultations/{consultationId}', [ConsultationController::class, 'update'])->name('consultation.update');
        Route::patch('diagnoses/{diagnosisId}', [ConsultationController::class, 'updateDiagnosis'])->name('diagnosis.update');
        Route::patch('prescriptions/{prescriptionId}', [ConsultationController::class, 'updatePrescription'])->name('prescription.update');
        Route::patch('prescription-items/{itemId}', [ConsultationController::class, 'updatePrescriptionItem'])->name('prescription.item.update');
        Route::post('patients/{patientId}/consultation', [PatientChartController::class, 'startConsultation'])->name('patients.consultation');
    });

    // Doctor — patient records (doctor role + permission)
    Route::middleware(['role:doctor', 'permission:view-patient-records'])->prefix('doctor')->name('doctor.')->group(function () {
        Route::get('patients', [PatientChartController::class, 'index'])->name('patients.index');
        Route::get('patients/{patientId}', [PatientChartController::class, 'show'])->name('patients.show');
    });

    // Doctor — record diagnosis (doctor role + permission)
    Route::middleware(['role:doctor', 'permission:record-diagnosis'])->prefix('doctor')->name('doctor.')->group(function () {
        Route::post('consultations/{consultationId}/diagnosis', [ConsultationController::class, 'storeDiagnosis'])->name('consultation.diagnosis');
    });

    // Doctor — issue prescription (doctor role + permission)
    Route::middleware(['role:doctor', 'permission:issue-prescription'])->prefix('doctor')->name('doctor.')->group(function () {
        Route::post('consultations/{consultationId}/prescription', [ConsultationController::class, 'storePrescription'])->name('consultation.prescription');
        Route::delete('prescription-items/{itemId}', [ConsultationController::class, 'destroyItem'])->name('prescription.item.destroy');
    });

    // MedTech — process lab requests (medtech role + permission)
    Route::middleware(['role:med_tech', 'permission:process-lab-request'])->prefix('medtech')->name('medtech.')->group(function () {
        Route::get('lab', [LabController::class, 'index'])->name('lab.index');
    });

    // MedTech — upload lab results (medtech role + permission)
    Route::middleware(['role:med_tech', 'permission:upload-lab-result'])->prefix('medtech')->name('medtech.')->group(function () {
        Route::get('lab/items/{itemId}/result', [LabController::class, 'createResult'])->name('lab.result.create');
        Route::post('lab/items/{itemId}/result', [LabController::class, 'storeResult'])->name('lab.result.store');
    });

    // MedTech — release / soft-copy (medtech role + permission)
    Route::middleware(['role:med_tech', 'permission:release-lab-result'])->prefix('medtech')->name('medtech.')->group(function () {
        Route::get('soft-copy', [SoftCopyController::class, 'index'])->name('softcopy.index');
        Route::post('soft-copy/{requestId}/fulfill', [SoftCopyController::class, 'fulfill'])->name('softcopy.fulfill');
        Route::post('lab/items/{itemId}/release', [LabController::class, 'releaseResult'])->name('lab.result.release');
    });

    // Void requests — submission open to any authenticated user (doctor, medtech, etc.)
    Route::post('void-request', [VoidRequestController::class, 'store'])->name('void.store');

    // Receptionist — patient list + add (receptionist role + permission)
    Route::middleware(['role:receptionist', 'permission:manage-patients'])->prefix('receptionist')->name('receptionist.')->group(function () {
        Route::get('patients', [ReceptionistPatientController::class, 'index'])->name('patients.index');
        Route::get('patients/new', [ReceptionistPatientController::class, 'create'])->name('patients.create');
        Route::post('patients', [ReceptionistPatientController::class, 'store'])->name('patients.store');
    });

    // Receptionist — view individual patient (receptionist role + permission)
    Route::middleware(['role:receptionist', 'permission:view-patient-info'])->prefix('receptionist')->name('receptionist.')->group(function () {
        Route::get('patients/{patientId}', [ReceptionistPatientController::class, 'show'])->name('patients.show');
    });

    // Audit logs — cross-role
    Route::middleware('permission:view-audit-logs')->prefix('admin')->name('admin.')->group(function () {
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('audit/dashboard/{role}', [AuditLogController::class, 'dashboard'])->name('audit.dashboard');
    });

    // Role permissions management — cross-role
    Route::middleware('permission:manage-roles')->prefix('admin')->name('admin.')->group(function () {
        Route::get('roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::get('roles/{roleId}/edit', [RolePermissionController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{roleId}', [RolePermissionController::class, 'update'])->name('roles.update');
    });

    // User management — cross-role
    Route::middleware('permission:manage-users')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/new', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::post('users/{userId}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
    });

    // Void request queue — admin approval, direct void, restore
    Route::middleware('permission:manage-maintenance')->prefix('admin')->name('admin.')->group(function () {
        Route::get('void-requests', [VoidRequestController::class, 'index'])->name('void.index');
        Route::post('void-requests/{id}/approve', [VoidRequestController::class, 'approve'])->name('void.approve');
        Route::post('void-requests/{id}/reject', [VoidRequestController::class, 'reject'])->name('void.reject');
        Route::post('void-requests/{id}/restore', [VoidRequestController::class, 'restore'])->name('void.restore');
        Route::post('void-direct', [VoidRequestController::class, 'adminVoid'])->name('void.admin');
    });

    // Maintenance — cross-role
    Route::middleware('permission:manage-maintenance')->prefix('admin')->name('admin.')->group(function () {
        Route::get('lab-categories', [LabCategoryController::class, 'index'])->name('lab-categories.index');
        Route::get('lab-categories/new', [LabCategoryController::class, 'create'])->name('lab-categories.create');
        Route::post('lab-categories', [LabCategoryController::class, 'store'])->name('lab-categories.store');
        Route::get('lab-categories/{id}/edit', [LabCategoryController::class, 'edit'])->name('lab-categories.edit');
        Route::put('lab-categories/{id}', [LabCategoryController::class, 'update'])->name('lab-categories.update');
        Route::delete('lab-categories/{id}', [LabCategoryController::class, 'destroy'])->name('lab-categories.destroy');
        Route::patch('lab-categories/{id}/toggle', [LabCategoryController::class, 'toggleActive'])->name('lab-categories.toggle');

        Route::get('lab-tests', [LabTestController::class, 'index'])->name('lab-tests.index');
        Route::get('lab-tests/new', [LabTestController::class, 'create'])->name('lab-tests.create');
        Route::post('lab-tests', [LabTestController::class, 'store'])->name('lab-tests.store');
        Route::get('lab-tests/{id}/edit', [LabTestController::class, 'edit'])->name('lab-tests.edit');
        Route::put('lab-tests/{id}', [LabTestController::class, 'update'])->name('lab-tests.update');
        Route::delete('lab-tests/{id}', [LabTestController::class, 'destroy'])->name('lab-tests.destroy');
        Route::patch('lab-tests/{id}/toggle', [LabTestController::class, 'toggleActive'])->name('lab-tests.toggle');

        Route::get('doctor-schedules', [DoctorScheduleController::class, 'index'])->name('doctor-schedules.index');
        Route::get('doctor-schedules/new', [DoctorScheduleController::class, 'create'])->name('doctor-schedules.create');
        Route::post('doctor-schedules', [DoctorScheduleController::class, 'store'])->name('doctor-schedules.store');
        Route::get('doctor-schedules/{id}/edit', [DoctorScheduleController::class, 'edit'])->name('doctor-schedules.edit');
        Route::put('doctor-schedules/{id}', [DoctorScheduleController::class, 'update'])->name('doctor-schedules.update');
        Route::delete('doctor-schedules/{id}', [DoctorScheduleController::class, 'destroy'])->name('doctor-schedules.destroy');
        Route::patch('doctor-schedules/{id}/toggle', [DoctorScheduleController::class, 'toggleActive'])->name('doctor-schedules.toggle');
    });
});
