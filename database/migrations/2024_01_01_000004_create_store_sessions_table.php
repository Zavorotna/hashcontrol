<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Сесії роботи магазину (відкриття → закриття).
     */
    public function up(): void
    {
        Schema::create('store_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable(); // null = магазин досі відкритий
            $table->unsignedBigInteger('duration_seconds')->nullable(); // обраховується при закритті
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'opened_at']);
            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_sessions');
    }
};
