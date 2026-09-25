<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coc_account_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coc_account_id')->constrained('coc_accounts')->cascadeOnDelete();
            $table->timestamp('captured_at', 6);
            $table->smallInteger('th_level');
            $table->smallInteger('xp_level');
            $table->integer('trophies');
            $table->integer('best_trophies');
            $table->integer('war_stars');
            $table->integer('attack_wins');
            $table->integer('defense_wins');
            $table->integer('donations');
            $table->string('clan_tag', 15)->nullable();
            $table->integer('league_id')->nullable();
            $table->jsonb('heroes')->default('[]');
            $table->jsonb('troops')->default('[]');
            $table->jsonb('spells')->default('[]');
            $table->jsonb('hero_equipment')->default('[]');
            $table->string('source', 20);
            $table->unique(['coc_account_id', 'captured_at']);
            $table->index(['coc_account_id', 'captured_at']);
        });

        Schema::create('sync_states', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 20);
            $table->unsignedBigInteger('resource_id');
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->unsignedSmallInteger('not_found_failures')->default(0);
            $table->timestamp('next_due_at')->nullable();
            $table->string('tier', 20);
            $table->boolean('stale')->default(false);
            $table->boolean('flagged')->default(false);
            $table->unique(['resource_type', 'resource_id']);
            $table->index('next_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_states');
        Schema::dropIfExists('coc_account_snapshots');
    }
};
