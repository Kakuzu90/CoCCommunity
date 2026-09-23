<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unique('user_id');
            $table->string('username')->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->foreignId('featured_account_id')->nullable()->constrained('coc_accounts')->nullOnDelete();
            $table->jsonb('privacy');
            $table->softDeletes();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
