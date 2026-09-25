<?php

namespace App\Domain\PlayerAccounts\Data;

use App\Domain\PlayerAccounts\Enums\DisputeStatus;
use App\Domain\PlayerAccounts\Models\CocAccountDispute;
use Carbon\CarbonInterface;

/**
 * A read model of a dispute for the claimant, the holder and the admin queue (specs/13 §5). Usernames
 * are resolved by the query so a view never touches the User model. Evidence media is referenced by id
 * only — the bytes are private and fetched through a separately-authorized, audit-logged path.
 *
 * @property-read array{notes: string, media: array<int, string>} $evidence
 */
final readonly class DisputeSummary
{
    /** @param array{notes: string, media: array<int, string>} $evidence */
    public function __construct(
        public int $id,
        public string $ulid,
        public string $tagNormalized,
        public DisputeStatus $status,
        public int $claimantId,
        public ?string $claimantName,
        public ?int $holderId,
        public ?string $holderName,
        public string $reason,
        public array $evidence,
        public ?string $holderResponse,
        public ?string $decisionNote,
        public ?CarbonInterface $holderRespondsBy,
        public ?CarbonInterface $decidedAt,
        public ?CarbonInterface $createdAt,
    ) {}

    /**
     * @param  array<int, string>  $names  user id => username
     */
    public static function fromModel(CocAccountDispute $dispute, array $names = []): self
    {
        $evidence = $dispute->evidence;

        return new self(
            id: $dispute->id,
            ulid: $dispute->ulid,
            tagNormalized: $dispute->tag_normalized,
            status: $dispute->status,
            claimantId: $dispute->claimant_id,
            claimantName: $names[$dispute->claimant_id] ?? null,
            holderId: $dispute->current_holder_id,
            holderName: $dispute->current_holder_id !== null ? ($names[$dispute->current_holder_id] ?? null) : null,
            reason: $dispute->reason,
            evidence: [
                'notes' => (string) ($evidence['notes'] ?? ''),
                'media' => array_values(array_map('strval', (array) ($evidence['media'] ?? []))),
            ],
            holderResponse: isset($evidence['holder_response']['notes'])
                ? (string) $evidence['holder_response']['notes']
                : null,
            decisionNote: $dispute->decision_note,
            holderRespondsBy: $dispute->holder_responds_by,
            decidedAt: $dispute->decided_at,
            createdAt: $dispute->created_at,
        );
    }
}
