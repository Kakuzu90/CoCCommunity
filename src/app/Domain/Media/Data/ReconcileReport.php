<?php

namespace App\Domain\Media\Data;

/** Result of a storage reconcile pass (specs/10 §9 `media:reconcile-storage`, weekly). */
final readonly class ReconcileReport
{
    /**
     * @param  list<string>  $scannedPrefixes  prefixes listed — the `game/` prefix is never among them
     * @param  list<string>  $orphanObjects  bucket objects with no `media`/`media_variants` row
     * @param  list<string>  $missingObjects  database paths whose object is gone
     */
    public function __construct(
        public array $scannedPrefixes,
        public array $orphanObjects,
        public array $missingObjects,
    ) {}
}
