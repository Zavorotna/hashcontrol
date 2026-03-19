<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Пристрої (рідери, генератор, холодильник, охорона тощо).
     *
     * type — що робить пристрій з точки зору MQTT-події:
     *   reader_open   → фіксує початок (відкриття магазину)
     *   reader_close  → фіксує кінець (закриття магазину)
     *   generator_on  → фіксує увімкнення генератора
     *   generator_off → фіксує вимкнення генератора
     *   generic_on    → універсальний старт (холодильник, охорона тощо)
     *   generic_off   → універсальний стоп
     *
     * session_type — який тип сесії створює цей пристрій:
     *   store_open | generator | refrigerator | shift | security | generic
     *
     * store_id = null для пристроїв прив'язаних до топіку (генератор тощо)
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();

            $table->string('mqtt_device_id')->unique(); // ID з MQTT-повідомлення, напр. "GEN_AQ_01"
            $table->string('name')->nullable();         // "Рідер вхід", "Генератор вмк"
            $table->boolean('is_active')->default(true);

            // ─── Тип події ────────────────────────────────────────────
            // Змінено з enum на string — підтримує нові типи без міграції
            $table->string('type');
            // reader_open | reader_close | generator_on | generator_off | generic_on | generic_off

            // ─── Тип сесії яку створює цей пристрій ──────────────────
            $table->string('session_type')->default('generic');
            // store_open | generator | refrigerator | shift | security | generic

            // ─── Метрика ──────────────────────────────────────────────
            // Копіюється в Session при створенні — щоб зберегти знімок на момент події
            $table->decimal('metric_rate_per_hour', 8, 2)->nullable();
            // Скільки одиниць витрачається за годину (літри пального, кВт·год тощо)

            $table->string('metric_unit')->nullable();
            // Одиниця вимірювання: 'л', 'кВт·год', '°C', 'год'

            $table->string('metric_label')->nullable();
            // Назва для UI: 'Витрата пального', 'Споживання енергії'

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};