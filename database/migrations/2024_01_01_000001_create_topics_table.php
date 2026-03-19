<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // "Aquapark Kyiv"
            $table->string('slug')->unique();                 // "aquapark" — відповідає MQTT топіку
            $table->text('description')->nullable();
            $table->boolean('has_generator')->default(false); // чи є генератор у цьому топіку
            $table->decimal('fuel_rate_per_hour', 8, 2)->default(5.00); // літрів/год
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
