<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Moderation\Enums\ReportStatus;
use App\Modules\PlayerAccounts\Enums\AccountState;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function insertSchemaRow(string $table, array $attributes): int
{
    return (int) DB::table($table)->insertGetId([
        ...$attributes,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->accountId = insertSchemaRow('coc_accounts', [
        'user_id' => $this->owner->id, 'tag' => '#P0Y028',
    ]);
    $this->baseId = insertSchemaRow('base_layouts', [
        'user_id' => $this->owner->id,
        'coc_account_id' => $this->accountId,
        'title' => 'War layout',
        'th_level' => 14,
        'category' => 'War',
        'base_link' => 'https://link.clashofclans.com/en?action=OpenLayout&id=test',
        'visibility' => 'private',
    ]);
});

test('every specified domain table has identity and timestamps', function (string $table): void {
    expect(Schema::hasColumns($table, ['id', 'created_at', 'updated_at']))->toBeTrue();
})->with([
    'users', 'profiles', 'roles', 'permissions', 'coc_accounts', 'coc_account_claims',
    'coc_account_snapshots', 'coc_account_media', 'clans', 'clan_memberships',
    'base_layouts', 'base_media', 'tags', 'base_tags', 'base_comments', 'base_likes',
    'base_bookmarks', 'recruitment_posts', 'recruitment_applications',
    'marketplace_listings', 'marketplace_orders', 'marketplace_reviews',
    'conversations', 'conversation_participants', 'messages', 'notifications',
    'reports', 'moderation_actions', 'audit_logs', 'media',
]);

test('user facing content supports soft deletion', function (string $table): void {
    expect(Schema::hasColumn($table, 'deleted_at'))->toBeTrue();
})->with(['users', 'profiles', 'coc_accounts', 'base_layouts', 'base_comments',
    'recruitment_posts', 'marketplace_listings', 'marketplace_reviews', 'conversations', 'messages']);

test('user status defaults to active and cannot be mass assigned', function (): void {
    expect($this->owner->refresh()->status)->toBe(UserStatus::Active);
    expect($this->owner->isFillable('status'))->toBeFalse();
});

test('account tags remain globally unique after soft deletion', function (): void {
    DB::table('coc_accounts')->where('id', $this->accountId)->update(['deleted_at' => now()]);
    $other = User::factory()->create();

    expect(fn () => DB::transaction(fn () => insertSchemaRow('coc_accounts', [
        'user_id' => $other->id, 'tag' => '#P0Y028',
    ])))->toThrow(QueryException::class);
});

test('profile usernames and user ids are unique', function (string $duplicate): void {
    insertSchemaRow('profiles', [
        'user_id' => $this->owner->id, 'username' => 'player', 'privacy' => '{}',
    ]);
    $other = User::factory()->create();

    expect(fn () => DB::transaction(fn () => insertSchemaRow('profiles', [
        'user_id' => $duplicate === 'user_id' ? $this->owner->id : $other->id,
        'username' => $duplicate === 'username' ? 'player' : 'another-player',
        'privacy' => '{}',
    ])))->toThrow(QueryException::class);
})->with(['user_id', 'username']);

test('a user cannot duplicate a base reaction', function (string $table): void {
    $row = ['base_layout_id' => $this->baseId, 'user_id' => $this->owner->id];
    insertSchemaRow($table, $row);
    expect(fn () => DB::transaction(fn () => insertSchemaRow($table, $row)))->toThrow(QueryException::class);
})->with(['base_likes', 'base_bookmarks']);

test('one application is allowed per applicant and recruitment post', function (): void {
    $postId = insertSchemaRow('recruitment_posts', [
        'type' => 'lfc', 'user_id' => $this->owner->id, 'status' => 'open', 'body' => 'Looking for a clan',
    ]);
    $row = [
        'recruitment_post_id' => $postId, 'applicant_user_id' => $this->owner->id,
        'coc_account_id' => $this->accountId, 'status' => 'pending',
    ];
    insertSchemaRow('recruitment_applications', $row);
    expect(fn () => DB::transaction(fn () => insertSchemaRow('recruitment_applications', $row)))->toThrow(QueryException::class);
});

test('marketplace reviews are unique per order and rater and escrow is unset', function (): void {
    $listingId = insertSchemaRow('marketplace_listings', [
        'seller_id' => $this->owner->id, 'category' => 'coaching',
        'title' => 'Base review', 'price' => 1500, 'status' => 'draft',
    ]);
    $buyer = User::factory()->create();
    $orderId = insertSchemaRow('marketplace_orders', [
        'listing_id' => $listingId, 'buyer_id' => $buyer->id,
        'seller_id' => $this->owner->id, 'status' => 'pending', 'amount' => 1500,
    ]);
    $row = ['order_id' => $orderId, 'rater_id' => $buyer->id, 'rating' => 5];
    insertSchemaRow('marketplace_reviews', $row);

    expect(DB::table('marketplace_orders')->find($orderId)->escrow_state)->toBeNull();
    expect((int) DB::table('marketplace_orders')->find($orderId)->amount)->toBe(1500);
    expect(fn () => DB::transaction(fn () => insertSchemaRow('marketplace_reviews', $row)))->toThrow(QueryException::class);
});

test('foreign keys reject missing referenced resources', function (): void {
    expect(fn () => DB::transaction(fn () => insertSchemaRow('base_likes', [
        'base_layout_id' => 999999, 'user_id' => $this->owner->id,
    ])))->toThrow(QueryException::class);
});

test('hard deleting a content owner is restricted and soft deletion retains content', function (): void {
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $this->owner->id)->delete()))
        ->toThrow(QueryException::class);

    $this->owner->delete();
    $this->assertDatabaseHas('base_layouts', ['id' => $this->baseId, 'user_id' => $this->owner->id]);
    $this->assertDatabaseHas('coc_accounts', ['id' => $this->accountId, 'user_id' => $this->owner->id]);
});

test('hard deleting a base cascades reactions and tags but retains media', function (): void {
    foreach (['base_likes', 'base_bookmarks'] as $table) {
        insertSchemaRow($table, ['base_layout_id' => $this->baseId, 'user_id' => $this->owner->id]);
    }
    $tagId = insertSchemaRow('tags', ['name' => 'war']);
    insertSchemaRow('base_tags', ['base_layout_id' => $this->baseId, 'tag_id' => $tagId]);
    $mediaId = insertSchemaRow('media', [
        'path' => 'bases/image.jpg', 'mime' => 'image/jpeg', 'size' => 100, 'uploader_id' => $this->owner->id,
    ]);
    insertSchemaRow('base_media', [
        'base_layout_id' => $this->baseId, 'media_id' => $mediaId, 'kind' => 'image', 'position' => 0,
    ]);

    DB::table('base_layouts')->where('id', $this->baseId)->delete();

    foreach (['base_likes', 'base_bookmarks', 'base_tags', 'base_media'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
    $this->assertDatabaseHas('media', ['id' => $mediaId]);
    $this->assertDatabaseHas('tags', ['id' => $tagId]);
});

test('snapshot blobs round trip without normalizing API payloads', function (): void {
    $data = ['heroes' => [['name' => 'Barbarian King', 'level' => 80]], 'troops' => []];
    $id = insertSchemaRow('coc_account_snapshots', [
        'coc_account_id' => $this->accountId, 'th_level' => 14, 'trophies' => 4000,
        'war_stars' => 500, 'data' => json_encode($data, JSON_THROW_ON_ERROR), 'fetched_at' => now(),
    ]);

    expect(json_decode(DB::table('coc_account_snapshots')->find($id)->data, true))->toEqual($data);
    $base = DB::table('base_layouts')->find($this->baseId);
    expect((int) $base->like_count)->toBe(0);
    expect((int) $base->view_count)->toBe(0);
    expect((int) $base->copy_click_count)->toBe(0);
});

test('database state constraints accept the backed enum values and reject unknown states', function (): void {
    foreach (UserStatus::cases() as $status) {
        DB::table('users')->where('id', $this->owner->id)->update(['status' => $status->value]);
        expect($this->owner->refresh()->status)->toBe($status);
    }
    foreach (AccountState::cases() as $state) {
        DB::table('coc_accounts')->where('id', $this->accountId)->update(['state' => $state->value]);
        expect(DB::table('coc_accounts')->find($this->accountId)->state)->toBe($state->value);
    }
    foreach (ReportStatus::cases() as $status) {
        insertSchemaRow('reports', [
            'reporter_id' => $this->owner->id, 'reportable_type' => 'base',
            'reportable_id' => $this->baseId, 'reason' => 'spam', 'status' => $status->value,
        ]);
    }
    foreach ([['users', 'status'], ['coc_accounts', 'state'], ['reports', 'status']] as [$table, $column]) {
        expect(fn () => DB::transaction(fn () => DB::table($table)->update([$column => 'invalid-state'])))
            ->toThrow(QueryException::class);
    }
});

test('discovery and relationship indexes match the specified column order', function (string $table, array $columns): void {
    $indexes = Schema::getIndexes($table);
    expect(collect($indexes)->contains(fn (array $index): bool => $index['columns'] === $columns))->toBeTrue();
})->with([
    ['users', ['status']],
    ['coc_accounts', ['user_id', 'state']],
    ['coc_account_claims', ['coc_account_id', 'status']],
    ['clan_memberships', ['clan_id']],
    ['clan_memberships', ['coc_account_id']],
    ['base_layouts', ['th_level', 'category', 'visibility']],
    ['base_layouts', ['user_id']],
    ['base_comments', ['base_layout_id', 'status']],
    ['recruitment_posts', ['type', 'status', 'language', 'location']],
    ['marketplace_listings', ['category', 'status']],
    ['marketplace_orders', ['buyer_id']],
    ['marketplace_orders', ['seller_id', 'status']],
    ['conversations', ['subject_type', 'subject_id']],
    ['messages', ['conversation_id', 'created_at']],
    ['notifications', ['user_id', 'read_at']],
    ['reports', ['reportable_type', 'reportable_id']],
    ['reports', ['status']],
    ['moderation_actions', ['target_type', 'target_id']],
    ['audit_logs', ['subject_type', 'subject_id']],
    ['audit_logs', ['actor_id', 'created_at']],
]);

test('postgres stores structured blobs as jsonb and indexes newest snapshots first', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('PostgreSQL catalog assertions run in the PostgreSQL CI job.');
    }

    foreach ([
        'profiles' => ['privacy'], 'coc_account_claims' => ['evidence'],
        'coc_account_snapshots' => ['data'], 'clans' => ['data'],
        'notifications' => ['data'], 'reports' => ['evidence'], 'audit_logs' => ['before', 'after'],
    ] as $table => $columns) {
        foreach ($columns as $column) {
            expect(Schema::getColumnType($table, $column))->toBe('jsonb');
        }
    }

    $index = DB::table('pg_indexes')->where('schemaname', 'public')
        ->where('indexname', 'coc_snapshots_account_fetched_index')->value('indexdef');
    expect($index)->toContain('coc_account_id, fetched_at DESC');
});
