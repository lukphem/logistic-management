<x-layouts.app :title="$user->exists ? 'Edit Staff User' : 'New Staff User'">

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="max-w-lg space-y-6">
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
                <label class="flex items-center gap-2 text-sm text-ink-900">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->exists ? $user->is_active : true)) class="rounded border-line">
                    Active
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="rounded-md px-4 py-2.5 text-sm font-medium text-ink-500 hover:text-ink-900">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                {{ $user->exists ? 'Save changes' : 'Create account' }}
            </button>
        </div>
    </form>

</x-layouts.app>
