<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Сесії роботи генератора (увімкнення → вимкнення).
     * Прив'язані до топіку — один генератор на весь топік.
     */
    public function up(): void
    {
        Schema::create('generator_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable(); // null = генератор досі працює
            $table->unsignedBigInteger('duration_seconds')->nullable();
            $table->decimal('fuel_consumed_total', 10, 2)->nullable(); // загальний літраж за цю сесію
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['topic_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generator_sessions');
    }
};
