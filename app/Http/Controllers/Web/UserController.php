<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::where('user_type', 'staff')
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        // Only the 'web' guard copy is shown — a role's permission set is
        // identical across both guards (see store/update), so the guard
        // is an implementation detail the picker doesn't need to expose.
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get();

        return view('users.form', ['user' => new User(), 'roles' => $roles]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ]);

        $validator->validate();
        $data = $validator->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'user_type' => 'staff',
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->assignRoleAcrossGuards($user, $data['role']);

        return redirect()->route('users.index')->with('status', 'Staff account created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->user_type === 'staff', 404);

        $roles = Role::where('guard_name', 'web')->orderBy('name')->get();

        return view('users.form', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'staff', 404);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ]);

        $validator->validate();
        $data = $validator->validated();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $request->boolean('is_active', true),
            ...($data['password'] ? ['password' => Hash::make($data['password'])] : []),
        ]);

        $user->syncRoles([]); // clear existing (both guards) before reassigning
        $this->assignRoleAcrossGuards($user, $data['role']);

        return redirect()->route('users.index')->with('status', 'Staff account updated.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'staff', 404);

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->is_active ? 'Account reactivated.' : 'Account deactivated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'staff', 404);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => "You can't delete your own account."]);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Staff account removed.');
    }

    /**
     * A role's name exists as two separate Role rows, one per guard (see
     * RolePermissionSeeder) — assigning it to a user means assigning both,
     * since the same person authenticates via 'web' on the dashboard and
     * potentially 'sanctum' if they ever use the API/mobile too.
     */
    private function assignRoleAcrossGuards(User $user, string $roleName): void
    {
        foreach (['web', 'sanctum'] as $guard) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();

            if ($role) {
                $user->assignRole($role);
            }
        }
    }
}
