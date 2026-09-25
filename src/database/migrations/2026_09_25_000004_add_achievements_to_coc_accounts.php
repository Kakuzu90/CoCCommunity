<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The displayed subset of player achievements (specs/07 coc_accounts). Stored as jsonb like the other
 * progression lists so a game update adds entries without a schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->jsonb('achievements')->default('[]')->after('labels');
        });
    }

    public function down(): void
    {
        Schema::table('coc_accounts', function (Blueprint $table) {
            $table->dropColumn('achievements');
        });
    }
};
