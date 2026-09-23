<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('kind')->default('image')->after('path');
            // Derivatives (thumbnails) point at their original; cascade cleans
            // children if the original row is force-deleted.
            $table->foreignId('parent_id')->nullable()->after('id')
                ->constrained('media')->cascadeOnDelete();
            $table->string('variant')->default('original')->after('kind');
            $table->unsignedSmallInteger('width')->nullable()->after('size');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
            $table->index(['parent_id', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['kind', 'variant', 'width', 'height']);
        });
    }
};
