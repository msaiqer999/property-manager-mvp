<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Building;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BuildingController extends Controller
{
    use ScopesOrganization;

    public function index()
    {
        Gate::authorize('viewAny', Building::class);

        $buildings = Building::where('organization_id', $this->organizationId())->latest()->paginate(15);

        return view('buildings.index', compact('buildings'));
    }

    public function create()
    {
        Gate::authorize('create', Building::class);

        return view('buildings.form', ['building' => new Building]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        Gate::authorize('create', Building::class);
        $building = Building::create($this->validated($request) + ['organization_id' => $this->organizationId()]);
        $logger->log('building.created', $building);

        return redirect()->route('buildings.show', $building);
    }

    public function show(Building $building)
    {
        Gate::authorize('view', $building);

        return view('buildings.show', compact('building'));
    }

    public function edit(Building $building)
    {
        Gate::authorize('update', $building);

        return view('buildings.form', compact('building'));
    }

    public function update(Request $request, Building $building, ActivityLogger $logger)
    {
        Gate::authorize('update', $building);
        $building->update($this->validated($request));
        $logger->log('building.updated', $building);

        return redirect()->route('buildings.show', $building);
    }

    public function destroy(Building $building, ActivityLogger $logger)
    {
        Gate::authorize('delete', $building);

        if ($building->units()->withTrashed()->exists() || $building->expenses()->exists()) {
            abort(422, __('buildings.lifecycle.cannot_archive_with_history'));
        }

        $building->delete();
        $logger->log('building.deleted', $building);

        return redirect()->route('buildings.index');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
