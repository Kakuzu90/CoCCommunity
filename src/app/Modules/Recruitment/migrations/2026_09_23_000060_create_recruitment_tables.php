<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('clan_id')->nullable()->constrained('clans')->restrictOnDelete();
            $table->unsignedSmallInteger('th_req')->nullable();
            $table->unsignedInteger('trophies_req')->nullable();
            $table->string('language')->nullable();
            $table->string('location')->nullable();
            $table->string('status');
            $table->text('body');
            $table->index(['type', 'status', 'language', 'location'], 'recruitment_discovery_index');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('recruitment_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recruitment_post_id')->constrained('recruitment_posts')->cascadeOnDelete();
            $table->foreignId('applicant_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('coc_account_id')->constrained('coc_accounts')->restrictOnDelete();
            $table->string('status');
            $table->text('message')->nullable();
            $table->unique(['recruitment_post_id', 'applicant_user_id'], 'recruitment_application_applicant_unique');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_applications');
        Schema::dropIfExists('recruitment_posts');
    }
};
