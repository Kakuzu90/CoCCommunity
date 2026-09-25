<?php

namespace App\Providers;

use App\Domain\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        // Stable morph alias so moderation/audit rows reference a user by 'user', not a class name
        // that a refactor could move. Modules record polymorphic targets by this alias, never by
        // importing the User model across a boundary (specs/19 module rules).
        Relation::morphMap(['user' => User::class]);
    }
}
