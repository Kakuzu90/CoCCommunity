<?php

namespace App\Domain\PlayerAccounts;

use App\Domain\PlayerAccounts\Events\CocAccountVerified;
use App\Domain\PlayerAccounts\Listeners\InitializeAccountSync;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class PlayerAccountsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(CocAccountVerified::class, InitializeAccountSync::class);
    }
}
