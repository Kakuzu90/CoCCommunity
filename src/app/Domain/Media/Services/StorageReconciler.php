<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Data\ReconcileReport;
use App\Domain\Media\Models\Media;
use App\Domain\Media\Models\MediaVariant;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Diffs the bucket against the database (specs/10 §9). It scans an EXPLICIT allowlist of prefixes —
 * never `game/`. Game assets have no `media` row by design (specs/10 §11), so an unguarded reconcile
 * would see the entire curated pack as orphaned and delete it. The exclusion is a property of this
 * constant, enforced by a test, not a convention a future edit can quietly break.
 *
 * This pass reports only. Deletion (log-first, delete on a second consecutive detection) belongs to
 * the Ops phase; the allowlist that makes it safe is established here.
 */
final class StorageReconciler
{
    /**
     * The only prefixes the reconcile job may enumerate. `config('assets.prefix')` ("game") is
     * deliberately absent and a test asserts it can never appear here.
     *
     * @var list<string>
     */
    public const PREFIXES = ['quarantine', 'public', 'private'];

    public function reconcile(): ReconcileReport
    {
        $disk = $this->disk();

        /** @var array<string, true> $known */
        $known = [];
        foreach (Media::query()->pluck('path') as $path) {
            $known[(string) $path] = true;
        }
        foreach (MediaVariant::query()->pluck('path') as $path) {
            $known[(string) $path] = true;
        }

        $orphans = [];
        foreach (self::PREFIXES as $prefix) {
            foreach ($disk->allFiles($prefix) as $object) {
                if (! isset($known[$object])) {
                    $orphans[] = $object;
                }
            }
        }

        $missing = [];
        foreach (array_keys($known) as $path) {
            if (! $disk->exists($path)) {
                $missing[] = $path;
            }
        }

        return new ReconcileReport(self::PREFIXES, $orphans, $missing);
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('media.disk'));
    }
}
