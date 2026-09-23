<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (glob(app_path('Modules/*'), GLOB_ONLYDIR) ?: [] as $module) {
            if (is_dir($module.'/migrations')) {
                $this->loadMigrationsFrom($module.'/migrations');
            }

            if (! $this->app->routesAreCached() && is_file($module.'/routes.php')) {
                Route::middleware('web')->group($module.'/routes.php');
            }
        }
    }
}
