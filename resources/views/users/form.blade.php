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
            <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="space-y-6">
                @csrf
                @if ($user->exists) @method('PUT') @endif

                <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-ink-900">Account</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Full name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">
                                {{ $user->exists ? 'New password' : 'Password' }}
                            </label>
                            <input type="password" name="password"
                                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                            @if ($user->exists)
                                <p class="mt-1 text-xs text-ink-500">Leave blank to keep the current password.</p>
                            @endif
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Role</label>
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
                        $currentScope = old('access_scope', $user->region_id ? 'region' : ($user->hub_id ? 'hub' : 'global'));
                    @endphp
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="access_scope" value="global" class="mt-1"
                                   @checked($currentScope === 'global')
                                   onchange="document.getElementById('region-field').style.display='none'; document.getElementById('hub-field').style.display='none';">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Global</span>
                                <span class="block text-xs text-ink-500">Sees and manages shipments across every hub.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="access_scope" value="region" class="mt-1"
                                   @checked($currentScope === 'region')
                                   onchange="document.getElementById('region-field').style.display=''; document.getElementById('hub-field').style.display='none';">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Region</span>
                                <span class="block text-xs text-ink-500">Sees every hub within one region.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="access_scope" value="hub" class="mt-1"
                                   @checked($currentScope === 'hub')
                                   onchange="document.getElementById('region-field').style.display='none'; document.getElementById('hub-field').style.display='';">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Specific hub (station)</span>
                                <span class="block text-xs text-ink-500">Restricted to one hub's shipments only.</span>
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
                </div>

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
