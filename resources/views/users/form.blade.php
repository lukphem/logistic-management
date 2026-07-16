<x-layouts.app :title="$user->exists ? 'Manage — ' . $user->name : 'New Staff User'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @if ($user->exists) @method('PUT') @endif

                <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-ink-900">Account</h2>

                    @if ($user->exists)
                        <div class="mb-4 flex items-center gap-4">
                            <span class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-full bg-surface-50 ring-1 ring-line">
                                @if ($user->photo_url)
                                    <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                @else
                                    <span class="text-lg font-semibold text-ink-500">{{ strtoupper(substr($user->first_name ?? $user->name, 0, 1)) }}</span>
                                @endif
                            </span>
                            <div>
                                <p class="font-mono text-xs text-ink-500">{{ $user->staff_id }}</p>
                                <label class="mt-1 inline-block cursor-pointer text-sm font-medium text-[var(--brand-primary)] hover:underline">
                                    Change photo
                                    <input type="file" name="photo" accept="image/*" class="hidden">
                                </label>
                            </div>
                        </div>
                    @else
                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-medium text-ink-900">Photo</label>
                            <input type="file" name="photo" accept="image/*"
                                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <p class="mt-1 text-xs text-ink-500">Optional. A staff ID is generated automatically once created.</p>
                        </div>
                    @endif

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Title</label>
                            <select name="title" class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                <option value="">—</option>
                                @foreach (['Mr', 'Mrs', 'Miss', 'Ms', 'Dr', 'Chief', 'Engr', 'Prof', 'Rev', 'Alhaji', 'Alhaja'] as $titleOption)
                                    <option value="{{ $titleOption }}" @selected(old('title', $user->title) === $titleOption)>{{ $titleOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">First name <x-required /></label>
                                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Last name <x-required /></label>
                                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Email <x-required /></label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Phone number <x-required /></label>
                                <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">
                                {{ $user->exists ? 'New password' : 'Password' }}
                                @unless ($user->exists) <x-required /> @endunless
                            </label>
                            <input type="password" name="password"
                                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                            @if ($user->exists)
                                <p class="mt-1 text-xs text-ink-500">Leave blank to keep the current password.</p>
                            @endif
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Role <x-required /></label>
                            <select name="role" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                @php $currentRole = $user->roles->first()?->name; @endphp
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}" @selected(old('role', $currentRole) === $role->name)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
                    <h2 class="mb-1 text-sm font-semibold text-ink-900">Access scope</h2>
                    <p class="mb-4 text-xs text-ink-500">Controls which hub(s) this person can see and act on shipments for.</p>

                    @php
                        $currentScope = old('access_scope', $user->outlet_id ? 'outlet' : ($user->region_id ? 'region' : ($user->hub_id ? 'hub' : 'global')));
                    @endphp
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="access_scope" value="global" class="mt-1"
                                   @checked($currentScope === 'global')
                                   onchange="document.getElementById('region-field').style.display='none'; document.getElementById('hub-field').style.display='none'; document.getElementById('outlet-field').style.display='none'; document.getElementById('unit-field').style.display='none';">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Global</span>
                                <span class="block text-xs text-ink-500">Sees and manages shipments across every hub.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="access_scope" value="region" class="mt-1"
                                   @checked($currentScope === 'region')
                                   onchange="document.getElementById('region-field').style.display=''; document.getElementById('hub-field').style.display='none'; document.getElementById('outlet-field').style.display='none'; document.getElementById('unit-field').style.display='none';">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Region</span>
                                <span class="block text-xs text-ink-500">Sees every hub within one region.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="access_scope" value="hub" class="mt-1"
                                   @checked($currentScope === 'hub')
                                   onchange="document.getElementById('region-field').style.display='none'; document.getElementById('hub-field').style.display=''; document.getElementById('outlet-field').style.display='none'; document.getElementById('unit-field').style.display='';">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Specific hub (station)</span>
                                <span class="block text-xs text-ink-500">Restricted to one hub's shipments only.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="access_scope" value="outlet" class="mt-1"
                                   @checked($currentScope === 'outlet')
                                   onchange="document.getElementById('region-field').style.display='none'; document.getElementById('hub-field').style.display='none'; document.getElementById('outlet-field').style.display=''; document.getElementById('unit-field').style.display='';">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Specific outlet</span>
                                <span class="block text-xs text-ink-500">Restricted to shipments physically at that outlet.</span>
                            </span>
                        </label>
                    </div>

                    <div id="region-field" class="mt-4" style="{{ $currentScope === 'region' ? '' : 'display:none' }}">
                        <label class="mb-1 block text-sm font-medium text-ink-900">Region</label>
                        <select name="region_id" class="w-full max-w-xs rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}" @selected(old('region_id', $user->region_id) == $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="hub-field" class="mt-4" style="{{ $currentScope === 'hub' ? '' : 'display:none' }}">
                        <label class="mb-1 block text-sm font-medium text-ink-900">Hub</label>
                        <select name="hub_id" class="w-full max-w-xs rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($hubs as $hub)
                                <option value="{{ $hub->id }}" @selected(old('hub_id', $user->hub_id) == $hub->id)>{{ $hub->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="outlet-field" class="mt-4" style="{{ $currentScope === 'outlet' ? '' : 'display:none' }}">
                        <label class="mb-1 block text-sm font-medium text-ink-900">Outlet</label>
                        <select name="outlet_id" class="w-full max-w-xs rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $user->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="unit-field" class="mt-4" style="{{ in_array($currentScope, ['hub', 'outlet']) ? '' : 'display:none' }}">
                        <label class="mb-1 block text-sm font-medium text-ink-900">Unit (optional)</label>
                        <select name="unit_id" class="w-full max-w-xs rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">No unit</option>
                            @foreach ($units->groupBy('hub_id') as $hubUnits)
                                <optgroup label="{{ $hubUnits->first()->hub->name }}">
                                    @foreach ($hubUnits as $unit)
                                        <option value="{{ $unit->id }}" @selected(old('unit_id', $user->unit_id) == $unit->id)>{{ $unit->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-ink-500">Which team within the hub — never changes what shipments they can see.</p>
                    </div>

                    @php
                        $resolvedHub = $user->hasOutletAccess() ? $user->outlet?->hub : ($user->hub ?? null);
                        $resolvedCity = $resolvedHub?->city;
                    @endphp
                    @if ($user->exists && $resolvedCity)
                        <div class="mt-4 rounded-lg border border-line bg-surface-50 p-3">
                            <p class="text-xs font-medium text-ink-500">Operating location</p>
                            <p class="text-sm text-ink-900">
                                {{ $resolvedCity->name }}, {{ $resolvedCity->state->name }}, {{ $resolvedCity->state->country->name }}
                            </p>
                            <p class="mt-0.5 text-xs text-ink-500">Resolved from {{ $resolvedHub->name }}'s city — set under Setups → Location → Hubs.</p>
                        </div>
                    @endif
                </div>

                <details class="group rounded-xl border border-line bg-surface-0 shadow-sm">
                    <summary class="flex cursor-pointer items-center justify-between p-5 text-sm font-semibold text-ink-900">
                        Additional details <span class="text-xs font-normal text-ink-500">(optional)</span>
                        <x-icon name="chevron" class="h-4 w-4 shrink-0 text-ink-500 transition-transform group-open:rotate-180" />
                    </summary>
                    <div class="space-y-4 border-t border-line p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Date of birth</label>
                                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Gender</label>
                                <select name="gender" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    <option value="">—</option>
                                    @foreach (['Male', 'Female', 'Prefer not to say'] as $genderOption)
                                        <option value="{{ $genderOption }}" @selected(old('gender', $user->gender) === $genderOption)>{{ $genderOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Address</label>
                            <textarea name="address" rows="2"
                                      class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">{{ old('address', $user->address) }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Job title</label>
                                <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. Dispatch Supervisor"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Employment type</label>
                                <select name="employment_type" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    <option value="">—</option>
                                    @foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'intern' => 'Intern'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('employment_type', $user->employment_type) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Date joined</label>
                            <input type="date" name="date_joined" value="{{ old('date_joined', $user->date_joined?->format('Y-m-d')) }}"
                                   class="w-full max-w-xs rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Emergency contact name</label>
                                <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $user->emergency_contact_name) }}"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink-900">Emergency contact phone</label>
                                <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $user->emergency_contact_phone) }}"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                        </div>
                    </div>
                </details>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('users.index') }}" class="rounded-md px-4 py-2.5 text-sm font-medium text-ink-500 hover:text-ink-900">Cancel</a>
                    <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                        {{ $user->exists ? 'Save changes' : 'Create account' }}
                    </button>
                </div>
            </form>
        </div>

        @if ($user->exists)
            <div class="space-y-6">
                {{-- Status management --}}
                <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
                    <h2 class="mb-1 text-sm font-semibold text-ink-900">Account status</h2>
                    @php
                        $statusStyles = [
                            'active' => 'bg-status-delivered/10 text-status-delivered',
                            'suspended' => 'bg-status-pending/10 text-status-pending',
                            'locked' => 'bg-status-exception/10 text-status-exception',
                            'terminated' => 'bg-ink-500/10 text-ink-500',
                        ];
                    @endphp
                    <span class="mb-4 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$user->account_status] }}">
                        {{ ucfirst($user->account_status) }}
                    </span>

                    @if ($user->id === auth()->id())
                        <p class="text-xs text-ink-500">You can't change your own account's status.</p>
                    @else
                        <div class="space-y-2">
                            @if ($user->account_status !== 'active')
                                <button type="button" onclick="document.getElementById('status-form-active').classList.toggle('hidden')"
                                        class="w-full rounded-md border border-line px-3 py-2 text-sm font-medium text-status-delivered hover:bg-status-delivered/5">
                                    Reactivate
                                </button>
                                <form id="status-form-active" method="POST" action="{{ route('users.change-status', [$user, 'active']) }}" class="hidden space-y-2 pt-1">
                                    @csrf @method('PATCH')
                                    <input type="text" name="reason" placeholder="Reason (optional)" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    <button type="submit" class="w-full rounded-md bg-status-delivered px-3 py-2 text-sm font-medium text-white hover:opacity-90">Confirm reactivate</button>
                                </form>
                            @endif

                            @foreach (['suspended' => 'Suspend', 'locked' => 'Lock', 'terminated' => 'Terminate'] as $statusValue => $label)
                                @if ($user->account_status !== $statusValue)
                                    <button type="button" onclick="document.getElementById('status-form-{{ $statusValue }}').classList.toggle('hidden')"
                                            class="w-full rounded-md border border-line px-3 py-2 text-sm font-medium text-status-exception hover:bg-status-exception/5">
                                        {{ $label }}
                                    </button>
                                    <form id="status-form-{{ $statusValue }}" method="POST" action="{{ route('users.change-status', [$user, $statusValue]) }}" class="hidden space-y-2 pt-1">
                                        @csrf @method('PATCH')
                                        <input type="text" name="reason" placeholder="Reason (required)" required
                                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                        <button type="submit" class="w-full rounded-md bg-status-exception px-3 py-2 text-sm font-medium text-white hover:opacity-90">Confirm {{ strtolower($label) }}</button>
                                    </form>
                                @endif
                            @endforeach

                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Permanently delete this account? This cannot be undone.')" class="pt-2">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full text-sm font-medium text-ink-500 hover:text-status-exception hover:underline">
                                    Delete permanently
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                {{-- Audit history --}}
                <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-ink-900">Status history</h2>
                    <div class="space-y-4">
                        @forelse ($user->statusAudits as $audit)
                            <div class="border-l-2 border-line pl-3">
                                <p class="text-sm text-ink-900">
                                    <span class="font-medium">{{ ucfirst($audit->from_status) }}</span>
                                    → <span class="font-medium">{{ ucfirst($audit->to_status) }}</span>
                                </p>
                                @if ($audit->reason)
                                    <p class="text-xs text-ink-500">"{{ $audit->reason }}"</p>
                                @endif
                                <p class="mt-0.5 text-xs text-ink-500">
                                    {{ $audit->created_at->format('d M Y, H:i') }}
                                    @if ($audit->changedBy) · by {{ $audit->changedBy->name }} @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">No status changes recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>

</x-layouts.app>
