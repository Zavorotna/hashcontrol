<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('topic_id')
                  ->nullable()
                  ->after('role')
                  ->constrained()
                  ->nullOnDelete();

            // JSON масив увімкнених віджетів дашборду
            // Можливі значення: live_status, generator, recent_messages, stats_today, chart
            $table->json('dashboard_widgets')
                  ->nullable()
                  ->after('topic_id')
                  ->comment('Налаштування віджетів дашборду');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->dropColumn(['topic_id', 'dashboard_widgets']);
        });
    }
};
