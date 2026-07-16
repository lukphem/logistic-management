<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\Outlet;
use App\Models\Region;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::where('user_type', 'staff')
            ->with(['roles', 'hub', 'region', 'outlet', 'unit'])
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
            'regions' => Region::orderBy('name')->get(),
            'outlets' => Outlet::orderBy('name')->get(),
            'units' => Unit::with('hub')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateForm($request);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'title' => $data['title'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => Hash::make($data['password']),
            'user_type' => 'staff',
            'hub_id' => $data['hub_id'],
            'region_id' => $data['region_id'],
            'outlet_id' => $data['outlet_id'],
            'unit_id' => $data['unit_id'],
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'job_title' => $data['job_title'],
            'date_joined' => $data['date_joined'],
            'employment_type' => $data['employment_type'],
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
        ]);

        if ($request->hasFile('photo')) {
            $user->update(['photo_path' => $request->file('photo')->store('staff-photos', 'public')]);
        }

        $this->assignRoleAcrossGuards($user, $data['role']);

        return redirect()->route('users.index')->with('status', 'Staff account created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->user_type === 'staff', 404);

        return view('users.form', [
            'user' => $user->load('statusAudits.changedBy', 'hub.city.state.country', 'outlet.hub.city.state.country'),
            'roles' => $this->webRoles(),
            'hubs' => Hub::orderBy('name')->get(),
            'regions' => Region::orderBy('name')->get(),
            'outlets' => Outlet::orderBy('name')->get(),
            'units' => Unit::with('hub')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->user_type === 'staff', 404);

        $data = $this->validateForm($request, $user->id);

        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'title' => $data['title'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'hub_id' => $data['hub_id'],
            'region_id' => $data['region_id'],
            'outlet_id' => $data['outlet_id'],
            'unit_id' => $data['unit_id'],
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'job_title' => $data['job_title'],
            'date_joined' => $data['date_joined'],
            'employment_type' => $data['employment_type'],
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
            ...($data['password'] ? ['password' => Hash::make($data['password'])] : []),
        ]);

        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $user->update(['photo_path' => $request->file('photo')->store('staff-photos', 'public')]);
        }

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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($ignoreUserId ? ",{$ignoreUserId}" : ''),
            'phone_number' => 'required|string|max:30',
            'photo' => 'nullable|image|max:2048',
            'password' => $ignoreUserId ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'access_scope' => 'required|in:global,region,hub,outlet',
            'region_id' => 'required_if:access_scope,region|nullable|exists:regions,id',
            'hub_id' => 'required_if:access_scope,hub|nullable|exists:hubs,id',
            'outlet_id' => 'required_if:access_scope,outlet|nullable|exists:outlets,id',
            'unit_id' => 'nullable|exists:units,id',
            'title' => 'nullable|in:Mr,Mrs,Miss,Ms,Dr,Chief,Engr,Prof,Rev,Alhaji,Alhaja',
            // Optional staff details — none required.
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:Male,Female,Prefer not to say',
            'address' => 'nullable|string|max:1000',
            'job_title' => 'nullable|string|max:255',
            'date_joined' => 'nullable|date',
            'employment_type' => 'nullable|in:full_time,part_time,contract,intern',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
        ]);

        $validator->validate();
        $data = $validator->validated();

        // HTML <select> "blank" options submit as an empty string, not
        // null — and Validator::validated() passes that through
        // unchanged. Inserting '' into a nullable foreign key column
        // fails the FK constraint (or an integer type check on strict
        // MySQL), which silently breaks the *entire* save, not just the
        // one field — this is what was blocking every staff form
        // submission where "No unit" was selected. Normalize every
        // nullable FK to true null before it goes anywhere near a query.
        foreach (['region_id', 'hub_id', 'outlet_id', 'unit_id', 'employment_type', 'title', 'gender'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        // access_scope is a form-only concept — the four levels are
        // mutually exclusive, so only ever one of region_id/hub_id/
        // outlet_id is actually stored, regardless of what was posted.
        $data['region_id'] = $data['access_scope'] === 'region' ? $data['region_id'] : null;
        $data['hub_id'] = $data['access_scope'] === 'hub' ? $data['hub_id'] : null;
        $data['outlet_id'] = $data['access_scope'] === 'outlet' ? $data['outlet_id'] : null;
        // unit_id is independent of access_scope — an organizational tag,
        // not a scope level, so it's never zeroed based on the selection.

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
