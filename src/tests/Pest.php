<?php

use App\Modules\CocIntegration\Adapters\FakeClashClient;
use App\Modules\CocIntegration\Contracts\ClashClient;
use App\Modules\CocIntegration\DTOs\PlayerData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Bind an in-memory ClashClient and return it so a test can define players.
 */
function fakeClash(): FakeClashClient
{
    $fake = new FakeClashClient;
    app()->instance(ClashClient::class, $fake);

    return $fake;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function playerData(string $tag, array $overrides = []): PlayerData
{
    return new PlayerData(
        tag: $tag,
        name: $overrides['name'] ?? 'NightWitch',
        townHall: $overrides['townHall'] ?? 17,
        expLevel: $overrides['expLevel'] ?? 250,
        trophies: $overrides['trophies'] ?? 6000,
        bestTrophies: $overrides['bestTrophies'] ?? 6200,
        warStars: $overrides['warStars'] ?? 1500,
        league: $overrides['league'] ?? 'Legend League',
        clanTag: $overrides['clanTag'] ?? '#CLANTAG',
        clanName: $overrides['clanName'] ?? 'Bicol Warriors',
        clanRole: $overrides['clanRole'] ?? 'coLeader',
        raw: $overrides['raw'] ?? ['tag' => $tag, 'name' => $overrides['name'] ?? 'NightWitch'],
    );
}
