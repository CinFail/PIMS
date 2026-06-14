<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /** Scheduled check-ups: own queue for doctors, all appointments for admin. */
    public function index()
    {
        $doctor = Auth::user()->doctorProfile;

        if ($doctor) {
            $appointments = Appointment::with(['patient.user', 'status'])
                ->where('doctor_id', $doctor->doctor_id)
                ->where('is_voided', 0)
                ->where('appointment_at', '>=', now())
                ->orderBy('appointment_at')
                ->get();
            $byDoctor = null;
        } else {
            $appointments = collect();
            $byDoctor = Appointment::with(['patient.user', 'doctor.user', 'status'])
                ->where('is_voided', 0)
                ->whereDate('appointment_at', today())
                ->orderBy('appointment_at')
                ->get()
                ->groupBy('doctor_id');
        }

        return view('doctor.appointments', compact('appointments', 'doctor', 'byDoctor'));
    }
}
