<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // migration 2026-06-20 changed action to VARCHAR(20); this ensures
        // existing installs that ran the old schema also get the column widened
        try {
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(20) NOT NULL");
        } catch (\Throwable $e) {
            // already varchar or table missing — ignore
        }

        Paginator::defaultView('vendor.pagination.simple');
        Paginator::defaultSimpleView('vendor.pagination.simple');
    }
}
