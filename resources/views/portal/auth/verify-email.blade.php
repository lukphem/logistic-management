<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email — {{ config('branding.company_name') }}</title>
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
        <div class="w-full max-w-sm text-center">
            <div class="mb-6 flex flex-col items-center gap-3">
                @if (config('branding.logo_url'))
                    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-12 w-12 rounded-lg object-cover">
                @else
                    <span class="grid h-12 w-12 place-items-center rounded-lg bg-[var(--brand-primary)] font-mono text-lg font-bold text-white">
                        {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                    </span>
                @endif
            </div>

            <h1 class="mb-2 text-xl font-semibold text-ink-900">Check your email</h1>
            <p class="mb-6 text-sm text-ink-500">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Click it to activate your account — then come back here to sign in.</p>

            @if (session('status'))
                <div class="mb-4 rounded-md bg-status-delivered/10 px-3 py-2 text-sm text-status-delivered">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="w-full rounded-md bg-[var(--brand-primary)] py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                    Resend verification email
                </button>
            </form>

            <form method="POST" action="{{ route('portal.logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-md border border-line py-2.5 text-sm font-medium text-ink-700 hover:bg-surface-50">
                    Sign out
                </button>
            </form>
        </div>
    </div>
</body>
</html>
