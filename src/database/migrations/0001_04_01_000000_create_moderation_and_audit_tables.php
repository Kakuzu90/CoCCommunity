<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moderation + audit tables (specs/07 `moderation_actions`, `user_sanctions`, `audit_logs`;
 * specs/12 §5–§6). Admin v1 writes all three when a sanction is applied: the moderation action is
 * the immutable "what a moderator did" record, the sanction is the queryable active/historical state
 * that status checks and the expiry job read, and the audit log is the compliance/forensics trail
 * with a different audience and retention (specs/07). All three are append-only in spirit; only the
 * sanction carries a lifted_by/lifted_at mutation.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pg = DB::getDriverName() === 'pgsql';

        Schema::create('moderation_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->nullable(); // report_cases arrives with the Phase 3 moderation task.
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 30);
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason_code', 40);
            $table->text('note');
            $table->unsignedInteger('duration_hours')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['target_user_id', 'created_at']);
        });

        Schema::create('user_sanctions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('reason_code', 40);
            $table->string('public_reason', 255);
            $table->text('internal_note')->nullable();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('lifted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lifted_at')->nullable();
            $table->foreignId('moderation_action_id')->constrained('moderation_actions')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 20)->nullable();
            $table->string('action', 60);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->jsonb('context')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 128)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        if ($pg) {
            // Only unlifted, expiring sanctions matter to the expiry job (specs/07 partial index).
            DB::statement('CREATE INDEX user_sanctions_active_idx ON user_sanctions (expires_at) WHERE expires_at IS NOT NULL AND lifted_at IS NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sanctions');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('moderation_actions');
    }
};
