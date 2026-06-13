<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Building;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        $units = Unit::with('building')
            ->whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))
            ->when($request->building_id, fn ($q, $id) => $q->where('building_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return view('units.index', ['units' => $units, 'buildings' => $this->buildings()]);
    }

    public function create() { return view('units.form', ['unit' => new Unit(), 'buildings' => $this->buildings()]); }

    public function store(Request $request, ActivityLogger $logger)
    {
        $unit = Unit::create($this->validated($request));
        $logger->log('unit.created', $unit);
        return redirect()->route('units.show', $unit);
    }

    public function show(Unit $unit)
    {
        $this->authorizeUnit($unit);
        return view('units.show', compact('unit'));
    }

    public function edit(Unit $unit)
    {
        $this->authorizeUnit($unit);
        return view('units.form', ['unit' => $unit, 'buildings' => $this->buildings()]);
    }

    public function update(Request $request, Unit $unit, ActivityLogger $logger)
    {
        $this->authorizeUnit($unit);
        $oldStatus = $unit->status;
        $unit->update($this->validated($request));
        $logger->log($oldStatus !== $unit->status ? 'unit.status_changed' : 'unit.updated', $unit);
        return redirect()->route('units.show', $unit);
    }

    public function destroy(Unit $unit, ActivityLogger $logger)
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        $this->authorizeUnit($unit);
        $unit->delete();
        $logger->log('unit.deleted', $unit);
        return redirect()->route('units.index');
    }

    private function buildings()
    {
        return Building::where('organization_id', $this->organizationId())->orderBy('name')->get();
    }

    private function authorizeUnit(Unit $unit): void
    {
        abort_unless($unit->building()->where('organization_id', $this->organizationId())->exists(), 403);
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
