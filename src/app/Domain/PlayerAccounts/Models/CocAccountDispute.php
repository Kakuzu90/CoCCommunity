<?php

namespace App\Domain\PlayerAccounts\Models;

use App\Domain\PlayerAccounts\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A contested tag routed to admins (specs/07, 13 §5). Written by the dispute services; the parties
 * (`claimant_id`, `current_holder_id`), `status`, the assignment and the decision fields are set
 * directly, never mass-assigned — only the claimant's own reason and evidence are fillable.
 *
 * @property int $id
 * @property string $ulid
 * @property int $coc_account_id
 * @property string $tag_normalized
 * @property int $claimant_id
 * @property ?int $current_holder_id
 * @property string $reason
 * @property array<string, mixed> $evidence
 * @property DisputeStatus $status
 * @property ?int $assigned_admin_id
 * @property ?string $decision_note
 * @property ?Carbon $holder_responds_by
 * @property ?int $decided_by
 * @property ?Carbon $decided_at
 * @property ?Carbon $last_claimant_activity_at
 * @property ?Carbon $created_at
 */
final class CocAccountDispute extends Model
{
    // Parties, status, assignment and decision fields are set directly by the services.
    protected $fillable = [
        'ulid', 'tag_normalized', 'reason', 'evidence', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => DisputeStatus::class,
            'evidence' => 'array',
            'holder_responds_by' => 'datetime',
            'decided_at' => 'datetime',
            'last_claimant_activity_at' => 'datetime',
        ];
    }
}
