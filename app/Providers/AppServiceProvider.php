<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Safe default for older MySQL/MariaDB string length handling.
        Schema::defaultStringLength(191);

        // Self-healing: widen audit_logs.action from ENUM to VARCHAR so any
        // action name (RESTORE, REJECT, etc.) is accepted without schema edits.
        try {
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(20) NOT NULL");
        } catch (\Throwable $e) {
            // Already VARCHAR or table not yet created — nothing to do.
        }

        // Use our simple black & white pagination markup (no Tailwind needed).
        Paginator::defaultView('vendor.pagination.simple');
        Paginator::defaultSimpleView('vendor.pagination.simple');
    }
}
