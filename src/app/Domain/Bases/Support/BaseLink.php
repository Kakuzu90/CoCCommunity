<?php

namespace App\Domain\Bases\Support;

use InvalidArgumentException;

final readonly class BaseLink
{
    private function __construct(public string $url, public LayoutHash $layoutHash) {}

    public static function parse(string $url): self
    {
        $url = trim($url);
        $parts = parse_url($url);
        if ($parts === false || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($parts['host'] ?? '')) !== 'link.clashofclans.com'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || isset($parts['fragment'])
            || ! isset($parts['query']) || strlen($url) > 2048) {
            throw new InvalidArgumentException('Enter an official Clash of Clans base link.');
        }

        parse_str($parts['query'], $query);
        if (($query['action'] ?? null) !== 'OpenLayout' || ! is_string($query['id'] ?? null)) {
            throw new InvalidArgumentException('Enter a base link with an OpenLayout action and layout ID.');
        }

        return new self($url, LayoutHash::fromLayoutId($query['id']));
    }
}
