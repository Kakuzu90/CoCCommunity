<?php

namespace App\Domain\GameAssets\Services;

use App\Domain\GameAssets\Data\PublishReport;
use App\Domain\GameAssets\Exceptions\AssetPackException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Uploads the curated asset pack to the `game/` prefix byte-for-byte (specs/10 §11.2).
 *
 * Nothing is re-encoded, resized or optimised — the files are the originals, and any rewrite would
 * forfeit the "unmodified" claim the fan-content policy requires. Every local file is checked
 * against the manifest's SHA-256 and byte size *before* any upload, and every uploaded object is
 * re-read and checksummed *after*, so a corrupted or mismatched pack aborts.
 */
final class AssetPackPublisher
{
    public function __construct(private readonly ManifestReader $reader) {}

    public function publish(string $sourceDir): PublishReport
    {
        $sourceDir = rtrim($sourceDir, '/');
        $manifest = $this->reader->read($sourceDir.'/manifest.json');
        $assets = $manifest['assets'];

        if ($assets === []) {
            throw new AssetPackException('Refusing to publish an empty pack.');
        }

        // Phase 1 — verify every local file matches the manifest before a single byte is uploaded.
        $planned = [];
        foreach ($assets as $entry) {
            $key = (string) $entry['key'];
            $local = $sourceDir.'/'.$key;
            if (! is_file($local)) {
                throw new AssetPackException("Missing local file for '{$key}'.");
            }

            $bytes = (int) filesize($local);
            $sha = (string) hash_file('sha256', $local);
            if ($bytes !== (int) $entry['bytes']) {
                throw new AssetPackException("Byte size mismatch for '{$key}': manifest {$entry['bytes']}, file {$bytes}.");
            }
            if (! hash_equals((string) $entry['sha256'], $sha)) {
                throw new AssetPackException("Checksum mismatch for '{$key}' before upload.");
            }

            $mime = (string) (mime_content_type($local) ?: 'application/octet-stream');
            $allowed = (array) config('assets.allowed_mimes');
            if (! in_array($mime, $allowed, true)) {
                throw new AssetPackException("Disallowed MIME '{$mime}' for '{$key}'.");
            }

            $planned[] = ['key' => $key, 'local' => $local, 'sha' => $sha, 'mime' => $mime];
        }

        // Phase 2 — upload byte-for-byte to game/{key}, immutable, then verify each object.
        $disk = $this->disk();
        $prefix = trim((string) config('assets.prefix'), '/');
        $written = [];
        foreach ($planned as $item) {
            $object = "{$prefix}/{$item['key']}";
            $disk->put($object, (string) file_get_contents($item['local']), [
                'ContentType' => $item['mime'],
                'CacheControl' => (string) config('assets.cache_control'),
                'visibility' => 'public',
            ]);

            $roundTrip = $disk->get($object);
            if ($roundTrip === null || ! hash_equals($item['sha'], hash('sha256', $roundTrip))) {
                throw new AssetPackException("Uploaded object failed verification: {$object}");
            }

            $written[] = $object;
        }

        return new PublishReport($written);
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('assets.disk'));
    }
}
