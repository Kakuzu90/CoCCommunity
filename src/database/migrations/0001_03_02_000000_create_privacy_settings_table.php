<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('profile_visibility', 16)->default('public');
            $table->boolean('show_coc_accounts')->default(true);
            $table->boolean('show_clan')->default(true);
            $table->boolean('show_activity')->default(true);
            $table->boolean('allow_recruitment_contact')->default(false);
            $table->boolean('allow_marketplace_contact')->default(false);
            $table->boolean('searchable')->default(true);
        });

        DB::table('privacy_settings')->insertUsing(['user_id'], DB::table('users')->select('id'));
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_settings');
    }
};
