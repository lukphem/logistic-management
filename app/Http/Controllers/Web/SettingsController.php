<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $settings = Setting::current();

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'color_primary' => 'required|string|max:20',
            'color_secondary' => 'required|string|max:20',
            'login_design' => 'required|in:' . implode(',', array_keys(Setting::LOGIN_DESIGNS)),
            'supported_billing_models' => 'nullable|array',
            'supported_billing_models.*' => 'in:' . implode(',', array_keys(Setting::BILLING_MODELS)),
            'vat_percentage' => 'required|numeric|min:0|max:100',
            'currency' => 'required|string|size:3',
            'waybill_thermal_size' => 'required|in:2x1,4x6',
            'waybill_show_qr' => 'sometimes|boolean',
            'operating_regions' => 'nullable|string',
            'invoice_header' => 'nullable|string|max:2000',
            'invoice_footer' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        unset($data['logo']);

        $data['waybill_show_qr'] = $request->boolean('waybill_show_qr');
        $data['supported_billing_models'] = $data['supported_billing_models'] ?? [];
        $data['operating_regions'] = $request->filled('operating_regions')
            ? array_map('trim', explode(',', $request->operating_regions))
            : [];

        $settings = Setting::current();

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        $settings->update($data);

        return back()->with('status', 'Settings updated.');
    }
}
