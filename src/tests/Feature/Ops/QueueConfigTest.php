<?php

use App\Domain\Media\Jobs\ProcessMediaJob;

/*
 * specs/20 §5: retry_after MUST exceed the longest job timeout on a database queue, or a long
 * media re-encode is re-reserved and runs twice. This guards the config against a silent regression.
 */
it('leases media jobs longer than the media job can run', function () {
    $lease = (int) config('queue.connections.database-media.retry_after');
    $timeout = (new ProcessMediaJob(1))->timeout;

    expect($lease)->toBeGreaterThan($timeout)
        ->and($lease)->toBeGreaterThan((int) config('queue.connections.database.retry_after'));
});

it('routes the media connection at the media queue', function () {
    expect(config('queue.connections.database-media.queue'))->toBe('media');
});
