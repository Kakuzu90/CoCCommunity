<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('disk')->default('r2');
            $table->string('path')->unique();
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64)->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('uploader_id')->constrained('users')->restrictOnDelete();
            $table->index('uploader_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
