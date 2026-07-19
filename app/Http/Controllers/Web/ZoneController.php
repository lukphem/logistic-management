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
            $z->name, $z->code, $z->applies_domestic ? 'yes' : 'no', $z->applies_international ? 'yes' : 'no', $z->tier, $z->coverage_description,
        ]);

        return $this->csv->download('zones.csv', ['name', 'code', 'applies_domestic', 'applies_international', 'tier', 'coverage_description'], $rows);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $appliesDomestic = in_array(strtolower(trim($row['applies_domestic'] ?? '')), ['yes', 'true', '1']);
            $appliesInternational = in_array(strtolower(trim($row['applies_international'] ?? '')), ['yes', 'true', '1']);
            $tier = strtoupper(trim($row['tier'] ?? ''));

            if (empty($row['code']) || empty($row['name']) || (! $appliesDomestic && ! $appliesInternational)) {
                $skipped++;
                continue;
            }

            Zone::updateOrCreate(
                ['code' => strtoupper(trim($row['code']))],
                [
                    'name' => trim($row['name']),
                    'applies_domestic' => $appliesDomestic,
                    'applies_international' => $appliesInternational,
                    'tier' => ($appliesDomestic && array_key_exists($tier, Zone::TIERS)) ? $tier : null,
                    'coverage_description' => $row['coverage_description'] ?? '',
                ]
            );
            $count++;
        }

        return back()->with('status', "Imported {$count} zones" . ($skipped ? ", skipped {$skipped} (missing name/code, or neither domestic nor international checked)." : '.'));
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:zones,code,' . $request->route('zone')?->id,
            'hub_id' => 'nullable|exists:hubs,id',
            'applies_domestic' => 'sometimes|boolean',
            'applies_international' => 'sometimes|boolean',
            'tier' => 'nullable|in:' . implode(',', array_keys(\App\Models\Zone::TIERS)),
            'coverage_description' => 'required|string|max:255',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! $request->boolean('applies_domestic') && ! $request->boolean('applies_international')) {
                $validator->errors()->add('applies_domestic', 'A zone must apply to at least Domestic or International.');
            }
        });

        $data = $validator->validate();
        $data['applies_domestic'] = $request->boolean('applies_domestic');
        $data['applies_international'] = $request->boolean('applies_international');

        // Blank "— None —" option submits as an empty string, not null —
        // see the fuller explanation in UserController's matching fix.
        if (($data['hub_id'] ?? null) === '') {
            $data['hub_id'] = null;
        }
        if (($data['tier'] ?? null) === '') {
            $data['tier'] = null;
        }

        // Tier is a domestic-only refinement — a zone with no domestic
        // applicability has no A–F tariff tier, so clear it regardless
        // of what was posted (the form hides the tier field when
        // Domestic isn't checked, but don't rely on that alone).
        if (! $data['applies_domestic']) {
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
