<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * The five roles RolePermissionSeeder creates — protected from
     * deletion so a click can't strand every staff account assigned to
     * one of them. Custom roles created after setup can be removed freely.
     */
    private array $protectedRoles = ['Super Admin', 'Ops Manager', 'Hub Staff', 'Finance', 'Support'];

    public function index(): View
    {
        $roles = Role::where('guard_name', 'web')
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();

        return view('roles.index', ['roles' => $roles, 'protectedRoles' => $this->protectedRoles]);
    }

    public function create(): View
    {
        $permissions = $this->groupedPermissions();

        return view('roles.form', ['role' => new Role(), 'permissions' => $permissions, 'assigned' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $validator->validate();
        $data = $validator->validated();

        foreach (['web', 'sanctum'] as $guard) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => $guard]);
            $role->syncPermissions(
                Permission::where('guard_name', $guard)->whereIn('name', $data['permissions'] ?? [])->get()
            );
        }

        return redirect()->route('roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        abort_unless($role->guard_name === 'web', 404);

        $permissions = $this->groupedPermissions();
        $assigned = $role->permissions->pluck('name')->all();

        return view('roles.form', compact('role', 'permissions', 'assigned'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);

        $validator = Validator::make($request->all(), [
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $validator->validate();
        $selected = $validator->validated()['permissions'] ?? [];

        // Update this (web) role and its sanctum counterpart together —
        // a permission set is one decision, not two, even though Spatie
        // stores it as two rows per guard.
        foreach (['web', 'sanctum'] as $guard) {
            $guardRole = Role::where('name', $role->name)->where('guard_name', $guard)->first();

            $guardRole?->syncPermissions(
                Permission::where('guard_name', $guard)->whereIn('name', $selected)->get()
            );
        }

        return redirect()->route('roles.index')->with('status', 'Role permissions updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);

        if (in_array($role->name, $this->protectedRoles)) {
            return back()->withErrors(['role' => 'This is a default system role and cannot be deleted.']);
        }

        foreach (['web', 'sanctum'] as $guard) {
            Role::where('name', $role->name)->where('guard_name', $guard)->first()?->delete();
        }

        return redirect()->route('roles.index')->with('status', 'Role removed.');
    }

    private function groupedPermissions(): \Illuminate\Support\Collection
    {
        return Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($permission) => explode(':', $permission->name)[0]);
    }
}
