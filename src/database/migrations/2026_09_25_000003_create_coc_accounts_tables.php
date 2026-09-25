<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The central trust object: a CoC tag attached to a user, plus the forensic claim log (specs/07, 13).
 * A tag has at most one verified owner platform-wide — a partial unique index enforces it while
 * unverified claims coexist. Disputes and snapshots are separate tables owned by later Phase 2 tasks;
 * the `clan_id` column carries no FK yet because the `clans` table lands with the clan work.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pg = DB::getDriverName() === 'pgsql';

        Schema::create('coc_accounts', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('previous_user_id')->nullable(); // supersede audit trail (specs/13 §3.1)
            $table->string('tag', 15);
            $table->string('tag_normalized', 14);
            $table->string('status', 20)->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_method', 20)->nullable();

            $table->string('ign', 30);
            $table->smallInteger('th_level');
            $table->smallInteger('builder_hall_level')->nullable();
            $table->smallInteger('xp_level')->default(0);
            $table->integer('trophies')->default(0);
            $table->integer('best_trophies')->default(0);
            $table->integer('builder_trophies')->default(0);
            $table->integer('war_stars')->default(0);
            $table->integer('attack_wins')->default(0);
            $table->integer('defense_wins')->default(0);
            $table->integer('donations')->default(0);
            $table->integer('donations_received')->default(0);

            $table->string('clan_tag', 15)->nullable();
            $table->unsignedBigInteger('clan_id')->nullable(); // FK added with the clans table (clan task)
            $table->string('clan_role', 20)->nullable();
            $table->integer('league_id')->nullable();
            $table->string('league_name', 50)->nullable();
            $table->text('league_icon_url')->nullable();

            $table->jsonb('troops')->default('[]');
            $table->jsonb('heroes')->default('[]');
            $table->jsonb('spells')->default('[]');
            $table->jsonb('hero_equipment')->default('[]');
            $table->jsonb('labels')->default('[]');
            $table->jsonb('raw_payload')->nullable();

            $table->timestamp('api_synced_at')->nullable();
            $table->smallInteger('api_sync_failures')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->smallInteger('images_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'tag_normalized']); // a user cannot attach the same tag twice
            $table->index(['user_id', 'status']);
            $table->index('tag_normalized');
            $table->index('clan_id');
        });

        // Only one verified owner per tag globally, and one featured account per user — partial uniques
        // that let unverified duplicates and unfeatured accounts coexist. Both drivers support them.
        DB::statement("CREATE UNIQUE INDEX coc_accounts_verified_tag_idx ON coc_accounts (tag_normalized) WHERE status = 'verified'");
        DB::statement('CREATE UNIQUE INDEX coc_accounts_featured_idx ON coc_accounts (user_id) WHERE is_featured');

        if ($pg) {
            DB::statement('ALTER TABLE coc_accounts ADD CONSTRAINT coc_accounts_th_level_check CHECK (th_level BETWEEN 1 AND 30)');
            DB::statement('ALTER TABLE coc_accounts ADD CONSTRAINT coc_accounts_images_count_check CHECK (images_count <= 5)');
        }

        Schema::create('coc_account_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coc_account_id')->nullable()->constrained('coc_accounts')->nullOnDelete();
            $table->string('tag_normalized', 14);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('method', 20);
            $table->string('status', 20);
            $table->string('failure_reason', 100)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tag_normalized', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coc_account_claims');
        Schema::dropIfExists('coc_accounts');
    }
};
