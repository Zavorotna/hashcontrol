<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracked_objects', function (Blueprint $table) {
            $table->string('email')->nullable()->after('tenant_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('address')->nullable()->after('phone');
            $table->text('notes')->nullable()->after('address');
        });

        // Add 'worker' and 'thermometer' to type enum
        DB::statement("ALTER TABLE tracked_objects MODIFY COLUMN type ENUM('shop','generator','fridge','counter','worker','thermometer','other') DEFAULT 'shop'");
    }

    public function down(): void
    {
        Schema::table('tracked_objects', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'address', 'notes']);
        });

        DB::statement("ALTER TABLE tracked_objects MODIFY COLUMN type ENUM('shop','generator','fridge','counter','other') DEFAULT 'shop'");
    }
};
