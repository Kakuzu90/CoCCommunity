<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\PlayerAccounts\Models\CocAccountSnapshot;

final class SnapshotCompactor
{
    public function compact(bool $dryRun = false): int
    {
        $cutoff = now()->subDays((int) config('coc.sync.snapshot_full_days'));
        $weeklyCutoff = now()->subDays((int) config('coc.sync.snapshot_daily_days'));
        $removed = 0;

        $accountIds = CocAccountSnapshot::query()
            ->where('captured_at', '<', $cutoff)
            ->select('coc_account_id')
            ->distinct()
            ->orderBy('coc_account_id')
            ->cursor();

        foreach ($accountIds as $row) {
            $snapshots = CocAccountSnapshot::query()
                ->where('coc_account_id', $row->coc_account_id)
                ->orderByDesc('captured_at')
                ->orderByDesc('id')
                ->get(['id', 'captured_at']);
            $seen = [];
            $delete = [];

            foreach ($snapshots as $index => $snapshot) {
                if ($snapshot->captured_at->greaterThanOrEqualTo($cutoff)) {
                    continue;
                }

                $date = $snapshot->captured_at;
                $bucket = $date->greaterThanOrEqualTo($weeklyCutoff)
                    ? 'day:'.$date->toDateString()
                    : 'week:'.$date->format('o-W');
                if ($index === 0) {
                    $seen[$bucket] = true;

                    continue;
                }
                if (isset($seen[$bucket])) {
                    $delete[] = $snapshot->id;
                } else {
                    $seen[$bucket] = true;
                }
            }

            $removed += count($delete);
            if (! $dryRun) {
                foreach (array_chunk($delete, max(1, (int) config('coc.sync.batch_size'))) as $chunk) {
                    CocAccountSnapshot::query()->whereIn('id', $chunk)->delete();
                }
            }
        }

        return $removed;
    }
}
