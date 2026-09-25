<?php

namespace App\Domain\Bases\Data;

final readonly class PublishBaseData
{
    /**
     * @param  list<string>  $tags
     * @param  list<string>  $screenshots
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public int $thLevel,
        public string $category,
        public string $baseLink,
        public string $visibility,
        public array $tags,
        public array $screenshots,
        public ?string $video,
        public ?int $cocAccountId,
    ) {}
}
