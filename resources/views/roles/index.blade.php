<x-layouts.app :title="'Roles & Permissions'">

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
        <p class="text-sm text-ink-500">The five default roles are protected from deletion — edit their permissions instead of removing them.</p>
        <a href="{{ route('roles.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add custom role
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Permissions</th>
                    <th class="px-5 py-3 font-medium">Staff assigned</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3">
                            <p class="font-medium text-ink-900">{{ $role->name }}</p>
                            @if (in_array($role->name, $protectedRoles))
                                <span class="text-xs text-ink-500">System default</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-ink-500">{{ $role->permissions_count }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $role->users_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('roles.edit', $role) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            @unless (in_array($role->name, $protectedRoles))
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline" onsubmit="return confirm('Remove this role? Staff assigned to it will lose these permissions.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">No roles configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layouts.app>
