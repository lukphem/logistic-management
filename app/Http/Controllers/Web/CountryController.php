<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function __construct(private CsvService $csv)
    {
    }

    public function index(): View
    {
        $countries = Country::withCount('states')->orderBy('name')->paginate(15);

        return view('countries.index', compact('countries'));
    }

    public function create(): View
    {
        return view('countries.form', ['country' => new Country()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Country::create($this->validated($request));

        return redirect()->route('countries.index')->with('status', 'Country added.');
    }

    public function edit(Country $country): View
    {
        return view('countries.form', compact('country'));
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $country->update($this->validated($request));

        return redirect()->route('countries.index')->with('status', 'Country updated.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        return redirect()->route('countries.index')->with('status', 'Country removed.');
    }

    /**
     * Downloads every existing country — the same file, amended, can be
     * re-uploaded via import() to update in bulk.
     */
    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = Country::orderBy('name')->get()->map(fn ($c) => [$c->name, $c->code]);

        return $this->csv->download('countries.csv', ['name', 'code'], $rows);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;

        foreach ($rows as $row) {
            if (empty($row['code']) || empty($row['name'])) {
                continue;
            }

            Country::updateOrCreate(
                ['code' => strtoupper(trim($row['code']))],
                ['name' => trim($row['name'])]
            );
            $count++;
        }

        return back()->with('status', "Imported {$count} countries.");
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:3|unique:countries,code,' . $request->route('country')?->id,
        ]);

        $validator->validate();

        return $validator->validated();
    }
}
