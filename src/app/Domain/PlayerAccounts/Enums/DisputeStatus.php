<?php

namespace App\Domain\PlayerAccounts\Enums;

/**
 * Lifecycle of an ownership dispute (specs/13 §5). A dispute is "live" while open, awaiting the
 * claimant, or awaiting the holder — that is when it holds the tag in `disputed` and blocks a second
 * dispute on the same tag. Every other value is terminal.
 */
enum DisputeStatus: string
{
    case Open = 'open';
    case AwaitingClaimant = 'awaiting_claimant';
    case AwaitingHolder = 'awaiting_holder';
    case ResolvedTransfer = 'resolved_transfer';
    case ResolvedDenied = 'resolved_denied';
    case Withdrawn = 'withdrawn';
    case AutoResolved = 'auto_resolved';

    /** Live states: the tag is held in `disputed` and no new dispute may be filed against it. */
    public function isLive(): bool
    {
        return in_array($this, [self::Open, self::AwaitingClaimant, self::AwaitingHolder], true);
    }

    public function isResolved(): bool
    {
        return ! $this->isLive();
    }

    /**
     * The live states, as their string values — for the partial-unique guard and queries.
     *
     * @return array<int, string>
     */
    public static function liveValues(): array
    {
        return [self::Open->value, self::AwaitingClaimant->value, self::AwaitingHolder->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::AwaitingClaimant => 'Awaiting claimant',
            self::AwaitingHolder => 'Awaiting holder',
            self::ResolvedTransfer => 'Transferred',
            self::ResolvedDenied => 'Denied',
            self::Withdrawn => 'Withdrawn',
            self::AutoResolved => 'Auto-resolved',
        };
    }
}
