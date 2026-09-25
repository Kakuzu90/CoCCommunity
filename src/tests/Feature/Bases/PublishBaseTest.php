<?php

use App\Domain\Auth\Models\User;
use App\Domain\Bases\Data\PublishBaseData;
use App\Domain\Bases\Enums\BaseStatus;
use App\Domain\Bases\Models\BaseLayout;
use App\Domain\Bases\Services\PublishBaseService;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Events\MediaReady;
use App\Domain\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function validBaseInput(array $overrides = []): array
{
    return array_merge([
        'title' => 'Anti air war base',
        'description' => 'Protects the core.',
        'th_level' => 16,
        'category' => 'war',
        'base_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=TH16%3AHV%3AAAAAAA',
        'visibility' => 'public',
        'tags_text' => 'anti-air, ring',
    ], $overrides);
}

it('renders the composer for a verified account', function () {
    $user = User::factory()->withVerifiedCocAccount()->create();

    $this->actingAs($user)->get(route('bases.create'))
        ->assertOk()
        ->assertSee('Publish a base')
        ->assertSee('Choose screenshots or drop them here');
});

it('publishes a valid base and records its metrics and normalized tags', function () {
    $user = User::factory()->withVerifiedCocAccount()->create();

    $response = $this->actingAs($user)->post(route('bases.store'), validBaseInput());

    $base = BaseLayout::query()->sole();
    $response->assertRedirect(route('bases.submitted', $base->ulid));
    expect($base->status)->toBe(BaseStatus::Published)
        ->and($base->layout_hash)->toHaveLength(64)
        ->and($base->user_id)->toBe($user->id);
    $this->assertDatabaseHas('base_metrics', ['base_layout_id' => $base->id, 'likes_count' => 0]);
    $this->assertDatabaseCount('base_layout_tag', 2);
    $this->assertDatabaseHas('base_tags', ['slug' => 'anti-air', 'usage_count' => 1]);
});

it('requires a verified account and rejects a forged or duplicate link', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('bases.store'), validBaseInput())->assertForbidden();

    $user->forceFill(['verified_accounts_count' => 1])->save();
    $this->actingAs($user)->post(route('bases.store'), validBaseInput([
        'base_link' => 'https://link.clashofclans.com.evil.test/en?action=OpenLayout&id=TH16:HV:AAAAAA',
    ]))->assertSessionHasErrors('baseLink');

    $this->actingAs($user)->post(route('bases.store'), validBaseInput())->assertRedirect();
    $this->actingAs($user)->post(route('bases.store'), validBaseInput())->assertSessionHasErrors('baseLink');
    $this->assertDatabaseCount('base_layouts', 1);
});

it('keeps a base processing until its screenshot is ready', function () {
    $user = User::factory()->withVerifiedCocAccount()->create();
    $media = Media::factory()->forUser($user)->state([
        'collection' => MediaCollection::BaseScreenshot->value,
        'status' => MediaStatus::Processing->value,
    ])->create();

    $this->actingAs($user)->post(route('bases.store'), validBaseInput(['screenshots' => [$media->ulid]]))->assertRedirect();
    $base = BaseLayout::query()->sole();
    expect($base->status)->toBe(BaseStatus::Processing);
    expect($media->fresh()->attachable_type)->toBe('base_layout');

    $media->status = MediaStatus::Ready;
    $media->save();
    MediaReady::dispatch($media->id);

    expect($base->fresh()->status)->toBe(BaseStatus::Published);
});

it('rejects another user’s upload without leaving a base behind', function () {
    $author = User::factory()->withVerifiedCocAccount()->create();
    $other = User::factory()->create();
    $media = Media::factory()->forUser($other)->ready()->state([
        'collection' => MediaCollection::BaseScreenshot->value,
        'expires_at' => now()->addHour(),
    ])->create();

    $this->actingAs($author)->post(route('bases.store'), validBaseInput(['screenshots' => [$media->ulid]]))
        ->assertSessionHasErrors('media');

    $this->assertDatabaseCount('base_layouts', 0);
    $this->assertDatabaseCount('base_metrics', 0);
});

it('flags cross-author duplicates and caps publishing per user', function () {
    $first = User::factory()->withVerifiedCocAccount()->create();
    $second = User::factory()->withVerifiedCocAccount()->create();
    $this->actingAs($first)->post(route('bases.store'), validBaseInput())->assertRedirect();

    config(['bases.publish_day_limit' => 1]);
    $this->actingAs($second)->post(route('bases.store'), validBaseInput())->assertRedirect();
    $duplicate = BaseLayout::query()->where('user_id', $second->id)->sole();
    expect($duplicate->flagged_reason)->toBe('duplicate_layout');

    $data = new PublishBaseData('Second base', null, 16, 'war',
        'https://link.clashofclans.com/en?action=OpenLayout&id=TH16%3AHV%3ABBBBBB',
        'public', [], [], null, null);
    expect(fn () => app(PublishBaseService::class)->publish($second, $data))
        ->toThrow(ValidationException::class);
});

it('keeps another user’s submission private', function () {
    $owner = User::factory()->withVerifiedCocAccount()->create();
    $viewer = User::factory()->create();
    $this->actingAs($owner)->post(route('bases.store'), validBaseInput())->assertRedirect();
    $base = BaseLayout::query()->sole();

    $this->actingAs($viewer)->get(route('bases.submitted', $base->ulid))->assertNotFound();
});

it('escapes submitted titles and validates category and Town Hall', function () {
    $user = User::factory()->withVerifiedCocAccount()->create();
    $this->actingAs($user)->post(route('bases.store'), validBaseInput([
        'category' => 'unknown', 'th_level' => 1,
    ]))->assertSessionHasErrors(['category', 'th_level']);

    $this->actingAs($user)->post(route('bases.store'), validBaseInput([
        'title' => '<img src=x onerror=alert(1)>',
    ]))->assertRedirect();
    $base = BaseLayout::query()->sole();

    $this->actingAs($user)->get(route('bases.submitted', $base->ulid))
        ->assertOk()
        ->assertDontSee('<img src=x onerror=alert(1)>', false)
        ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
});

it('renders validation errors safely when posted fields have the wrong shape', function () {
    $user = User::factory()->withVerifiedCocAccount()->create();
    $this->actingAs($user)->post(route('bases.store'), validBaseInput([
        'title' => ['unexpected'],
        'screenshots' => [['not-an-ulid']],
    ]))->assertSessionHasErrors(['title', 'screenshots.0']);

    $this->get(route('bases.create'))->assertOk()->assertSee('Check these fields');
});
