<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Enums\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    public function seed(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::cases() as $role) {
            foreach ($role->permissions() as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            PermissionRole::findOrCreate($role->value, 'web')->syncPermissions($role->permissions());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
