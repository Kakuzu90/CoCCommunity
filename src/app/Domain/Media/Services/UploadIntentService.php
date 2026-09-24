<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Contracts\MediaStorage;
use App\Domain\Media\Data\UploadIntentData;
use App\Domain\Media\Data\UploadTicket;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Models\Media;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Step 1 of the upload flow (specs/10 §3): create a `pending` media row and hand back a presigned
 * PUT to a storage key the client cannot choose. The app never touches the file bytes.
 */
class UploadIntentService
{
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly MediaStorage $storage,
        private readonly MediaUrlResolver $urls,
    ) {}

    public function create(Authenticatable $user, UploadIntentData $data): UploadTicket
    {
        $collection = $data->collection;
        $config = $collection->config();

        if ($config === null) {
            throw ValidationException::withMessages([
                'collection' => "Uploads to the {$collection->value} collection are not available yet.",
            ]);
        }

        if ($data->size > $config['max_size']) {
            throw ValidationException::withMessages([
                'size' => 'The file is larger than this collection allows.',
            ]);
        }

        if (! in_array($data->declaredMime, $config['declared_mimes'], true)) {
            throw ValidationException::withMessages([
                'mime' => 'This file type is not accepted for this collection.',
            ]);
        }

        $this->assertUnderStorageCap($user, $data->size);

        $ulid = (string) Str::ulid();
        $extension = self::EXTENSIONS[$data->declaredMime];
        $path = sprintf(
            '%s/%s/%s/original.%s',
            config('media.prefixes.quarantine'),
            now()->format('Y/m'),
            $ulid,
            $extension,
        );

        $media = new Media([
            'ulid' => $ulid,
            'collection' => $collection->value,
            'kind' => MediaKind::Image->value,
            'disk' => config('media.disk'),
            'path' => $path,
            'original_filename' => $this->sanitizeFilename($data->filename),
            'mime_type' => $data->declaredMime,
            'extension' => $extension,
            'size_bytes' => $data->size,
            'visibility' => $config['visibility'],
            'expires_at' => now()->addHours((int) config('media.intent.expiry_hours')),
        ]);
        $media->user_id = $user->getAuthIdentifier();
        $media->status = MediaStatus::Pending;
        $media->save();

        $ttl = (int) config('media.intent.presign_ttl');
        $presign = $this->storage->presignedPut($path, $data->declaredMime, $config['max_size'], $ttl);

        return new UploadTicket(
            mediaUlid: $ulid,
            uploadUrl: $this->urls->rewriteHost($presign['url']),
            headers: $presign['headers'],
            expiresIn: $ttl,
            maxSize: $config['max_size'],
        );
    }

    private function assertUnderStorageCap(Authenticatable $user, int $incoming): void
    {
        $used = (int) Media::query()->where('user_id', $user->getAuthIdentifier())->sum('size_bytes');

        if ($used + $incoming > (int) config('media.user_soft_cap')) {
            throw ValidationException::withMessages([
                'size' => 'You have reached your storage limit. Delete some media and try again.',
            ]);
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        $base = preg_replace('/[^\pL\pN.\-_ ]+/u', '', $filename) ?? '';

        return Str::limit(trim($base) ?: 'upload', 250, '');
    }
}
