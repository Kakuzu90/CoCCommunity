<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clans', function (Blueprint $table): void {
            $table->id();
            $table->string('tag')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('level');
            $table->jsonb('data');
            $table->timestampTz('last_synced_at')->nullable();
            $table->timestamps();
        });
        Schema::create('clan_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coc_account_id')->constrained('coc_accounts')->cascadeOnDelete();
            $table->foreignId('clan_id')->constrained('clans')->cascadeOnDelete();
            $table->string('role');
            $table->timestampTz('joined_at')->nullable();
            $table->index('clan_id');
            $table->index('coc_account_id');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('clan_memberships');
        Schema::dropIfExists('clans');
    }
};
