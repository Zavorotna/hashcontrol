<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('store_sessions');
        Schema::dropIfExists('generator_sessions');
    }

    public function down(): void
    {
        // Таблиці не відновлюємо — якщо потрібен rollback,
        // використовуй резервну копію БД
    }
};