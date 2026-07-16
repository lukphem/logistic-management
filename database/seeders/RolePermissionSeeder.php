<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Modules and actions match what's referenced throughout the API
     * controllers/routes and the Blade admin. Extend this list as new
     * modules are added rather than hardcoding checks elsewhere.
     */
    private array $modules = ['shipments', 'rates', 'riders', 'reports', 'settings', 'roles', 'locations', 'billing'];
    private array $actions = ['create', 'read', 'update', 'delete'];

    private array $defaultRoles = [
        'Super Admin' => '*', // gets every permission
        'Ops Manager' => ['shipments', 'riders', 'reports', 'locations:read', 'rates:read'],
        'Hub Staff' => ['shipments:read', 'shipments:update', 'locations:read'],
        'Finance' => ['reports:read', 'settings:read', 'rates', 'billing'],
        'Support' => ['shipments:read'],
    ];

    /**
     * Seeded under both guards because this app authenticates two ways
     * against the same users table: the Blade admin via the 'web' session
     * guard, and mobile/external API clients via the 'sanctum' guard.
     * Spatie resolves permissions per-guard, so a permission that only
     * exists under 'sanctum' silently fails every check made from a web
     * session request (and vice versa) — seeding both avoids that trap
     * rather than relying on everyone remembering which guard is active.
     */
    private array $guards = ['web', 'sanctum'];

    public function run(): void
    {
        foreach ($this->guards as $guard) {
            foreach ($this->modules as $module) {
                foreach ($this->actions as $action) {
                    Permission::firstOrCreate([
                        'name' => "{$module}:{$action}",
                        'guard_name' => $guard,
                    ]);
                }
            }

            foreach ($this->defaultRoles as $roleName => $scope) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);

                if ($scope === '*') {
                    $role->syncPermissions(Permission::where('guard_name', $guard)->get());
                    continue;
                }

                $permissionNames = collect($scope)->flatMap(function ($entry) {
                    // "shipments" (whole module) vs "shipments:read" (specific action)
                    if (str_contains($entry, ':')) {
                        return [$entry];
                    }

                    return collect($this->actions)->map(fn ($action) => "{$entry}:{$action}");
                });

                $role->syncPermissions(
                    Permission::where('guard_name', $guard)->whereIn('name', $permissionNames)->get()
                );
            }
        }
    }
}
