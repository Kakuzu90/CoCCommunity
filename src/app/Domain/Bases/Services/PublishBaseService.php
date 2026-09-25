<?php

namespace App\Domain\Bases\Services;

use App\Domain\Bases\Data\PublishBaseData;
use App\Domain\Bases\Enums\BaseModerationState;
use App\Domain\Bases\Enums\BaseStatus;
use App\Domain\Bases\Events\BasePublished;
use App\Domain\Bases\Models\BaseLayout;
use App\Domain\Bases\Models\BaseTag;
use App\Domain\Bases\Support\BaseLink;
use App\Domain\Media\Contracts\MediaLibrary;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\PlayerAccounts\Services\VerifiedAccountLookup;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class PublishBaseService
{
    public function __construct(
        private readonly MediaLibrary $media,
        private readonly VerifiedAccountLookup $accounts,
    ) {}

    public function publish(Authenticatable $user, PublishBaseData $data): BaseLayout
    {
        Gate::forUser($user)->authorize('publish-base');

        Validator::make((array) $data, [
            'title' => ['required', 'string', 'max:'.config('bases.title_max')],
            'description' => ['nullable', 'string', 'max:'.config('bases.description_max')],
            'thLevel' => ['required', 'integer', 'between:'.config('bases.th_min').','.config('bases.th_max')],
            'category' => ['required', Rule::in(array_keys(config('bases.categories')))],
            'baseLink' => ['required', 'string', 'max:2048'],
            'visibility' => ['required', Rule::in(array_keys(config('bases.visibilities')))],
            'tags' => ['array', 'max:'.config('bases.tags_max')],
            'tags.*' => ['required', 'string', 'max:100'],
            'screenshots' => ['array', 'max:'.config('bases.screenshots_max')],
            'screenshots.*' => ['required', 'ulid', 'distinct'],
            'video' => ['nullable', 'ulid'],
            'cocAccountId' => ['nullable', 'integer'],
        ])->validate();

        try {
            $link = BaseLink::parse($data->baseLink);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['baseLink' => $e->getMessage()]);
        }

        $userId = (int) $user->getAuthIdentifier();
        if ($data->cocAccountId !== null && ! $this->accounts->ownedBy($userId, $data->cocAccountId)) {
            throw ValidationException::withMessages(['cocAccountId' => 'Choose one of your verified accounts.']);
        }

        $tags = [];
        foreach ($data->tags as $tag) {
            $slug = Str::slug($tag);
            if ($slug === '' || strlen($slug) > (int) config('bases.tag_length_max')) {
                throw ValidationException::withMessages(['tags' => 'Each tag must be 1 to 24 letters or numbers after normalisation.']);
            }
            $tags[$slug] = $slug;
        }

        return DB::transaction(function () use ($user, $userId, $data, $link, $tags): BaseLayout {
            DB::table('users')->where('id', $userId)->lockForUpdate()->first();
            $baseQuery = BaseLayout::withTrashed()->where('user_id', $userId);
            if ((clone $baseQuery)->where('layout_hash', $link->layoutHash->value)->exists()) {
                throw ValidationException::withMessages(['baseLink' => 'You have already shared this layout.']);
            }

            if ((clone $baseQuery)->where('created_at', '>=', now()->startOfDay())->count() >= (int) config('bases.publish_day_limit')
                || (clone $baseQuery)->where('created_at', '>=', now()->startOfWeek())->count() >= (int) config('bases.publish_week_limit')) {
                throw ValidationException::withMessages(['baseLink' => 'You have reached your base publishing limit. Try again later.']);
            }

            $duplicate = BaseLayout::query()->where('layout_hash', $link->layoutHash->value)
                ->where('user_id', '!=', $userId)->exists();
            $ulid = (string) Str::ulid();
            $titleSlug = rtrim(substr(Str::slug($data->title), 0, 63), '-');
            $base = new BaseLayout([
                'title' => trim($data->title),
                'description' => $data->description,
                'th_level' => $data->thLevel,
                'category' => $data->category,
                'base_link' => $link->url,
                'layout_hash' => $link->layoutHash->value,
                'visibility' => $data->visibility,
            ]);
            $base->ulid = $ulid;
            $base->slug = $ulid.'-'.($titleSlug !== '' ? $titleSlug : 'base');
            $base->user_id = $userId;
            $base->coc_account_id = $data->cocAccountId;
            $base->status = BaseStatus::Processing;
            $base->moderation_state = $duplicate ? BaseModerationState::Flagged : BaseModerationState::Clean;
            $base->flagged_reason = $duplicate ? 'duplicate_layout' : null;
            $base->has_video = $data->video !== null;
            $base->save();

            DB::table('base_metrics')->insert(['base_layout_id' => $base->id]);

            foreach ($tags as $slug) {
                $tag = BaseTag::firstOrCreate(['slug' => $slug], ['name' => $slug]);
                if ($tag->is_blocked) {
                    throw ValidationException::withMessages(['tags' => "The tag '{$slug}' is not available."]);
                }
                if (in_array($slug, (array) config('bases.suggested_tags'), true) && ! $tag->is_suggested) {
                    $tag->is_suggested = true;
                    $tag->save();
                }
                DB::table('base_layout_tag')->insert(['base_layout_id' => $base->id, 'base_tag_id' => $tag->id]);
                $tag->increment('usage_count');
            }

            foreach ($data->screenshots as $position => $ulid) {
                $this->media->attach($user, $ulid, MediaCollection::BaseScreenshot, $base, $position);
            }
            if ($data->video !== null) {
                $this->media->attach($user, $data->video, MediaCollection::BaseVideo, $base);
            }

            if ($this->media->allAttachedReady($base)) {
                $this->markPublished($base);
            }

            return $base;
        });
    }

    public function mediaReady(int $mediaId): void
    {
        $attachment = $this->media->attachmentFor($mediaId);
        if ($attachment === null || $attachment->type !== 'base_layout' || $attachment->status !== MediaStatus::Ready) {
            return;
        }

        DB::transaction(function () use ($attachment): void {
            $base = BaseLayout::query()->whereKey($attachment->id)->lockForUpdate()->first();
            if ($base !== null && $base->status === BaseStatus::Processing && $this->media->allAttachedReady($base)) {
                $this->markPublished($base);
            }
        });
    }

    private function markPublished(BaseLayout $base): void
    {
        $base->status = BaseStatus::Published;
        $base->published_at = now();
        $base->save();
        DB::afterCommit(fn () => BasePublished::dispatch($base->id));
    }
}
