<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

uses(RefreshDatabase::class);

it('echoes a caller-supplied request id for cross-tier correlation', function () {
    $res = $this->withHeaders(['X-Request-Id' => 'req-abc-123'])->get('/');

    $res->assertOk();
    expect($res->headers->get('X-Request-Id'))->toBe('req-abc-123');
});

it('mints a request id when the caller supplies none', function () {
    $res = $this->get('/');

    expect($res->headers->get('X-Request-Id'))->not->toBeNull()->not->toBe('');
});

it('writes one structured access-log line per request', function () {
    $logger = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->andReturn($logger);

    $this->get('/')->assertOk();

    $logger->shouldHaveReceived('info')
        ->with('request.handled', Mockery::type('array'))
        ->once();
});

it('does not access-log uptime probes', function () {
    $logger = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->andReturn($logger);

    $this->getJson('/health')->assertOk();

    $logger->shouldNotHaveReceived('info', ['request.handled', Mockery::any()]);
});
