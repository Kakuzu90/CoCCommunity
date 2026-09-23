<?php

declare(strict_types=1);

use App\Modules\PlayerAccounts\Enums\AccountState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coc_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('tag')->unique();
            $table->string('ign')->nullable();
            $table->enum('state', ['unverified', 'verified', 'disputed', 'suspended', 'needs_reverify'])->default(AccountState::Unverified->value);
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('last_synced_at')->nullable();
            $table->index(['user_id', 'state']);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('coc_account_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coc_account_id')->constrained('coc_accounts')->restrictOnDelete();
            $table->foreignId('claimant_user_id')->constrained('users')->restrictOnDelete();
            $table->string('method');
            $table->string('status');
            $table->jsonb('evidence')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->index(['coc_account_id', 'status']);
            $table->timestamps();
        });
        Schema::create('coc_account_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coc_account_id')->constrained('coc_accounts')->cascadeOnDelete();
            $table->unsignedSmallInteger('th_level');
            $table->unsignedInteger('trophies');
            $table->unsignedInteger('war_stars');
            $table->string('league')->nullable();
            $table->jsonb('data');
            $table->timestampTz('fetched_at');
            $table->index(['coc_account_id', new Expression('fetched_at DESC')], 'coc_snapshots_account_fetched_index');
            $table->timestamps();
        });
        Schema::create('coc_account_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coc_account_id')->constrained('coc_accounts')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unique(['coc_account_id', 'media_id']);
            $table->unique(['coc_account_id', 'position']);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('coc_account_media');
        Schema::dropIfExists('coc_account_snapshots');
        Schema::dropIfExists('coc_account_claims');
        Schema::dropIfExists('coc_accounts');
    }
};
