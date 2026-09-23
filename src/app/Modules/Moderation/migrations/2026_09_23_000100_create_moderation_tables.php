<?php

declare(strict_types=1);

use App\Modules\Moderation\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->morphs('reportable');
            $table->string('reason');
            $table->jsonb('evidence')->nullable();
            $table->enum('status', ['open', 'assigned', 'resolved', 'dismissed'])->default(ReportStatus::Open->value)->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('moderation_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('moderator_id')->constrained('users')->restrictOnDelete();
            $table->morphs('target');
            $table->string('action');
            $table->text('reason');
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->morphs('subject');
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->index(['actor_id', 'created_at']);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('moderation_actions');
        Schema::dropIfExists('reports');
    }
};
