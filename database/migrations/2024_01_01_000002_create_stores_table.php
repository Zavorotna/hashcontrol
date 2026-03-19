<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('mqtt_device_id')->unique(); // ID що приходить з MQTT (напр: "STORE_01")
            $table->string('name');                     // "Магазин №1"
            $table->string('employee_name')->nullable(); // відповідальний працівник
            $table->string('location')->nullable();      // адреса/місцезнаходження
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
