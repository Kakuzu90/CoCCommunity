<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\PlayerAccounts\Models\CocAccount;
use App\Modules\PlayerAccounts\Policies\CocAccountPolicy;
use App\Modules\Users\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(CocAccount::class, CocAccountPolicy::class);
        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)
            ->middleware(['web', 'throttle:60,1']));
    }
}
