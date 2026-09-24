<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('device_label', 120)->nullable();
            $table->timestampTz('created_at')->nullable();
        });

        // Existing sessions have no creation timestamp. Treat their last activity as their start.
        DB::table('sessions')->whereNull('created_at')->update(['created_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['device_label', 'created_at']);
        });
    }
};
