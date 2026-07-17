<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Territory;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TerritoryController extends Controller
{
    public function __construct(private CsvService $csv)
    {
    }

    public function index(): View
    {
        $territories = Territory::withCount('states')->orderBy('name')->paginate(15);

        return view('territories.index', compact('territories'));
    }

    public function create(): View
    {
        return view('territories.form', ['territory' => new Territory()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Territory::create($this->validated($request));

        return redirect()->route('territories.index')->with('status', 'Territory added.');
    }

    public function edit(Territory $territory): View
    {
        return view('territories.form', compact('territory'));
    }

    public function update(Request $request, Territory $territory): RedirectResponse
    {
        $territory->update($this->validated($request));

        return redirect()->route('territories.index')->with('status', 'Territory updated.');
    }

    public function destroy(Territory $territory): RedirectResponse
    {
        $territory->delete();

        return redirect()->route('territories.index')->with('status', 'Territory removed.');
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = Territory::orderBy('name')->get()->map(fn ($t) => [$t->name, $t->code]);

        return $this->csv->download('territories.csv', ['name', 'code'], $rows);
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

            Territory::updateOrCreate(
                ['code' => strtoupper(trim($row['code']))],
                ['name' => trim($row['name'])]
            );
            $count++;
        }

        return back()->with('status', "Imported {$count} territories.");
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:territories,code,' . $request->route('territory')?->id,
        ]);

        $validator->validate();

        return $validator->validated();
    }
}
