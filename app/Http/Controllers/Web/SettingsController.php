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
            'service_names' => 'required|array|min:1',
            'service_names.*' => 'required|string|max:100',
            'color_primary' => 'required|string|max:20',
            'color_secondary' => 'required|string|max:20',
            'vat_percentage' => 'required|numeric|min:0|max:100',
            'currency' => 'required|string|size:3',
            'waybill_thermal_size' => 'required|in:2x1,4x6',
            'waybill_show_qr' => 'sometimes|boolean',
            'operating_regions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['waybill_show_qr'] = $request->boolean('waybill_show_qr');
        $data['operating_regions'] = $request->filled('operating_regions')
            ? array_map('trim', explode(',', $request->operating_regions))
            : [];

        Setting::current()->update($data);

        return back()->with('status', 'Settings updated.');
    }
}
