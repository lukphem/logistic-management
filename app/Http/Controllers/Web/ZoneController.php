<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\Zone;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ZoneController extends Controller
{
    public function __construct(private CsvService $csv)
    {
    }

    public function index(): View
    {
        $zones = Zone::with('hub')->orderBy('name')->paginate(15);

        return view('zones.index', compact('zones'));
    }

    public function create(): View
    {
        $hubs = Hub::orderBy('name')->get();

        return view('zones.form', ['zone' => new Zone(), 'hubs' => $hubs]);
    }

    public function store(Request $request): RedirectResponse
    {
        Zone::create($this->validated($request));

        return redirect()->route('zones.index')->with('status', 'Zone added.');
    }

    public function edit(Zone $zone): View
    {
        $hubs = Hub::orderBy('name')->get();

        return view('zones.form', compact('zone', 'hubs'));
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $zone->update($this->validated($request));

        return redirect()->route('zones.index')->with('status', 'Zone updated.');
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        $zone->delete();

        return redirect()->route('zones.index')->with('status', 'Zone removed.');
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = Zone::orderBy('name')->get()->map(fn ($z) => [
            $z->name, $z->code, $z->type, $z->tier, $z->coverage_description,
        ]);

        return $this->csv->download('zones.csv', ['name', 'code', 'type', 'tier', 'coverage_description'], $rows);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $type = strtolower(trim($row['type'] ?? 'domestic'));
            $tier = strtoupper(trim($row['tier'] ?? ''));

            if (empty($row['code']) || empty($row['name']) || ! array_key_exists($type, Zone::TYPES)) {
                $skipped++;
                continue;
            }

            Zone::updateOrCreate(
                ['code' => strtoupper(trim($row['code']))],
                [
                    'name' => trim($row['name']),
                    'type' => $type,
                    'tier' => ($type === 'domestic' && array_key_exists($tier, Zone::TIERS)) ? $tier : null,
                    'coverage_description' => $row['coverage_description'] ?? '',
                ]
            );
            $count++;
        }

        return back()->with('status', "Imported {$count} zones" . ($skipped ? ", skipped {$skipped} (missing name/code or invalid type)." : '.'));
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:zones,code,' . $request->route('zone')?->id,
            'hub_id' => 'nullable|exists:hubs,id',
            'type' => 'required|in:' . implode(',', array_keys(\App\Models\Zone::TYPES)),
            'tier' => 'nullable|in:' . implode(',', array_keys(\App\Models\Zone::TIERS)),
            'coverage_description' => 'required|string|max:255',
        ]);

        $data = $validator->validate();

        // Blank "— None —" option submits as an empty string, not null —
        // see the fuller explanation in UserController's matching fix.
        if (($data['hub_id'] ?? null) === '') {
            $data['hub_id'] = null;
        }
        if (($data['tier'] ?? null) === '') {
            $data['tier'] = null;
        }

        // Tier is a domestic-only refinement — an international zone
        // grouped by region has no A–F tariff tier, so clear it
        // regardless of what was posted (the form hides the tier field
        // for international zones, but don't rely on that alone).
        if ($data['type'] === 'international') {
            $data['tier'] = null;
        }

        // Suggest the tier's standard coverage description when none was
        // typed — staff can always override it.
        if (empty($data['coverage_description']) && $data['tier']) {
            $data['coverage_description'] = \App\Models\Zone::TIERS[$data['tier']]['coverage'];
        }

        return $data;
    }
}
