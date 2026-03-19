<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Сирі MQTT-повідомлення — лог для відлагодження та переопрацювання.
     */
    public function up(): void
    {
        Schema::create('mqtt_messages', function (Blueprint $table) {
            $table->id();
            $table->string('topic');           // MQTT топік (напр: "aquapark")
            $table->string('device_id')->nullable(); // ID пристрою з payload
            $table->text('payload');           // повне повідомлення
            $table->boolean('processed')->default(false); // чи опрацьовано
            $table->string('error')->nullable();          // помилка якщо не вдалось опрацювати
            $table->timestamp('received_at'); // час приходу (може відрізнятись від created_at)
            $table->timestamps();

            $table->index(['topic', 'processed']);
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mqtt_messages');
    }
};
