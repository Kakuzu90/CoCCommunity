<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Observability log for outbound CoC API calls (specs/09 §4): endpoint, status, duration and whether the
 * response was served from cache. It stores the non-reversible key id, never the token, and never a
 * request body. Pruned at 7 days by coc:prune-api-requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coc_api_requests', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');            // logical label: players, verifytoken
            $table->string('method', 8);
            $table->unsignedSmallInteger('status')->nullable(); // null = no call made (circuit open)
            $table->unsignedInteger('duration_ms')->default(0);
            $table->boolean('cached')->default(false);
            $table->string('key_id', 16)->nullable();
            $table->string('reason', 32)->nullable(); // CocErrorReason value on failure
            $table->timestamp('created_at')->nullable();

            $table->index('created_at');            // pruning by age
            $table->index(['endpoint', 'created_at']); // per-endpoint rate/latency reporting
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coc_api_requests');
    }
};
