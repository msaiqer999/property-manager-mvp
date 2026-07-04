<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Building;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Unit::class);

        $units = Unit::with('building')
            ->whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))
            ->when($request->building_id, fn ($q, $id) => $q->where('building_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return view('units.index', ['units' => $units, 'buildings' => $this->buildings()]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Unit::class);

        $unit = new Unit([
            'building_id' => $request->integer('building_id') ?: null,
        ]);

        return view('units.form', ['unit' => $unit, 'buildings' => $this->buildings()]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        Gate::authorize('create', Unit::class);
        $unit = Unit::create($this->validated($request));
        $logger->log('unit.created', $unit);

        return redirect()->route('units.show', $unit);
    }

    public function show(Unit $unit)
    {
        Gate::authorize('view', $unit);
        $unit->loadMissing('building', 'unitDocuments.uploader');

        return view('units.show', compact('unit'));
    }

    public function edit(Unit $unit)
    {
        Gate::authorize('update', $unit);

        return view('units.form', ['unit' => $unit, 'buildings' => $this->buildings()]);
    }

    public function update(Request $request, Unit $unit, ActivityLogger $logger)
    {
        Gate::authorize('update', $unit);
        $oldStatus = $unit->status;
        $unit->update($this->validated($request));
        $logger->log($oldStatus !== $unit->status ? 'unit.status_changed' : 'unit.updated', $unit);

        return redirect()->route('units.show', $unit);
    }

    public function destroy(Unit $unit, ActivityLogger $logger)
    {
        Gate::authorize('delete', $unit);

        if ($unit->contracts()->exists() || $unit->expenses()->exists()) {
            abort(422, __('units.lifecycle.cannot_archive_with_history'));
        }

        $unit->delete();
        $logger->log('unit.deleted', $unit);

        return redirect()->route('units.index');
    }

    private function buildings()
    {
        return Building::where('organization_id', $this->organizationId())->orderBy('name')->get();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'building_id' => ['required', Rule::exists('buildings', 'id')->where('organization_id', $this->organizationId())],
            'unit_number' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:apartment,shop,office,warehouse,villa,chalet,other'],
            'size' => ['nullable', 'numeric', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:vacant,rented,maintenance'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
