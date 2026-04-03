<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // null  = одиничний (температура, лічильник — без пари)
            // true  = початок діапазону (вхід, запуск, прихід)
            // false = кінець діапазону  (вихід, зупинка, відхід)
            $table->boolean('is_range_start')->nullable()->default(null)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('is_range_start');
        });
    }
};
