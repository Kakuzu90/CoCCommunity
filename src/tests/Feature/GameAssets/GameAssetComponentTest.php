<?php

it('renders an accessible placeholder when the asset is unknown', function () {
    config(['assets.enabled' => true]);

    $html = $this->blade('<x-game.asset type="townhall" :value="99" :size="64" />');

    $html->assertSee('role="img"', false);
    $html->assertSee('aria-label="Town Hall 99"', false);
    $html->assertSee('game-asset--placeholder', false);
    $html->assertSee('>99</span>', false);
    $html->assertDontSee('<img', false);
});

it('renders the unmodified pack artwork with an alt name and fixed size', function () {
    config(['assets.enabled' => true, 'assets.cdn_url' => 'https://cdn.test']);

    $html = $this->blade('<x-game.asset type="townhall" :value="15" :size="64" />');

    $html->assertSee('src="https://cdn.test/game/townhalls/15.png?v=', false);
    $html->assertSee('alt="Town Hall 15"', false);
    $html->assertSee('width="64"', false);
    $html->assertDontSee('game-asset--placeholder', false);
});

it('renders an <img> with an alt name for a pass-through clan badge', function () {
    config(['assets.enabled' => true]);

    $html = $this->blade('<x-game.asset type="clan" value="https://api.example/badge.png" name="Reddit Zulu" />');

    $html->assertSee('<img', false);
    $html->assertSee('alt="Reddit Zulu clan badge"', false);
    $html->assertSee('loading="lazy"', false);
});

it('renders placeholders when the category kill switch is off', function () {
    config(['assets.enabled' => false]);

    $html = $this->blade('<x-game.asset type="clan" value="https://api.example/badge.png" name="Reddit Zulu" />');

    $html->assertSee('game-asset--placeholder', false);
    $html->assertDontSee('<img', false);
});
