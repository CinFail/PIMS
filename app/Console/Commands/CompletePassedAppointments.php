<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use Illuminate\Console\Command;

class CompletePassedAppointments extends Command
{
    protected $signature = 'appointments:complete-passed';
    protected $description = 'Mark past Scheduled appointments as Completed';

    public function handle(): void
    {
        $completed = AppointmentStatus::where('status_name', 'Completed')->first();

        if (! $completed) {
            $this->error('No "Completed" status found in appointment_statuses table.');
            return;
        }

        $count = Appointment::where('is_voided', 0)
            ->where('appointment_at', '<', now())
            ->whereHas('status', fn ($q) => $q->where('status_name', 'Scheduled'))
            ->update(['status_id' => $completed->appointment_status_id]);

        $this->info("Marked {$count} appointment(s) as Completed.");
    }
}
