<?php

use App\Domain\Auth\Models\User;
use App\Domain\GameAssets\Services\GameAssetCatalogue;
use App\Domain\GameAssets\Services\ManifestReader;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Services\AccountProgressionView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** A throwaway pack that knows Diggy is a pet and Wall Wrecker a siege machine. */
function progressionView(): AccountProgressionView
{
    $dir = sys_get_temp_dir().'/progression-'.uniqid();
    @mkdir($dir, 0777, true);
    $entry = fn (string $slug, string $kind) => ['key' => "x/{$slug}.png", 'slug' => $slug, 'name' => $slug, 'category' => 'unit', 'kind' => $kind, 'sha256' => 'x', 'bytes' => 1];
    file_put_contents($dir.'/manifest.json', json_encode(['assets' => [$entry('diggy', 'pet'), $entry('wall-wrecker', 'siege')]]));

    config([
        'assets.pack_path' => $dir,
        'assets.heroes_equipments' => ['barbarian_king' => ['barbarian-puppet', 'spiky ball']],
        'coc.excluded_units' => ['super-barbarian'],
    ]);
    app()->forgetInstance(GameAssetCatalogue::class);

    return new AccountProgressionView(new GameAssetCatalogue(new ManifestReader));
}

function progressionFixture(): array
{
    return [
        'heroes' => [
            ['name' => 'Barbarian King', 'level' => 105, 'maxLevel' => 105, 'village' => 'home'],
            ['name' => 'Battle Machine', 'level' => 30, 'maxLevel' => 35, 'village' => 'builderBase'],
        ],
        'troops' => [
            ['name' => 'Barbarian', 'level' => 12, 'maxLevel' => 12, 'village' => 'home'],
            ['name' => 'Super Barbarian', 'level' => 1, 'maxLevel' => 1, 'village' => 'home'],
            ['name' => 'Diggy', 'level' => 5, 'maxLevel' => 10, 'village' => 'home'],
            ['name' => 'Wall Wrecker', 'level' => 4, 'maxLevel' => 5, 'village' => 'home'],
            ['name' => 'Raged Barbarian', 'level' => 18, 'maxLevel' => 20, 'village' => 'builderBase'],
        ],
        'spells' => [['name' => 'Lightning Spell', 'level' => 11, 'maxLevel' => 12, 'village' => 'home']],
        'hero_equipment' => [['name' => 'Barbarian Puppet', 'level' => 18, 'maxLevel' => 18, 'village' => 'home']],
    ];
}

it('splits progression by village and groups pets and siege machines out of troops', function () {
    $data = progressionFixture();
    $view = progressionView()->build($data['heroes'], $data['troops'], $data['spells'], $data['hero_equipment']);

    expect(array_keys($view['home']))->toBe(['Heroes', 'Pets', 'Troops', 'Siege machines', 'Spells'])
        ->and(array_column($view['home']['Troops'], 'slug'))->toBe(['barbarian'])
        ->and(array_column($view['home']['Pets'], 'slug'))->toBe(['diggy'])
        ->and(array_column($view['home']['Siege machines'], 'slug'))->toBe(['wall-wrecker'])
        ->and(array_column($view['builder']['Heroes'], 'slug'))->toBe(['battle-machine'])
        ->and(array_column($view['builder']['Troops'], 'slug'))->toBe(['raged-barbarian']);
});

it('hides excluded units', function () {
    $data = progressionFixture();
    $view = progressionView()->build($data['heroes'], $data['troops'], $data['spells'], $data['hero_equipment']);

    expect(array_column($view['home']['Troops'], 'slug'))->not->toContain('super-barbarian');
});

it('attaches configured equipment to its hero, marking what is not unlocked', function () {
    $data = progressionFixture();
    $king = progressionView()->build($data['heroes'], $data['troops'], $data['spells'], $data['hero_equipment'])['home']['Heroes'][0];

    expect($king['maxed'])->toBeTrue()
        ->and($king['equipment'][0])->toMatchArray(['slug' => 'barbarian-puppet', 'level' => 18, 'maxed' => true, 'unlocked' => true])
        ->and($king['equipment'][1])->toMatchArray(['slug' => 'spiky-ball', 'unlocked' => false]);
});

it('renders village tabs, level badges and the hero equipment dialog', function () {
    progressionView();
    $owner = User::factory()->create();
    $account = new CocAccount(['ign' => 'Night Chief', 'th_level' => 16] + progressionFixture());
    $account->forceFill([
        'ulid' => (string) Str::ulid(), 'user_id' => $owner->id, 'tag' => '#2PP0LJQ', 'tag_normalized' => '2PP0LJQ',
        'status' => CocAccountStatus::Verified->value, 'verified_at' => now(), 'verification_method' => 'api_token', 'api_synced_at' => now(),
    ])->save();

    $this->actingAs($owner)->get(route('accounts.show', $account->ulid))->assertOk()
        ->assertSee('Home Village')->assertSee('Builder Base')
        ->assertSee('Level 105, maxed')->assertSee('Level 5')
        ->assertSee('aria-haspopup="dialog"', false)
        ->assertSee('Barbarian King equipment')->assertSee('Not unlocked')
        ->assertDontSee('Super Barbarian');
});

it('renders the hero card with clan, role, league and donations', function () {
    $owner = User::factory()->create();
    $account = new CocAccount([
        'ign' => 'Clyde', 'th_level' => 17, 'xp_level' => 228, 'trophies' => 231, 'best_trophies' => 4926,
        'war_stars' => 1238, 'donations' => 104, 'donations_received' => 0, 'clan_tag' => '#ROYAL', 'clan_role' => 'admin',
        'league_name' => 'Titan League 1', 'raw_payload' => ['clan' => ['name' => 'Royal Family', 'clanLevel' => 11, 'badgeUrls' => ['medium' => 'https://api-assets.example/badge.png']]],
    ]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(), 'user_id' => $owner->id, 'tag' => '#QQ0U8C8JU', 'tag_normalized' => 'QQ0U8C8JU',
        'status' => CocAccountStatus::Verified->value, 'verified_at' => now(), 'verification_method' => 'api_token', 'api_synced_at' => now(),
    ])->save();

    $this->actingAs($owner)->get(route('accounts.show', $account->ulid))->assertOk()
        ->assertSee('aria-label="XP level 228"', false)->assertSee('Elder')
        ->assertSee('Royal Family')->assertSee('Clan level 11')->assertSee('1,238')
        ->assertSee('Titan League 1')->assertSee('4,926 trophies')
        ->assertSeeInOrder(['Troops donated', '104', 'Troops received', '0'])
        ->assertDontSee('Player stats');
});
