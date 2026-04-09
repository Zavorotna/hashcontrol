<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make action nullable and string in mqtt_messages (act field may not always be present)
        Schema::table('mqtt_messages', function (Blueprint $table) {
            $table->string('action')->nullable()->comment('Action name from MQTT payload (optional)')->change();
        });

        // Make action_id nullable in device_logs (action may be absent in MQTT message)
        Schema::table('device_logs', function (Blueprint $table) {
            $table->foreignId('action_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mqtt_messages', function (Blueprint $table) {
            $table->unsignedTinyInteger('action')->nullable(false)->change();
        });

        Schema::table('device_logs', function (Blueprint $table) {
            $table->foreignId('action_id')->nullable(false)->change();
        });
    }
};
