<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Public profile presentation and denormalised stats (specs/07 Users & profiles). Split 1:1 from
 * `users` so profile writes never touch the hot auth row. Every existing user is backfilled a
 * profile + stats row so the 1:1 invariant holds from the first request.
 *
 * Portability: `languages` and `socials` are stored as JSON (not a Postgres array) so the schema is
 * identical on SQLite CI; the `search_vector` tsvector and its GIN index are deferred to the Search
 * v1 task, which owns full-text search (specs/07 divergence noted in specs/07 and specs/25).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 50)->nullable();
            $table->string('bio', 500)->nullable();
            $table->unsignedBigInteger('avatar_media_id')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->json('languages')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->json('socials')->nullable();
            $table->timestampsTz();

            // ON DELETE SET NULL: deleting a media row must not delete the profile (specs/07).
            $table->foreign('avatar_media_id')->references('id')->on('media')->nullOnDelete();
            $table->index('country_code');
        });

        Schema::create('user_stats', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('bases_published')->default(0);
            $table->unsignedInteger('total_base_likes')->default(0);
            $table->unsignedInteger('total_base_copies')->default(0);
            $table->unsignedInteger('total_base_views')->default(0);
            $table->unsignedInteger('comments_posted')->default(0);
            $table->timestampTz('recomputed_at')->nullable();
        });

        // Backfill the 1:1 rows for any users that already exist (registration creates them going
        // forward). Insert-select is portable across SQLite and Postgres.
        DB::statement('INSERT INTO profiles (user_id, created_at, updated_at) SELECT id, '.$this->now().', '.$this->now().' FROM users');
        DB::statement('INSERT INTO user_stats (user_id) SELECT id FROM users');
    }

    private function now(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'now()' : "datetime('now')";
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats');
        Schema::dropIfExists('profiles');
    }
};
