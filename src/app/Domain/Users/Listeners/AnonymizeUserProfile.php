<?php

namespace App\Domain\Users\Listeners;

use App\Domain\Auth\Events\AccountAnonymized;
use App\Domain\Users\Services\ProfileService;
use App\Support\Enums\QueueName;
use Illuminate\Contracts\Queue\ShouldQueue;

class AnonymizeUserProfile implements ShouldQueue
{
    public string $queue = QueueName::Low->value;

    public function __construct(private readonly ProfileService $profiles) {}

    public function handle(AccountAnonymized $event): void
    {
        $this->profiles->anonymize($event->userId);
    }
}
