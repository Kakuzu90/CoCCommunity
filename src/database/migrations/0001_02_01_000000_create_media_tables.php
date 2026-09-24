<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Media pipeline schema — specs/07-database-schema.md (Media) and specs/10-media-storage.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('ulid', 26)->unique();           // used in signed/public URLs
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('attachable_type', 60)->nullable();
            $table->unsignedBigInteger('attachable_id')->nullable();
            $table->string('collection', 30);
            $table->string('kind', 10);
            $table->string('disk', 20);
            $table->text('path');
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size_bytes');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->decimal('duration_seconds', 6, 2)->nullable();
            $table->char('checksum_sha256', 64)->nullable();
            $table->string('status', 20);
            $table->string('failure_reason', 100)->nullable();
            $table->string('visibility', 10);
            $table->smallInteger('position')->default(0);
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('expires_at')->nullable();   // drives the orphan sweeper
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['disk', 'path']);
            $table->index(['attachable_type', 'attachable_id', 'collection', 'position'], 'media_attachable_idx');
            $table->index(['user_id', 'created_at'], 'media_user_created_idx');
            $table->index('checksum_sha256', 'media_checksum_idx');
        });

        // Partial index the hourly sweeper uses: unattached rows carrying an expiry (specs/10 §9).
        DB::statement('CREATE INDEX media_sweeper_idx ON media (expires_at) WHERE attachable_id IS NULL AND expires_at IS NOT NULL');

        // A `ready` file is either attached (permanent, expiry cleared) or a draft still within its
        // expiry window (the sweeper reaps it). Reconciles the spec-07 CHECK with edge-case §4, where
        // an uploaded-but-never-published screenshot is ready yet must expire and be swept.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE media ADD CONSTRAINT media_ready_attached_chk CHECK (status <> 'ready' OR attachable_id IS NOT NULL OR expires_at IS NOT NULL)");
        }

        Schema::create('media_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('variant', 20);
            $table->text('path');
            $table->integer('width');
            $table->integer('height');
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type', 100);
            $table->timestampTz('created_at')->nullable();

            $table->unique(['media_id', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_variants');
        Schema::dropIfExists('media');
    }
};
