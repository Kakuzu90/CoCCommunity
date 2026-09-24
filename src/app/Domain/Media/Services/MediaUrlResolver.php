<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Contracts\MediaStorage;

/**
 * Builds the URLs the app hands out: public CDN URLs for ready media, short-lived signed URLs for
 * private/pending objects. Also rewrites the presign host for local MinIO (specs/10 §2.1).
 */
class MediaUrlResolver
{
    public function __construct(private readonly MediaStorage $storage) {}

    public function public(string $path): string
    {
        return rtrim((string) config('media.cdn_url'), '/').'/'.ltrim($path, '/');
    }

    public function signed(string $path, ?int $ttl = null): string
    {
        $ttl ??= (int) config('media.intent.presign_ttl');

        return $this->rewriteHost($this->storage->presignedGet($path, $ttl));
    }

    /**
     * Locally the app signs against an in-network host (http://minio:9000) the host browser cannot
     * resolve; swap the origin for MEDIA_PRESIGN_HOST. No-op when that variable is empty (prod).
     */
    public function rewriteHost(string $url): string
    {
        $host = config('media.presign_host');
        if (empty($host)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        $origin = ($parts['scheme'] ?? 'http').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return rtrim($host, '/').substr($url, strlen($origin));
    }
}
