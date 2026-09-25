<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->jsonb('data');
            $table->timestampTz('read_at')->nullable();
            $table->string('group_key', 100)->nullable();
            $table->timestampsTz();
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_recent');
            $table->index(['group_key', 'notifiable_id']);
        });
        // Both supported databases support partial indexes for the unread bell query.
        DB::statement('CREATE INDEX notifications_unread ON notifications (notifiable_id) WHERE read_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
