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
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b border-line last:border-0 hover:bg-surface-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $user->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $user->email }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $user->roles->pluck('name')->unique()->join(', ') ?: '—' }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->is_active ? 'bg-status-delivered/10 text-status-delivered' : 'bg-ink-500/10 text-ink-500' }}">
                                {{ $user->is_active ? 'Active' : 'Deactivated' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('users.edit', $user) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="ml-3 text-sm font-medium text-ink-500 hover:underline">
                                    {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No staff accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>

</x-layouts.app>
