<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('PIMS - Clinic Patient Information Management System');
})->purpose('Display an inspiring quote');

// Mark past Scheduled appointments as Completed every day at midnight
Schedule::command('appointments:complete-passed')->dailyAt('00:01');
