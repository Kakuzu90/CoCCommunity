<?php

namespace App\Domain\Media\Storage;

use App\Domain\Media\Contracts\MediaStorage;
use RuntimeException;

/**
 * In-memory storage for tests. Presigned URLs are synthetic — the client PUT/GET happens outside
 * the app, so tests seed uploaded bytes with put() and assert on keys.
 */
class FakeMediaStorage implements MediaStorage
{
    /** @var array<string, string> */
    public array $objects = [];

    public function presignedPut(string $path, string $contentType, int $maxSize, int $ttl): array
    {
        return [
            'url' => "https://fake-storage.test/{$path}?ttl={$ttl}",
            'headers' => ['Content-Type' => $contentType],
        ];
    }

    public function presignedGet(string $path, int $ttl): string
    {
        return "https://fake-storage.test/get/{$path}?ttl={$ttl}";
    }

    public function size(string $path): ?int
    {
        return isset($this->objects[$path]) ? strlen($this->objects[$path]) : null;
    }

    public function exists(string $path): bool
    {
        return isset($this->objects[$path]);
    }

    public function download(string $path, string $localPath): void
    {
        if (! isset($this->objects[$path])) {
            throw new RuntimeException("Fake object missing: {$path}");
        }

        file_put_contents($localPath, $this->objects[$path]);
    }

    public function put(string $path, string $contents, string $visibility, string $contentType): void
    {
        $this->objects[$path] = $contents;
    }

    public function delete(string ...$paths): void
    {
        foreach ($paths as $path) {
            unset($this->objects[$path]);
        }
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->objects);
    }
}
