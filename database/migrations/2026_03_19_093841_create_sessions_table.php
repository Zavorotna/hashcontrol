<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Назва 'sessions' зайнята Laravel для авторизації — використовуємо 'work_sessions'

    public function up(): void
    {
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->id();

            // Поліморфний зв'язок — Store або Topic
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->index(['subject_type', 'subject_id']);

            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();

            // Тип сесії: store_open | generator | refrigerator | shift | security | generic
            $table->string('type');

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            // Метрика — копіюється з device при створенні
            $table->decimal('metric_rate_per_hour', 8, 2)->nullable();
            $table->decimal('metric_consumed', 10, 4)->nullable(); // рахується при закритті
            $table->string('metric_unit')->nullable();   // 'л', 'кВт·год'
            $table->string('metric_label')->nullable();  // 'Витрата пального'

            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
    }
};