<?php

namespace App\Domain\PlayerAccounts\Queries;

use App\Domain\Auth\Services\UserDirectory;
use App\Domain\PlayerAccounts\Data\DisputeSummary;
use App\Domain\PlayerAccounts\Enums\DisputeStatus;
use App\Domain\PlayerAccounts\Models\CocAccountDispute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read side of the dispute workflow (specs/13 §5). Returns DTOs with the parties' handles resolved in
 * one batch, so no view or component ever touches a User or dispute model. IDOR-safe by construction:
 * the claimant/holder methods filter by the viewer's own id, and the admin queue is only reachable
 * behind the resolve gate.
 */
final class DisputeQuery
{
    public function __construct(private readonly UserDirectory $directory) {}

    /**
     * The disputes this user has filed (specs/13 §5). Newest first.
     *
     * @return Collection<int, DisputeSummary>
     */
    public function forClaimant(int $userId): Collection
    {
        $rows = CocAccountDispute::query()
            ->where('claimant_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return $this->decorate($rows);
    }

    /**
     * Live disputes filed against this user's accounts, needing a response (specs/13 §5 step 3).
     *
     * @return Collection<int, DisputeSummary>
     */
    public function againstHolder(int $userId): Collection
    {
        $rows = CocAccountDispute::query()
            ->where('current_holder_id', $userId)
            ->whereIn('status', DisputeStatus::liveValues())
            ->orderBy('holder_responds_by')
            ->get();

        return $this->decorate($rows);
    }

    /**
     * The admin queue (specs/13 §5 step 4). Defaults to the live states that need attention, newest
     * first; a specific status narrows it.
     *
     * @return LengthAwarePaginator<int, DisputeSummary>
     */
    public function queue(?DisputeStatus $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = CocAccountDispute::query();
        if ($status !== null) {
            $query->where('status', $status->value);
        } else {
            $query->whereIn('status', DisputeStatus::liveValues());
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        $names = $this->names($paginator->getCollection());

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (CocAccountDispute $d): DisputeSummary => DisputeSummary::fromModel($d, $names))
        );

        return $paginator;
    }

    public function find(int $id): ?DisputeSummary
    {
        $dispute = CocAccountDispute::query()->find($id);

        return $dispute === null ? null : DisputeSummary::fromModel($dispute, $this->names(collect([$dispute])));
    }

    /** Look a dispute up by its opaque external id — the admin detail route key (specs/04 §3 IDOR). */
    public function findByUlid(string $ulid): ?DisputeSummary
    {
        $dispute = CocAccountDispute::query()->where('ulid', $ulid)->first();

        return $dispute === null ? null : DisputeSummary::fromModel($dispute, $this->names(collect([$dispute])));
    }

    /**
     * @param  Collection<int, CocAccountDispute>  $rows
     * @return Collection<int, DisputeSummary>
     */
    private function decorate(Collection $rows): Collection
    {
        $names = $this->names($rows);

        return $rows->map(fn (CocAccountDispute $d): DisputeSummary => DisputeSummary::fromModel($d, $names));
    }

    /**
     * @param  Collection<int, CocAccountDispute>  $rows
     * @return array<int, string>
     */
    private function names(Collection $rows): array
    {
        $ids = $rows->flatMap(fn (CocAccountDispute $d): array => array_filter([
            $d->claimant_id,
            $d->current_holder_id,
        ]))->all();

        return $this->directory->usernamesByIds($ids);
    }
}
