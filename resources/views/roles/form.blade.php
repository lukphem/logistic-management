<x-layouts.app :title="$role->exists ? 'Edit Role — ' . $role->name : 'New Role'">

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}" class="max-w-2xl space-y-6">
        @csrf
        @if ($role->exists) @method('PUT') @endif

        @unless ($role->exists)
            <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-ink-900">Role name</h2>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Regional Supervisor"
                       class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
        @endunless

        <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Permissions</h2>
            <p class="mb-4 text-xs text-ink-500">Grouped by module. Applies identically whether the person signs into the dashboard or an API integration acting on their behalf.</p>

            <div class="space-y-5">
                @foreach ($permissions as $module => $modulePermissions)
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-sm font-medium capitalize text-ink-900">{{ $module }}</p>
                            <label class="text-xs font-medium text-[var(--brand-primary)] hover:underline">
                                <input type="checkbox" class="select-all-module" data-module="{{ $module }}" class="hidden">
                                <span onclick="toggleModule('{{ $module }}')" class="cursor-pointer">Select all</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($modulePermissions as $permission)
                                <label class="flex items-center gap-2 rounded-md border border-line px-3 py-2 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                           data-module="{{ $module }}"
                                           @checked(in_array($permission->name, old('permissions', $assigned)))
                                           class="rounded border-line">
                                    {{ explode(':', $permission->name)[1] ?? $permission->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('roles.index') }}" class="rounded-md px-4 py-2.5 text-sm font-medium text-ink-500 hover:text-ink-900">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                {{ $role->exists ? 'Save permissions' : 'Create role' }}
            </button>
        </div>
    </form>

    <script>
        function toggleModule(moduleName) {
            const boxes = document.querySelectorAll('input[type="checkbox"][data-module="' + moduleName + '"]:not(.select-all-module)');
            const anyUnchecked = Array.from(boxes).some(function (box) { return !box.checked; });
            boxes.forEach(function (box) { box.checked = anyUnchecked; });
        }
    </script>

</x-layouts.app>
