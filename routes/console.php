<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('PIMS - Clinic Patient Information Management System');
})->purpose('Display an inspiring quote');
