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

/*
 | NOTE: route parameter names (e.g. {patientId}) are written to MATCH the
 | controller method argument names. Laravel injects scalar route parameters
 | into controller methods by name, so they must be the same.
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

    // Patient-only routes
    Route::middleware('role:patient')->prefix('patient')->name('patient.')->group(function () {
        Route::get('profile', [PatientProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [PatientProfileController::class, 'update'])->name('profile.update');

        Route::get('appointments', [PatientAppointmentController::class, 'index'])->name('appointments.index');

        // Laboratory results & soft-copy requests
        Route::get('lab-results', [PatientLabRequestController::class, 'index'])->name('lab.index');
        Route::post('lab-results', [PatientLabRequestController::class, 'store'])->name('lab.store');

        // Request a lab test (no doctor consultation) + book a lab appointment
        Route::get('lab-tests/request', [PatientLabTestController::class, 'create'])->name('lab.request.create');
        Route::post('lab-tests/request', [PatientLabTestController::class, 'store'])->name('lab.request.store');
    });

    // Appointment booking — any authenticated role with the book-appointment permission
    Route::middleware('permission:book-appointment')->prefix('patient')->name('patient.')->group(function () {
        Route::get('appointments/book', [PatientAppointmentController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [PatientAppointmentController::class, 'store'])->name('appointments.store');
    });

    // Doctor
    Route::middleware('role:doctor')->prefix('doctor')->name('doctor.')->group(function () {
        Route::get('appointments', [DoctorAppointmentController::class, 'index'])->name('appointments.index');

        Route::get('patients', [PatientChartController::class, 'index'])->name('patients.index');
        Route::get('patients/{patientId}', [PatientChartController::class, 'show'])->name('patients.show');
        Route::post('patients/{patientId}/consultation', [PatientChartController::class, 'startConsultation'])->name('patients.consultation');

        Route::get('consultations/{consultationId}', [ConsultationController::class, 'show'])->name('consultation.show');
        Route::post('consultations/{consultationId}/diagnosis', [ConsultationController::class, 'storeDiagnosis'])->name('consultation.diagnosis');
        Route::post('consultations/{consultationId}/prescription', [ConsultationController::class, 'storePrescription'])->name('consultation.prescription');
    });

    // MedTech
    Route::middleware('role:med_tech')->prefix('medtech')->name('medtech.')->group(function () {
        Route::get('lab', [LabController::class, 'index'])->name('lab.index');
        Route::get('lab/items/{itemId}/result', [LabController::class, 'createResult'])->name('lab.result.create');
        Route::post('lab/items/{itemId}/result', [LabController::class, 'storeResult'])->name('lab.result.store');

        Route::get('soft-copy', [SoftCopyController::class, 'index'])->name('softcopy.index');
        Route::post('soft-copy/{requestId}/fulfill', [SoftCopyController::class, 'fulfill'])->name('softcopy.fulfill');
    });

    // Receptionist
    Route::middleware('role:receptionist')->prefix('receptionist')->name('receptionist.')->group(function () {
        Route::get('patients', [ReceptionistPatientController::class, 'index'])->name('patients.index');
        Route::get('patients/new', [ReceptionistPatientController::class, 'create'])->name('patients.create');
        Route::post('patients', [ReceptionistPatientController::class, 'store'])->name('patients.store');
        Route::get('patients/{patientId}', [ReceptionistPatientController::class, 'show'])->name('patients.show');
    });

    // Super Admin
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        // Role permissions
        Route::get('roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::get('roles/{roleId}/edit', [RolePermissionController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{roleId}', [RolePermissionController::class, 'update'])->name('roles.update');

        // Users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/new', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::post('users/{userId}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');

        // Maintenance: lab categories
        Route::get('lab-categories', [LabCategoryController::class, 'index'])->name('lab-categories.index');
        Route::get('lab-categories/new', [LabCategoryController::class, 'create'])->name('lab-categories.create');
        Route::post('lab-categories', [LabCategoryController::class, 'store'])->name('lab-categories.store');
        Route::get('lab-categories/{id}/edit', [LabCategoryController::class, 'edit'])->name('lab-categories.edit');
        Route::put('lab-categories/{id}', [LabCategoryController::class, 'update'])->name('lab-categories.update');
        Route::delete('lab-categories/{id}', [LabCategoryController::class, 'destroy'])->name('lab-categories.destroy');

        // Maintenance: lab tests
        Route::get('lab-tests', [LabTestController::class, 'index'])->name('lab-tests.index');
        Route::get('lab-tests/new', [LabTestController::class, 'create'])->name('lab-tests.create');
        Route::post('lab-tests', [LabTestController::class, 'store'])->name('lab-tests.store');
        Route::get('lab-tests/{id}/edit', [LabTestController::class, 'edit'])->name('lab-tests.edit');
        Route::put('lab-tests/{id}', [LabTestController::class, 'update'])->name('lab-tests.update');
        Route::delete('lab-tests/{id}', [LabTestController::class, 'destroy'])->name('lab-tests.destroy');

        // Doctor schedules
        Route::get('doctor-schedules', [DoctorScheduleController::class, 'index'])->name('doctor-schedules.index');
        Route::get('doctor-schedules/new', [DoctorScheduleController::class, 'create'])->name('doctor-schedules.create');
        Route::post('doctor-schedules', [DoctorScheduleController::class, 'store'])->name('doctor-schedules.store');
        Route::get('doctor-schedules/{id}/edit', [DoctorScheduleController::class, 'edit'])->name('doctor-schedules.edit');
        Route::put('doctor-schedules/{id}', [DoctorScheduleController::class, 'update'])->name('doctor-schedules.update');
        Route::delete('doctor-schedules/{id}', [DoctorScheduleController::class, 'destroy'])->name('doctor-schedules.destroy');

        // Audit dashboards
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('audit/dashboard/{role}', [AuditLogController::class, 'dashboard'])->name('audit.dashboard');
    });
});
