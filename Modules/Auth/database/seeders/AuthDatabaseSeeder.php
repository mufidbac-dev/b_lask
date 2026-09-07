<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AuthDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'profile:read',
            'profile:write',
            'tokens:manage',
            'view-any-project', 'view-project', 'create-project', 'update-project',
            'delete-project', 'restore-project', 'force-delete-project', 'export-project',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $owner = Role::findOrCreate('owner', 'web');
        $owner->syncPermissions($permissions);
    }
}
