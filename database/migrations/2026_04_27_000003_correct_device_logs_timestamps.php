<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Correct timestamps that were stored 3 hours ahead due to a timezone mismatch
        // between PHP (Europe/Kyiv = UTC+3) and MySQL session (UTC).
        // PHP sent local Kyiv time as if it were UTC, so all stored values are +3h too late.
        DB::statement('UPDATE device_logs SET logged_at = DATE_SUB(logged_at, INTERVAL 3 HOUR)');
        DB::statement('UPDATE device_logs_archive SET logged_at = DATE_SUB(logged_at, INTERVAL 3 HOUR)');
    }

    public function down(): void
    {
        DB::statement('UPDATE device_logs SET logged_at = DATE_ADD(logged_at, INTERVAL 3 HOUR)');
        DB::statement('UPDATE device_logs_archive SET logged_at = DATE_ADD(logged_at, INTERVAL 3 HOUR)');
    }
};
