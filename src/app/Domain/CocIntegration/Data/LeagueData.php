<?php

namespace App\Domain\CocIntegration\Data;

/**
 * A player's league (specs/09 §8). `iconUrls` is stored so the GameAssetResolver can prefer our
 * self-hosted copy and fall back to the API URL — never downloaded or re-encoded (specs/09 §8, 10 §11).
 */
final readonly class LeagueData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $iconUrl = null,
    ) {}

    /** @param array<string, mixed>|null $raw */
    public static function fromArray(?array $raw): ?self
    {
        if ($raw === null || ! isset($raw['id'])) {
            return null;
        }

        $icons = is_array($raw['iconUrls'] ?? null) ? $raw['iconUrls'] : [];

        return new self(
            id: (int) $raw['id'],
            name: (string) ($raw['name'] ?? ''),
            iconUrl: isset($icons['medium']) ? (string) $icons['medium'] : (isset($icons['small']) ? (string) $icons['small'] : null),
        );
    }
}
