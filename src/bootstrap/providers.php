<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\VoltServiceProvider;

return [
    AppServiceProvider::class,
    VoltServiceProvider::class,
    ModuleServiceProvider::class,
];
