<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hub;
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
            ->with(['roles', 'hub'])
            ->orderBy('name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(),
            'roles' => $this->webRoles(),
            'hubs' => Hub::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateForm($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'user_type' => 'staff',
            'hub_id' => $data['hub_id'],
        ]);

        $this->assignRoleAcrossGuards($user, $data['role']);

        return redirect()->route('users.index')->with('status', 'Staff account created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->user_type === 'staff', 404);

        return view('users.form', [
            'user' => $user->load('statusAudits.changedBy'),
            'roles' => $this->webRoles(),
            'hubs' => Hub::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'staff', 404);

        $data = $this->validateForm($request, $user->id);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'hub_id' => $data['hub_id'],
            ...($data['password'] ? ['password' => Hash::make($data['password'])] : []),
        ]);

        $user->syncRoles([]); // clear existing (both guards) before reassigning
        $this->assignRoleAcrossGuards($user, $data['role']);

        return redirect()->route('users.index')->with('status', 'Staff account updated.');
    }

    /**
     * One action handles all three restrictive statuses — same
     * validation, same audit write, only the target status differs.
     * 'reactivate' goes through the same path back to 'active'.
     */
    public function changeStatus(Request $request, User $user, string $status): RedirectResponse
    {
        abort_unless($user->user_type === 'staff', 404);
        abort_unless(in_array($status, ['suspended', 'locked', 'terminated', 'active']), 404);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => "You can't change your own account's status."]);
        }

        $validator = Validator::make($request->all(), [
            'reason' => $status === 'active' ? 'nullable|string|max:255' : 'required|string|max:255',
        ]);
        $validator->validate();

        $user->changeStatus($status, $validator->validated()['reason'] ?? null, auth()->user());

        $labels = ['suspended' => 'suspended', 'locked' => 'locked', 'terminated' => 'terminated', 'active' => 'reactivated'];

        return back()->with('status', "Account {$labels[$status]}.");
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

    private function webRoles()
    {
        return Role::where('guard_name', 'web')->orderBy('name')->get();
    }

    private function validateForm(Request $request, ?int $ignoreUserId = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($ignoreUserId ? ",{$ignoreUserId}" : ''),
            'password' => $ignoreUserId ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'access_scope' => 'required|in:global,hub',
            'hub_id' => 'required_if:access_scope,hub|nullable|exists:hubs,id',
        ]);

        $validator->validate();
        $data = $validator->validated();

        // access_scope is a form-only concept — the database just stores
        // hub_id null-or-not. Collapsing it here keeps that single-column
        // design from Migration 2026_01_06_000001 while still giving the
        // form two clear, mutually exclusive options.
        $data['hub_id'] = $data['access_scope'] === 'hub' ? $data['hub_id'] : null;

        return $data;
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
