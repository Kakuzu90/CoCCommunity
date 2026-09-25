<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contested tags routed to admins (specs/07 `coc_account_disputes`, specs/13 §5). A dispute exists for
 * the one honest case where the real owner cannot produce a token. At most one live dispute per
 * challenger per tag — a partial unique index enforces it. The account FK is `restrict`: a disputed
 * account cannot be hard-deleted out from under an open dispute (specs/08 §2).
 */
return new class extends Migration
{
    public function up(): void
    {
        $pg = DB::getDriverName() === 'pgsql';

        Schema::create('coc_account_disputes', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('coc_account_id')->constrained('coc_accounts')->restrictOnDelete();
            $table->string('tag_normalized', 14);
            $table->foreignId('claimant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_holder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->jsonb('evidence')->default('[]');
            $table->string('status', 20)->default('open');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamp('holder_responds_by')->nullable(); // 7-day window (specs/13 §5 step 2)
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('last_claimant_activity_at')->nullable(); // 30-day auto-withdraw clock
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('assigned_admin_id');
            $table->index('coc_account_id');
        });

        // One live dispute per challenger per tag (specs/07): the partial index lets a claimant refile
        // after a previous dispute resolves, while blocking two live at once. Both drivers support it.
        $live = "'open','awaiting_claimant','awaiting_holder'";
        DB::statement("CREATE UNIQUE INDEX coc_disputes_live_idx ON coc_account_disputes (tag_normalized, claimant_id) WHERE status IN ({$live})");
    }

    public function down(): void
    {
        Schema::dropIfExists('coc_account_disputes');
    }
};
