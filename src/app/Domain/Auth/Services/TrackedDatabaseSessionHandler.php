<?php

namespace App\Domain\Auth\Services;

use Illuminate\Session\DatabaseSessionHandler;

/** Adds safe display metadata without changing Laravel's database session semantics. */
class TrackedDatabaseSessionHandler extends DatabaseSessionHandler
{
    /** @return array<string, mixed> */
    protected function getDefaultPayload($data)
    {
        $payload = parent::getDefaultPayload($data);
        $payload['device_label'] = $this->deviceLabel($this->userAgent());

        return $payload;
    }

    protected function performInsert($sessionId, $payload)
    {
        $payload['created_at'] = now();

        return parent::performInsert($sessionId, $payload);
    }

    private function deviceLabel(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };
        $device = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Macintosh') => 'Mac',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Unknown device',
        };

        return $browser.' on '.$device;
    }
}
