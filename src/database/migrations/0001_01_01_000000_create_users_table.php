<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auth identity + platform status (specs/07 `users`), released-handle history, Laravel's session
 * and password-reset tables. Deliberately thin: profile data lives in `profiles` (a later task) so
 * hot auth queries stay narrow. Handles/emails become citext on Postgres for case-insensitive
 * uniqueness; SQLite keeps string and the app normalises case (specs/07 portability pattern).
 */
return new class extends Migration
{
    public function up(): void
    {
        $pg = DB::getDriverName() === 'pgsql';
        if ($pg) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 20)->default('user');
            $table->string('status', 20)->default('active');
            $table->string('status_reason', 255)->nullable();
            $table->timestamp('status_expires_at')->nullable();
            $table->unsignedInteger('verified_accounts_count')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip_hash', 64)->nullable();
            $table->timestamp('username_changed_at')->nullable();
            $table->timestamp('deletion_requested_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('username_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->timestamp('released_at');
            $table->unique(['username', 'released_at']);
            $table->index('username');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Laravel's database session table. The session-management UI columns (ip_hash, device_label)
        // and the handler override that fills them are owned by the Settings/sessions task; the
        // default columns Laravel's DatabaseSessionHandler writes are kept here so sessions work now.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        if ($pg) {
            // Case-insensitive handles/emails; rebuilding the type rebuilds the unique indexes with
            // citext comparison, so uniqueness is case-insensitive without app-side lower() tricks.
            DB::statement('ALTER TABLE users ALTER COLUMN username TYPE citext');
            DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext');
            DB::statement('ALTER TABLE username_history ALTER COLUMN username TYPE citext');

            DB::statement("CREATE INDEX users_status_idx ON users (status) WHERE status <> 'active'");
            DB::statement("CREATE INDEX users_role_idx ON users (role) WHERE role <> 'user'");
            DB::statement('CREATE INDEX users_deletion_idx ON users (deletion_requested_at) WHERE deletion_requested_at IS NOT NULL');
            DB::statement('CREATE INDEX users_created_idx ON users (created_at)');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->index('status');
                $table->index('role');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('username_history');
        Schema::dropIfExists('users');
    }
};
