<?php

namespace App\Support\Enums;

// Queue topology from spec 20 §1. Retry and worker policy belong to the Ops task.
enum QueueName: string
{
    case High = 'high';
    case Default = 'default';
    case Media = 'media';
    case Sync = 'sync';
    case Low = 'low';
}
