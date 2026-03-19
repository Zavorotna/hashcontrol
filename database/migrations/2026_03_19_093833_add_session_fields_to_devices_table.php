<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Змінюємо enum на string — щоб підтримувати нові типи без міграцій
            $table->string('type')->change();

            // Нові поля
            $table->string('session_type')->default('generic')->after('type');
            $table->decimal('metric_rate_per_hour', 8, 2)->nullable()->after('session_type');
            $table->string('metric_unit')->nullable()->after('metric_rate_per_hour');
            $table->string('metric_label')->nullable()->after('metric_unit');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['session_type', 'metric_rate_per_hour', 'metric_unit', 'metric_label']);

            // Повертаємо enum
            $table->enum('type', ['reader_open', 'reader_close', 'generator_on', 'generator_off'])->change();
        });
    }
};