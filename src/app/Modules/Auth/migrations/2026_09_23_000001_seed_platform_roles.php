<?php

declare(strict_types=1);

use App\Modules\Auth\Services\RoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RoleService::class)->seed();
    }

    public function down(): void
    {
        // Roles may have live assignments; the preceding schema migration owns their removal.
    }
};
