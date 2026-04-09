<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ця таблиця зберігає об'єкти які власник ТЦ реєструє вручну.
        // external_id = значення поля "data" з MQTT JSON (напр. "SHOP_42")
        // type розрізняє магазин / генератор / холодильник / лічильник
        Schema::create('tracked_objects', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');              // ID з MQTT data-поля
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');                     // Людська назва: "Магазин Пума"
            $table->enum('type', ['shop', 'generator', 'fridge', 'counter', 'other'])->default('shop');
            $table->string('tenant_name')->nullable();  // Орендар (тільки для магазинів)
            $table->timestamps();

            // Один external_id унікальний в межах компанії
            $table->unique(['external_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_objects');
    }
};