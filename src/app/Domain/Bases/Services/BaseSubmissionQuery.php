<?php

namespace App\Domain\Bases\Services;

use App\Domain\Bases\Data\BaseSubmission;
use App\Domain\Bases\Models\BaseLayout;

final class BaseSubmissionQuery
{
    public function own(int $userId, string $ulid): BaseSubmission
    {
        $base = BaseLayout::query()->where('user_id', $userId)->where('ulid', $ulid)->firstOrFail();

        return new BaseSubmission($base->ulid, $base->title, $base->status, $base->published_at?->toIso8601String());
    }
}
