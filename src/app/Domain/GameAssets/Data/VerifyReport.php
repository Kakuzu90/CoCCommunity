<?php

namespace App\Domain\GameAssets\Data;

/** Result of auditing the published pack against its committed manifest (specs/10 §9, §11.2). */
final readonly class VerifyReport
{
    /**
     * @param  list<string>  $missing  manifest entries with no object in the bucket
     * @param  list<string>  $modified  objects whose SHA-256 no longer matches the manifest
     * @param  list<string>  $extra  bucket objects under the game/ prefix not in the manifest
     */
    public function __construct(
        public int $checked,
        public array $missing = [],
        public array $modified = [],
        public array $extra = [],
    ) {}

    public function ok(): bool
    {
        return $this->missing === [] && $this->modified === [] && $this->extra === [];
    }
}
