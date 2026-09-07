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
            'view-any-task', 'view-task', 'create-task', 'update-task',
            'delete-task', 'restore-task', 'force-delete-task', 'export-task',
            'view-any-tag', 'view-tag', 'create-tag', 'update-tag',
            'delete-tag', 'restore-tag', 'force-delete-tag', 'export-tag',
            'view-any-note', 'view-note', 'create-note', 'update-note',
            'delete-note', 'restore-note', 'force-delete-note', 'export-note',
            'view-any-reminder', 'view-reminder', 'create-reminder', 'update-reminder',
            'delete-reminder', 'restore-reminder', 'force-delete-reminder', 'export-reminder',
            'view-any-calendar-event', 'view-calendar-event', 'create-calendar-event',
            'update-calendar-event', 'delete-calendar-event', 'restore-calendar-event',
            'force-delete-calendar-event', 'export-calendar-event',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $owner = Role::findOrCreate('owner', 'web');
        $owner->syncPermissions($permissions);
    }
}
