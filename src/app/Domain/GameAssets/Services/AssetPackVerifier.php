<?php

namespace App\Domain\GameAssets\Services;

use App\Domain\GameAssets\Data\VerifyReport;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Audits a published pack against its committed manifest (specs/10 §9 `assets:verify-pack`, weekly):
 * every manifest entry must resolve to an object whose SHA-256 matches the recorded checksum, and
 * no extra object may exist under the version prefix. This is what makes "unmodified" demonstrable
 * rather than asserted, and it catches a bucket edited out from under the repo.
 */
final class AssetPackVerifier
{
    public function __construct(private readonly ManifestReader $reader) {}

    public function verify(int $version): VerifyReport
    {
        $file = rtrim((string) config('assets.manifest_path'), '/')."/{$version}/manifest.json";
        $manifest = $this->reader->read($file);
        $assets = $manifest['assets'];

        $disk = $this->disk();
        $prefix = trim((string) config('assets.prefix'), '/');
        $base = "{$prefix}/{$version}";

        $missing = [];
        $modified = [];
        $expected = [];
        foreach ($assets as $entry) {
            $object = $base.'/'.(string) $entry['key'];
            $expected[$object] = true;

            if (! $disk->exists($object)) {
                $missing[] = $object;

                continue;
            }

            $contents = $disk->get($object);
            if ($contents === null || ! hash_equals((string) $entry['sha256'], hash('sha256', $contents))) {
                $modified[] = $object;
            }
        }

        $extra = [];
        foreach ($disk->allFiles($base) as $object) {
            if (! isset($expected[$object])) {
                $extra[] = $object;
            }
        }

        return new VerifyReport($version, count($assets), $missing, $modified, $extra);
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('assets.disk'));
    }
}
