<?php

use App\Domain\Auth\Models\User;
use App\Support\Enums\QueueName;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;

uses(RefreshDatabase::class);

it('registers every domain provider when the application boots', function () {
    $modules = ['Auth', 'Users', 'CocIntegration', 'PlayerAccounts', 'Clans', 'Bases', 'Recruitment',
        'Marketplace', 'Messaging', 'Media', 'GameAssets', 'Notifications', 'Moderation', 'Audit', 'Search', 'Admin'];
    foreach ($modules as $module) {
        expect(app()->providerIsLoaded("App\\Domain\\{$module}\\{$module}ServiceProvider"))->toBeTrue();
    }
});

it('uses immutable UTC dates for the clock and Eloquent timestamps', function () {
    $this->travelTo(CarbonImmutable::parse('2026-01-15 12:00:00', 'UTC'));
    $now = Date::now();
    expect($now)->toBeInstanceOf(CarbonImmutable::class)
        ->and($now->timezoneName)->toBe('UTC')
        ->and($now->addDay()->toDateString())->toBe('2026-01-16')
        ->and(Date::now()->toDateTimeString())->toBe('2026-01-15 12:00:00');

    $user = User::factory()->create()->fresh();
    expect($user->created_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($user->email_verified_at)->toBeInstanceOf(CarbonImmutable::class);
    $user->created_at->addDay();
    expect($user->created_at->toDateTimeString())->toBe('2026-01-15 12:00:00');
});

it('exposes the five specified queue names without requiring a driver', function () {
    expect(array_column(QueueName::cases(), 'value'))->toBe(['high', 'default', 'media', 'sync', 'low']);
    expect(QueueName::from('media'))->toBe(QueueName::Media);
    expect(QueueName::tryFrom('unknown'))->toBeNull();
});
