<x-layouts.app :title="'System Setup'">

    @if (session('status'))
        <div class="mb-5 rounded-md bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            <p class="font-medium">Please fix the following:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Company Profile --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-4 text-sm font-semibold text-ink-900">Company profile</h2>
            <div class="mb-4 flex items-center gap-4">
                @if ($settings->logo_url)
                    <img src="{{ $settings->logo_url }}" alt="Current logo" class="h-14 w-14 rounded-md border border-line object-cover">
                @endif
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Logo</label>
                    <input type="file" name="logo" accept="image/*"
                           class="text-sm text-ink-500 file:mr-3 file:rounded-md file:border-0 file:bg-surface-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ink-900 hover:file:bg-line">
                    <p class="mt-1 text-xs text-ink-500">PNG or JPG, up to 2MB.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Business name <x-required /></label>
                    <input type="text" name="company_name" value="{{ old('company_name', $settings->company_name) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Operating regions</label>
                    <input type="text" name="operating_regions"
                           value="{{ old('operating_regions', implode(', ', $settings->operating_regions ?? [])) }}"
                           placeholder="Lagos, Abuja, Port Harcourt"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <p class="mt-1 text-xs text-ink-500">Comma-separated. Feeds hub/zone setup elsewhere.</p>
                </div>
            </div>
        </div>

        {{-- Service Names --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Service names</h2>
            <p class="mb-4 text-xs text-ink-500">These map to the <code class="font-mono">service_type</code> used when configuring rate cards.</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @php $serviceNames = old('service_names', $settings->service_names ?? []); @endphp
                @foreach (['express' => 'Express', 'same_day' => 'Same-Day', 'economy' => 'Economy'] as $key => $default)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">{{ $default }} label <x-required /></label>
                        <input type="text" name="service_names[{{ $key }}]" value="{{ $serviceNames[$key] ?? $default }}"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Branding --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-4 text-sm font-semibold text-ink-900">Branding</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Primary color <x-required /></label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="color_primary" value="{{ old('color_primary', $settings->color_primary) }}"
                               class="h-10 w-14 cursor-pointer rounded-md border border-line">
                        <span class="font-mono text-sm text-ink-500">{{ old('color_primary', $settings->color_primary) }}</span>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Secondary color <x-required /></label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="color_secondary" value="{{ old('color_secondary', $settings->color_secondary) }}"
                               class="h-10 w-14 cursor-pointer rounded-md border border-line">
                        <span class="font-mono text-sm text-ink-500">{{ old('color_secondary', $settings->color_secondary) }}</span>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs text-ink-500">Applied across the dashboard, client portal, and waybill templates as soon as saved.</p>
        </div>

        {{-- Login Design --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Login page design</h2>
            <p class="mb-4 text-xs text-ink-500">Changes the illustration panel shown on the staff sign-in page. Your brand colors above still apply to whichever one is selected.</p>

            @php $currentDesign = old('login_design', $settings->login_design ?? 'route'); @endphp
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach (\App\Models\Setting::LOGIN_DESIGNS as $key => $design)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="login_design" value="{{ $key }}" class="mt-1" @checked($currentDesign === $key)>
                        <span>
                            <span class="block text-sm font-medium text-ink-900">{{ $design['label'] }}</span>
                            <span class="block text-xs text-ink-500">{{ $design['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Supported Billing Models --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Supported billing models</h2>
            <p class="mb-4 text-xs text-ink-500">
                Which billing models this business actually uses. This is a reference list only right now — each
                model gets built and configured individually before it can actually be used on a rate card.
            </p>

            @php $selectedModels = old('supported_billing_models', $settings->supported_billing_models ?? array_keys(\App\Models\Setting::BILLING_MODELS)); @endphp
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach (\App\Models\Setting::BILLING_MODELS as $key => $label)
                    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="checkbox" name="supported_billing_models[]" value="{{ $key }}" class="mt-0.5 rounded border-line" @checked(in_array($key, $selectedModels))>
                        <span class="text-sm text-ink-900">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Billing Defaults --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-4 text-sm font-semibold text-ink-900">Billing defaults</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">VAT percentage <x-required /></label>
                    <input type="number" step="0.01" name="vat_percentage" value="{{ old('vat_percentage', $settings->vat_percentage) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Currency (ISO code) <x-required /></label>
                    <input type="text" name="currency" maxlength="3" value="{{ old('currency', $settings->currency) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
            </div>
        </div>

        {{-- Invoicing --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Invoicing</h2>
            <p class="mb-4 text-xs text-ink-500">Shown on generated invoice documents, above and below the line items.</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Invoice header</label>
                    <textarea name="invoice_header" rows="3" placeholder="Business address, tax ID, etc."
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('invoice_header', $settings->invoice_header) }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Invoice footer</label>
                    <textarea name="invoice_footer" rows="3" placeholder="Payment terms, bank details, thank-you note, etc."
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('invoice_footer', $settings->invoice_footer) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Waybill --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-4 text-sm font-semibold text-ink-900">Waybill design</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Thermal label size <x-required /></label>
                    <select name="waybill_thermal_size"
                            class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach (['4x6' => '4×6"', '2x1' => '2×1"'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('waybill_thermal_size', $settings->waybill_thermal_size) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-sm text-ink-900">
                        <input type="checkbox" name="waybill_show_qr" value="1"
                               @checked(old('waybill_show_qr', $settings->waybill_show_qr)) class="rounded border-line">
                        Show QR code on waybill
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                Save settings
            </button>
        </div>
    </form>

</x-layouts.app>
