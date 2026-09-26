<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create an account — {{ config('branding.company_name') }}</title>
    <style>
        :root {
            --brand-primary: {{ config('branding.colors.primary') }};
            --brand-secondary: {{ config('branding.colors.secondary') }};
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-surface-50 text-ink-900 antialiased">
    <div class="grid h-full place-items-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-6 flex flex-col items-center gap-3">
                @if (config('branding.logo_url'))
                    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-12 w-12 rounded-lg object-cover">
                @else
                    <span class="grid h-12 w-12 place-items-center rounded-lg bg-[var(--brand-primary)] font-mono text-lg font-bold text-white">
                        {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                    </span>
                @endif
                <p class="text-sm font-medium text-ink-500">{{ config('branding.company_name') }} — Client Portal</p>
            </div>

            <h1 class="mb-1 text-xl font-semibold text-ink-900 text-center">Create your account</h1>
            <p class="mb-6 text-center text-sm text-ink-500">For an organization account, sign up here first — you can request an upgrade once you're in.</p>

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-status-exception/10 px-3 py-2 text-sm text-status-exception">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.register.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-ink-900">Full name <x-required /></label>
                    <input id="name" name="name" type="text" required autofocus value="{{ old('name') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-ink-900">Email <x-required /></label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label for="phone_number" class="mb-1 block text-sm font-medium text-ink-900">Phone number <x-required /></label>
                    <input id="phone_number" name="phone_number" type="text" required value="{{ old('phone_number') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="id_type" class="mb-1 block text-sm font-medium text-ink-900">ID type <x-required /></label>
                        <select id="id_type" name="id_type" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                            <option value="">— Select —</option>
                            <option value="national_id" @selected(old('id_type') === 'national_id')>National ID</option>
                            <option value="passport" @selected(old('id_type') === 'passport')>Passport</option>
                            <option value="drivers_license" @selected(old('id_type') === 'drivers_license')>Driver's License</option>
                            <option value="voters_card" @selected(old('id_type') === 'voters_card')>Voter's Card</option>
                        </select>
                    </div>
                    <div>
                        <label for="id_number" class="mb-1 block text-sm font-medium text-ink-900">ID number <x-required /></label>
                        <input id="id_number" name="id_number" type="text" required value="{{ old('id_number') }}"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-ink-900">Password <x-required /></label>
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-md border border-line px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-ink-900">Confirm password <x-required /></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="w-full rounded-md border border-line px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <button type="submit"
                        class="w-full rounded-md bg-[var(--brand-primary)] py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                    Create account
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-ink-500">
                Already have an account? <a href="{{ route('portal.login') }}" class="font-medium text-[var(--brand-primary)] hover:underline">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>
