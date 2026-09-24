<?php

namespace App\Domain\Users\Services;

use App\Domain\Media\Contracts\MediaLibrary;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Users\Data\ProfileData;
use App\Domain\Users\Data\ProfileInput;
use App\Domain\Users\Events\ProfileUpdated;
use App\Domain\Users\Models\Profile;
use App\Domain\Users\Models\UserStats;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The Users module's public entry point for reading and editing a profile (specs/07). Presentation
 * calls this, never the Profile model. Avatar bytes are the Media module's concern — this service
 * only attaches an already-uploaded, processed media and points the profile at it.
 */
class ProfileService
{
    public function __construct(private readonly MediaLibrary $media) {}

    public function get(Authenticatable $user): ProfileData
    {
        return $this->toData($this->ensure($this->id($user)));
    }

    public function getByUserId(int $userId): ProfileData
    {
        return $this->toData($this->ensure($userId));
    }

    public function update(Authenticatable $user, ProfileInput $input): ProfileData
    {
        $profile = $this->ensure($this->id($user));

        $profile->fill([
            'display_name' => $input->displayName,
            'bio' => $input->bio,
            'country_code' => $input->countryCode,
            'languages' => $input->languages,
            'timezone' => $input->timezone,
            'socials' => $input->socials,
        ])->save();

        ProfileUpdated::dispatch($profile->user_id);

        return $this->toData($profile);
    }

    public function setAvatar(Authenticatable $user, string $mediaUlid): ProfileData
    {
        $profile = $this->ensure($this->id($user));

        // Attach validates ownership + collection and 404s a foreign id before any write.
        $newId = $this->media->attach($user, $mediaUlid, MediaCollection::Avatar, $profile);

        $previous = $profile->avatar_media_id;
        $profile->avatar_media_id = $newId;
        $profile->save();

        // The replaced avatar returns to the orphan sweeper (specs/10 §9).
        if ($previous !== null && $previous !== $newId) {
            $this->media->release($previous);
        }

        ProfileUpdated::dispatch($profile->user_id);

        return $this->toData($profile);
    }

    public function removeAvatar(Authenticatable $user): ProfileData
    {
        $profile = $this->ensure($this->id($user));

        $previous = $profile->avatar_media_id;
        $profile->avatar_media_id = null;
        $profile->save();

        $this->media->release($previous);

        ProfileUpdated::dispatch($profile->user_id);

        return $this->toData($profile);
    }

    /**
     * Guarantee the 1:1 profile + stats rows exist (registration creates them; this is defensive).
     * `user_id` is not fillable, so the rows are built explicitly rather than via firstOrCreate.
     */
    public function ensure(int $userId): Profile
    {
        if (! UserStats::query()->whereKey($userId)->exists()) {
            $stats = new UserStats;
            $stats->user_id = $userId;
            $stats->save();
        }

        $profile = Profile::query()->where('user_id', $userId)->first();
        if ($profile === null) {
            $profile = new Profile;
            $profile->user_id = $userId;
            $profile->save();
        }

        return $profile;
    }

    private function toData(Profile $profile): ProfileData
    {
        return new ProfileData(
            userId: $profile->user_id,
            displayName: $profile->display_name,
            bio: $profile->bio,
            countryCode: $profile->country_code,
            languages: $profile->languages ?? [],
            timezone: $profile->timezone,
            socials: $profile->socials ?? [],
            avatar: $this->media->resolve($profile->avatar_media_id),
        );
    }

    private function id(Authenticatable $user): int
    {
        return (int) $user->getAuthIdentifier();
    }
}
