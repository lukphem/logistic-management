<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\Route;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function index(): View
    {
        $routes = Route::with('hub')->withCount(['cities', 'districts'])->orderBy('name')->paginate(15);

        return view('routes.index', compact('routes'));
    }

    public function create(): View
    {
        return view('routes.form', ['route' => new Route(), 'hubs' => Hub::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Route::create($this->validated($request));

        return redirect()->route('routes.index')->with('status', 'Route added.');
    }

    public function edit(Route $route): View
    {
        return view('routes.form', ['route' => $route, 'hubs' => Hub::orderBy('name')->get()]);
    }

    public function update(Request $request, Route $route): RedirectResponse
    {
        $route->update($this->validated($request, $route->id));

        return redirect()->route('routes.index')->with('status', 'Route updated.');
    }

    public function destroy(Route $route): RedirectResponse
    {
        $route->delete();

        return redirect()->route('routes.index')->with('status', 'Route removed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:routes,code,' . $ignoreId,
            'hub_id' => 'nullable|exists:hubs,id',
        ]);

        $validator->validate();
        $data = $validator->validated();

        if (($data['hub_id'] ?? null) === '') {
            $data['hub_id'] = null;
        }

        return $data;
    }
}
