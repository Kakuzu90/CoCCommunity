<?php

namespace App\Console\Commands;

use App\Domain\Notifications\Services\PruneNotifications as Pruner;
use Illuminate\Console\Command;

final class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Prune expired notifications and enforce per-user retention limits';

    public function handle(Pruner $pruner): int
    {
        $this->info('Pruned '.$pruner->run().' notifications.');

        return self::SUCCESS;
    }
}
