<?php

namespace App\Domain\CocIntegration\Data;

/**
 * The clan a player belongs to, as seen from the player payload (specs/09 §8). `badgeUrl` is
 * referenced verbatim and rendered unmodified — clan badges are never mirrored (specs/10 §11.1).
 */
final readonly class PlayerClanRef
{
    public function __construct(
        public string $tag,
        public string $name,
        public ?string $role = null,
        public ?string $badgeUrl = null,
    ) {}

    /** @param array<string, mixed>|null $raw */
    public static function fromArray(?array $raw): ?self
    {
        if ($raw === null || ! isset($raw['tag'])) {
            return null;
        }

        $badges = is_array($raw['badgeUrls'] ?? null) ? $raw['badgeUrls'] : [];

        return new self(
            tag: (string) $raw['tag'],
            name: (string) ($raw['name'] ?? ''),
            role: isset($raw['role']) ? (string) $raw['role'] : null,
            badgeUrl: isset($badges['medium']) ? (string) $badges['medium'] : (isset($badges['small']) ? (string) $badges['small'] : null),
        );
    }
}
