<?php

declare(strict_types=1);

namespace App\Modules\Media\Policies;

use App\Models\User;
use App\Modules\Media\Models\Media;

class MediaPolicy
{
    public function view(User $user, Media $media): bool
    {
        return ! $user->trashed() && $user->id === $media->uploader_id;
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->view($user, $media);
    }
}
