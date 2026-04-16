<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('position')->default('owner');
            $table->unique(['company_id', 'user_id']);
            $table->timestamps();
        });

        // Migrate existing company->user_id into the pivot
        DB::table('companies')
            ->whereNotNull('user_id')
            ->get()
            ->each(function ($company) {
                DB::table('company_user')->insertOrIgnore([
                    'company_id' => $company->id,
                    'user_id'    => $company->user_id,
                    'position'   => 'owner',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
