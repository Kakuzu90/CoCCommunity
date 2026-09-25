<?php

namespace App\Domain\Auth\Data;

/**
 * The user-list filter/sort state (specs/12 §4 FilterBar, specs/18 §4 DataTable). Built from the
 * request query string so the list is deep-linkable and keyboard-first — no client state required.
 * Unknown sort keys fall back to a safe default rather than trusting request input into the query.
 */
final readonly class AdminUserFilters
{
    private const SORTS = ['created_at', 'username', 'last_login_at'];

    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public ?string $status = null,
        public string $sort = 'created_at',
        public string $direction = 'desc',
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $search = self::str($query['q'] ?? null);
        $sort = self::str($query['sort'] ?? null);
        $direction = self::str($query['dir'] ?? null);

        return new self(
            search: $search,
            role: self::str($query['role'] ?? null),
            status: self::str($query['status'] ?? null),
            sort: in_array($sort, self::SORTS, true) ? $sort : 'created_at',
            direction: $direction === 'asc' ? 'asc' : 'desc',
        );
    }

    private static function str(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
