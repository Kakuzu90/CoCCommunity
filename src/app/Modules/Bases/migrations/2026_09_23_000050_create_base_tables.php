<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_layouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('coc_account_id')->constrained('coc_accounts')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('th_level');
            $table->string('category');
            $table->text('base_link');
            $table->string('visibility');
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('copy_click_count')->default(0);
            $table->index(['th_level', 'category', 'visibility']);
            $table->index('user_id');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('base_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_layout_id')->constrained('base_layouts')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->string('kind');
            $table->unsignedSmallInteger('position');
            $table->index('base_layout_id');
            $table->unique(['base_layout_id', 'media_id']);
            $table->unique(['base_layout_id', 'position']);
            $table->timestamps();
        });
        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('base_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_layout_id')->constrained('base_layouts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->unique(['base_layout_id', 'tag_id']);
            $table->timestamps();
        });
        Schema::create('base_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_layout_id')->constrained('base_layouts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->foreignId('parent_id')->nullable()->constrained('base_comments')->nullOnDelete();
            $table->string('status');
            $table->index(['base_layout_id', 'status']);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('base_likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_layout_id')->constrained('base_layouts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['base_layout_id', 'user_id']);
            $table->timestamps();
        });
        Schema::create('base_bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_layout_id')->constrained('base_layouts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['base_layout_id', 'user_id']);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('base_bookmarks');
        Schema::dropIfExists('base_likes');
        Schema::dropIfExists('base_comments');
        Schema::dropIfExists('base_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('base_media');
        Schema::dropIfExists('base_layouts');
    }
};
