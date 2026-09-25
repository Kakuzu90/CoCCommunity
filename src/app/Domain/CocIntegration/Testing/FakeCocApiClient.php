<?php

namespace App\Domain\CocIntegration\Testing;

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Data\PlayerData;
use App\Domain\CocIntegration\Data\TokenVerificationResult;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Support\ValueObjects\PlayerTag;
use Carbon\CarbonImmutable;

/**
 * The in-memory client (specs/09 §10, CLAUDE.md local-first). It makes no network call: it returns
 * scripted DTOs and injectable failures for tests, and a plausible default player for local dev so the
 * app runs with no key. Bound whenever `coc.driver` is `fake`.
 */
final class FakeCocApiClient implements CocApiClient
{
    /** @var array<string, PlayerData> */
    private array $players = [];

    /** @var array<string, CocApiException> */
    private array $failures = [];

    private bool $verifyOutcome = true;

    /** @var list<array{tag: string, token: string}> */
    public array $verifyCalls = [];

    public function player(PlayerTag $tag, CocRequestPriority $priority = CocRequestPriority::Interactive): PlayerData
    {
        $id = $this->id($tag);

        if (isset($this->failures[$id])) {
            throw $this->failures[$id];
        }

        return $this->players[$id] ?? $this->defaultPlayer($tag);
    }

    public function verifyToken(PlayerTag $tag, string $token): TokenVerificationResult
    {
        $id = $this->id($tag);
        $this->verifyCalls[] = ['tag' => $tag->value, 'token' => $token];

        if (isset($this->failures[$id])) {
            throw $this->failures[$id];
        }

        return $this->verifyOutcome
            ? TokenVerificationResult::ok($tag->value)
            : TokenVerificationResult::invalid($tag->value);
    }

    public function stub(PlayerData $player): self
    {
        $this->players[ltrim($player->tag, '#')] = $player;

        return $this;
    }

    /** @param array<string, mixed> $raw */
    public function stubArray(string $tag, array $raw): self
    {
        return $this->stub(PlayerData::fromArray(['tag' => $tag, ...$raw], CarbonImmutable::now()));
    }

    public function throwFor(string $tag, CocApiException $exception): self
    {
        $this->failures[ltrim(strtoupper($tag), '#')] = $exception;

        return $this;
    }

    public function verifyReturns(bool $ok): self
    {
        $this->verifyOutcome = $ok;

        return $this;
    }

    private function defaultPlayer(PlayerTag $tag): PlayerData
    {
        return new PlayerData(
            tag: $tag->value,
            name: 'Chief '.substr(ltrim($tag->value, '#'), 0, 4),
            townHallLevel: 15,
            expLevel: 200,
            trophies: 4200,
            bestTrophies: 5100,
            warStars: 1500,
            attackWins: 120,
            defenseWins: 30,
            donations: 800,
            donationsReceived: 750,
            fetchedAt: CarbonImmutable::now(),
        );
    }

    private function id(PlayerTag $tag): string
    {
        return ltrim($tag->value, '#');
    }
}
