<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /** Scheduled check-ups: the queue of this doctor's appointments. */
    public function index()
    {
        $doctor = Auth::user()->doctorProfile;

        $appointments = $doctor
            ? Appointment::with(['patient.user', 'status'])
                ->where('doctor_id', $doctor->doctor_id)
                ->where('is_voided', 0)
                ->orderByDesc('appointment_at')
                ->get()
            : collect();

        return view('doctor.appointments', compact('appointments'));
    }
}
