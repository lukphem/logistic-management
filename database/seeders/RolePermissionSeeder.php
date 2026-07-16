<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Modules and actions match what's referenced throughout the API
     * controllers/routes. Extend this list as new modules are added
     * (e.g. 'hubs', 'zones', 'webhooks') rather than hardcoding checks
     * elsewhere.
     */
    private array $modules = ['shipments', 'rates', 'riders', 'reports', 'settings', 'roles'];
    private array $actions = ['create', 'read', 'update', 'delete'];

    private array $defaultRoles = [
        'Super Admin' => '*', // gets every permission
        'Ops Manager' => ['shipments', 'riders', 'reports'],
        'Hub Staff' => ['shipments:read', 'shipments:update'],
        'Finance' => ['reports:read', 'settings:read'],
        'Support' => ['shipments:read'],
    ];

    public function run(): void
    {
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}:{$action}",
                    'guard_name' => 'sanctum',
                ]);
            }
        }

        foreach ($this->defaultRoles as $roleName => $scope) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);

            if ($scope === '*') {
                $role->syncPermissions(Permission::all());
                continue;
            }

            $permissionNames = collect($scope)->flatMap(function ($entry) {
                // "shipments" (whole module) vs "shipments:read" (specific action)
                if (str_contains($entry, ':')) {
                    return [$entry];
                }

                return collect($this->actions)->map(fn ($action) => "{$entry}:{$action}");
            });

            $role->syncPermissions($permissionNames->all());
        }
    }
}
