<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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
        $zones = Zone::orderBy('name')->paginate(15);

        return view('zones.index', compact('zones'));
    }

    public function create(): View
    {
        return view('zones.form', ['zone' => new Zone()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Zone::create($this->validated($request));

        return redirect()->route('zones.index')->with('status', 'Zone added.');
    }

    public function edit(Zone $zone): View
    {
        return view('zones.form', compact('zone'));
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

    /**
     * The form submits a single 'applies_to' choice (domestic /
     * international / both) rather than two independent checkboxes —
     * clearer as one explicit decision than two boxes that happen to
     * combine. Still stored as the two underlying booleans, so nothing
     * downstream (PricingEngine, the Zone Mapping pickers) needs to
     * know about 'applies_to' at all.
     */
    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:zones,code,' . $request->route('zone')?->id,
            'applies_to' => 'required|in:domestic,international,both',
            'tier' => 'nullable|in:' . implode(',', array_keys(\App\Models\Zone::TIERS)),
            'coverage_description' => 'required|string|max:255',
        ]);

        $data = $validator->validate();

        $data['applies_domestic'] = in_array($data['applies_to'], ['domestic', 'both']);
        $data['applies_international'] = in_array($data['applies_to'], ['international', 'both']);
        unset($data['applies_to']);

        if (($data['tier'] ?? null) === '') {
            $data['tier'] = null;
        }

        // Tier is a domestic-only refinement — a zone with no domestic
        // applicability has no A–F tariff tier, so clear it regardless
        // of what was posted (the form hides the tier field when
        // Domestic isn't part of the selection, but don't rely on that
        // alone).
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
