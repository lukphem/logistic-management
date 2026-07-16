<x-layouts.app :title="'Staff Users'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-ink-500">Dashboard accounts — riders and clients are managed separately, not here.</p>
        <a href="{{ route('users.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white hover:opacity-90">
            + Add staff user
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Access</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $statusStyles = [
                            'active' => 'bg-status-delivered/10 text-status-delivered',
                            'suspended' => 'bg-status-pending/10 text-status-pending',
                            'locked' => 'bg-status-exception/10 text-status-exception',
                            'terminated' => 'bg-ink-500/10 text-ink-500',
                        ];
                    @endphp
                    <tr class="border-b border-line last:border-0 hover:bg-surface-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $user->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $user->email }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $user->roles->pluck('name')->unique()->join(', ') ?: '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">
                            @if ($user->hasGlobalAccess())
                                Global
                            @elseif ($user->hasRegionAccess())
                                {{ $user->region?->name ?? '—' }} <span class="text-xs">(region)</span>
                            @elseif ($user->hasOutletAccess())
                                {{ $user->outlet?->name ?? '—' }} <span class="text-xs">(outlet)</span>
                            @else
                                {{ $user->hub?->name ?? '—' }} <span class="text-xs">(hub)</span>
                            @endif
                            @if ($user->unit)
                                <span class="block text-xs text-ink-500">Unit: {{ $user->unit->name }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$user->account_status] ?? 'bg-ink-500/10 text-ink-500' }}">
                                {{ ucfirst($user->account_status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('users.edit', $user) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-ink-500">No staff accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>

</x-layouts.app>
