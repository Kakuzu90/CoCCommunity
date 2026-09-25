<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_layouts', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('slug', 90)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coc_account_id')->nullable()->constrained('coc_accounts')->nullOnDelete();
            $table->string('title', 80);
            $table->text('description')->nullable();
            $table->smallInteger('th_level');
            $table->string('category', 20);
            $table->text('base_link');
            $table->string('layout_hash', 64);
            $table->string('visibility', 10)->default('public');
            $table->string('status', 20)->default('processing');
            $table->boolean('has_video')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('moderation_state', 20)->default('clean');
            $table->string('flagged_reason', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility', 'published_at']);
            $table->index(['th_level', 'category', 'published_at']);
            $table->index(['user_id', 'published_at']);
            $table->index('layout_hash');
            $table->index('coc_account_id');
        });

        DB::statement('CREATE UNIQUE INDEX base_layouts_user_hash_active_unique ON base_layouts (user_id, layout_hash) WHERE deleted_at IS NULL');
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE base_layouts ADD CONSTRAINT base_layouts_category_check CHECK (category IN ('war', 'cwl', 'farming', 'trophy', 'legend', 'anti_3_star', 'anti_2_star', 'hybrid', 'progress', 'troll'))");
            DB::statement("ALTER TABLE base_layouts ADD CONSTRAINT base_layouts_visibility_check CHECK (visibility IN ('public', 'unlisted', 'private'))");
            DB::statement("ALTER TABLE base_layouts ADD CONSTRAINT base_layouts_status_check CHECK (status IN ('draft', 'processing', 'published', 'hidden', 'removed'))");
            DB::statement("ALTER TABLE base_layouts ADD CONSTRAINT base_layouts_moderation_state_check CHECK (moderation_state IN ('clean', 'flagged', 'under_review', 'actioned'))");
            DB::statement('ALTER TABLE base_layouts ADD CONSTRAINT base_layouts_th_level_check CHECK (th_level BETWEEN 2 AND 30)');
        }

        Schema::create('base_metrics', function (Blueprint $table): void {
            $table->foreignId('base_layout_id')->primary()->constrained('base_layouts')->cascadeOnDelete();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('copies_count')->default(0);
            $table->unsignedInteger('reports_count')->default(0);
            $table->float('trending_score')->default(0);
            $table->timestamp('score_updated_at')->nullable();

            $table->index('trending_score');
            $table->index('likes_count');
            $table->index('copies_count');
        });

        Schema::create('base_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 24);
            $table->string('slug', 24)->unique();
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('is_suggested')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_blocked', 'usage_count']);
        });

        Schema::create('base_layout_tag', function (Blueprint $table): void {
            $table->foreignId('base_layout_id')->constrained('base_layouts')->cascadeOnDelete();
            $table->foreignId('base_tag_id')->constrained('base_tags')->cascadeOnDelete();
            $table->primary(['base_layout_id', 'base_tag_id']);
            $table->index(['base_tag_id', 'base_layout_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_layout_tag');
        Schema::dropIfExists('base_tags');
        Schema::dropIfExists('base_metrics');
        Schema::dropIfExists('base_layouts');
    }
};
