<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Auth\Services\NotificationRecipient;
use App\Domain\Notifications\Data\NotificationData;
use App\Domain\Notifications\Enums\NoticeKind;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

final class NotificationReadModel
{
    public function morphType(): string
    {
        return app(NotificationRecipient::class)->morphType();
    }

    public function owned(int $userId): Builder
    {
        return DB::table('notifications')->where('notifiable_type', $this->morphType())->where('notifiable_id', $userId);
    }

    public function unread(int $userId): int
    {
        return (int) Cache::remember('notif:unread:'.$userId, (int) config('notifications.unread_ttl'),
            fn (): int => $this->owned($userId)->whereNull('read_at')->count());
    }

    public function invalidate(int $userId): void
    {
        Cache::forget('notif:unread:'.$userId);
    }

    /** @return Collection<int, NotificationData> */
    public function recent(int $userId): Collection
    {
        return $this->owned($userId)->orderByDesc('created_at')->orderByDesc('id')
            ->limit((int) config('notifications.preview_limit'))->get()->map($this->data(...));
    }

    /** @return LengthAwarePaginator<int, NotificationData> */
    public function paginate(int $userId, string $category = 'all'): LengthAwarePaginator
    {
        return $this->owned($userId)
            ->when($category !== 'all', fn (Builder $query): Builder => $query->where('data->category', $category))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate((int) config('notifications.per_page'))
            ->through($this->data(...));
    }

    public function find(int $userId, string $id): NotificationData
    {
        $row = $this->owned($userId)->where('id', $id)->first();
        abort_if($row === null, 404);

        return $this->data($row);
    }

    private function data(\stdClass $row): NotificationData
    {
        $kind = NoticeKind::tryFrom($row->type);
        $route = $kind?->targetRoute();

        return new NotificationData(
            $row->id, $kind?->title() ?? 'Notification',
            $kind?->message() ?? 'This content is no longer available.',
            $kind?->category() ?? 'other', $row->read_at === null,
            CarbonImmutable::parse($row->created_at),
            $route !== null && Route::has($route) ? route($route) : null,
        );
    }
}
