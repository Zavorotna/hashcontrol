<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mqtt_messages', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            $table->text('payload');
            $table->string('device_id')->comment('ID of the reader');
            $table->unsignedTinyInteger('action')->comment('Action type (1 for state change, etc.)');
            $table->string('data')->comment('Office ID or employee ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mqtt_messages');
    }
};
