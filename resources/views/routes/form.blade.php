<x-layouts.app :title="$route->exists ? 'Edit Route' : 'Add Route'">

    <form method="POST" action="{{ $route->exists ? route('routes.update', $route) : route('routes.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($route->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Route name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $route->name) }}" placeholder="e.g. Ikeja Morning Route"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $route->code) }}" placeholder="e.g. IKJ-R1"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Hub <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <select name="hub_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">— None —</option>
                @foreach ($hubs as $hub)
                    <option value="{{ $hub->id }}" @selected(old('hub_id', $route->hub_id) == $hub->id)>{{ $hub->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('routes.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $route->exists ? 'Save changes' : 'Add route' }}
            </button>
        </div>
    </form>

</x-layouts.app>
