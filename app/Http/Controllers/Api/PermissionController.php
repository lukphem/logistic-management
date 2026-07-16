<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Read-only — the permission list is seeded (see PermissionSeeder),
     * not created ad hoc through the API.
     */
    public function index(): JsonResponse
    {
        return response()->json(Permission::all());
    }
}
