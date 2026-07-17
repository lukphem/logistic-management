<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\OnforwardingClassification;
use App\Models\Route;
use App\Models\State;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function __construct(private CsvService $csv)
    {
    }

    public function index(Request $request): View
    {
        $query = District::with(['city.state.country', 'onforwardingClassification']);

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        } elseif ($request->filled('state_id')) {
            $query->whereHas('city', fn ($q) => $q->where('state_id', $request->state_id));
        }

        $districts = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('districts.index', [
            'districts' => $districts,
            'states' => State::orderBy('name')->get(),
            'cities' => City::with('state')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('districts.form', [
            'district' => new District(),
            'cities' => City::with('state.country')->orderBy('name')->get(),
            'classifications' => OnforwardingClassification::orderBy('name')->get(),
            'routes' => Route::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        District::create($this->validated($request));

        return redirect()->route('districts.index')->with('status', 'District/area added.');
    }

    public function edit(District $district): View
    {
        return view('districts.form', [
            'district' => $district,
            'cities' => City::with('state.country')->orderBy('name')->get(),
            'classifications' => OnforwardingClassification::orderBy('name')->get(),
            'routes' => Route::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, District $district): RedirectResponse
    {
        $district->update($this->validated($request, $district->id));

        return redirect()->route('districts.index')->with('status', 'District/area updated.');
    }

    public function destroy(District $district): RedirectResponse
    {
        $district->delete();

        return redirect()->route('districts.index')->with('status', 'District/area removed.');
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = District::with('city')->orderBy('name')->get()->map(fn ($d) => [
            $d->city->code,
            $d->name,
            $d->short_code,
            $d->postal_code,
        ]);

        return $this->csv->download('districts.csv', ['city_code', 'name', 'short_code', 'postal_code'], $rows);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $city = City::where('code', strtoupper(trim($row['city_code'] ?? '')))->first();

            if (! $city || empty($row['name']) || empty($row['short_code'])) {
                $skipped++;
                continue;
            }

            District::updateOrCreate(
                ['city_id' => $city->id, 'short_code' => strtoupper(trim($row['short_code']))],
                ['name' => trim($row['name']), 'postal_code' => $row['postal_code'] ?? null]
            );
            $count++;
        }

        return back()->with('status', "Imported {$count} districts" . ($skipped ? ", skipped {$skipped} (unknown city code or missing name/short code)." : '.'));
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255',
            'short_code' => [
                'required', 'string', 'max:10',
                Rule::unique('districts', 'short_code')
                    ->where('city_id', $request->city_id)
                    ->ignore($ignoreId),
            ],
            'onforwarding_classification_id' => 'nullable|exists:onforwarding_classifications,id',
            'route_id' => 'nullable|exists:routes,id',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $validator->validate();
        $data = $validator->validated();

        // Blank "No classification"/"No route" options submit as an
        // empty string, not null — normalize immediately, the same
        // class of bug fixed in Increment 20.
        foreach (['onforwarding_classification_id', 'route_id'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
