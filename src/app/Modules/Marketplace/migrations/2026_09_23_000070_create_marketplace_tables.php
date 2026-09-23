<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('category');
            $table->string('title');
            $table->unsignedBigInteger('price');
            $table->string('status');
            $table->index(['category', 'status']);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('marketplace_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('status');
            $table->unsignedBigInteger('amount');
            $table->string('escrow_state')->nullable();
            $table->index('buyer_id');
            $table->index(['seller_id', 'status']);
            $table->timestamps();
        });
        Schema::create('marketplace_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('marketplace_orders')->restrictOnDelete();
            $table->foreignId('rater_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('rating');
            $table->text('body')->nullable();
            $table->unique(['order_id', 'rater_id']);
            $table->softDeletes();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_reviews');
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_listings');
    }
};
