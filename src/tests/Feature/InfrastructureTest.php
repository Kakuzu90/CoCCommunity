<?php

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('uses the requested database and migrates infrastructure tables', function () {
    expect(DB::connection()->getDriverName())->toBe(getenv('DB_CONNECTION') ?: 'sqlite');

    foreach (['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    $user = User::factory()->create();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
});
