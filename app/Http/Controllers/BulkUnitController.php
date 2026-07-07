<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BulkUnitController extends Controller
{
    private const TYPES = ['apartment', 'shop', 'office', 'warehouse', 'villa', 'chalet', 'other'];
    private const STATUSES = ['vacant', 'rented', 'maintenance'];
    private const MANUAL_ROW_COUNT = 5;

    public function createStandalone()
    {
        Gate::authorize('create', Unit::class);

        return view('units.bulk-entry', [
            'buildings' => Building::where('organization_id', auth()->user()->organization_id)->orderBy('name')->get(),
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'rowCount' => self::MANUAL_ROW_COUNT,
        ]);
    }

    public function create(Building $building)
    {
        $this->authorizeBulkCreation($building);

        return view('units.bulk-create', [
            'building' => $building,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
        ]);
    }

    public function preview(Request $request, Building $building)
    {
        $this->authorizeBulkCreation($building);

        $validated = $request->validate([
            'prefix' => ['nullable', 'string', 'max:20'],
            'start_number' => ['required', 'integer', 'min:0', 'max:999999'],
            'end_number' => ['required', 'integer', 'min:0', 'max:999999', 'gte:start_number'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:0'],
            'size' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'notes' => ['nullable', 'string'],
        ], [], __('units.bulk.attributes'));

        if (($validated['end_number'] - $validated['start_number']) > 199) {
            throw ValidationException::withMessages([
                'end_number' => __('units.bulk.validation.range_too_large'),
            ]);
        }

        $rows = collect(range($validated['start_number'], $validated['end_number']))
            ->map(fn (int $number) => [
                'unit_number' => ($validated['prefix'] ?? '').$number,
                'type' => $validated['type'],
                'rent_amount' => $validated['rent_amount'],
                'rooms' => $validated['rooms'] ?? null,
                'size' => $validated['size'] ?? null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ])
            ->all();

        $this->ensureNoDuplicateUnitNumbersInRequest($rows);

        return view('units.bulk-preview', [
            'building' => $building,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'rows' => $rows,
        ]);
    }

    public function expiredPreview(Building $building)
    {
        $this->authorizeBulkCreation($building);

        return redirect()
            ->route('buildings.units.bulk.create', $building)
            ->with('status', __('units.bulk.preview_expired'));
    }

    public function store(Request $request, Building $building, ActivityLogger $logger)
    {
        $this->authorizeBulkCreation($building);

        $validated = $request->validate([
            'units' => ['required', 'array', 'min:1', 'max:200'],
            'units.*.unit_number' => ['required', 'string', 'max:50'],
            'units.*.type' => ['required', 'in:'.implode(',', self::TYPES)],
            'units.*.rent_amount' => ['required', 'numeric', 'min:0'],
            'units.*.rooms' => ['nullable', 'integer', 'min:0'],
            'units.*.size' => ['nullable', 'numeric', 'min:0'],
            'units.*.status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'units.*.notes' => ['nullable', 'string'],
        ], [], __('units.bulk.attributes'));

        $rows = collect($validated['units'])
            ->map(fn (array $row) => [
                'building_id' => $building->id,
                'unit_number' => trim($row['unit_number']),
                'type' => $row['type'],
                'rent_amount' => $row['rent_amount'],
                'rooms' => $row['rooms'] ?? null,
                'size' => $row['size'] ?? null,
                'status' => $row['status'],
                'notes' => isset($row['notes']) ? trim($row['notes']) : null,
            ])
            ->all();

        [$creatableRows, $skippedUnitNumbers] = $this->filterCreatableRows($building, $rows);

        DB::transaction(function () use ($creatableRows, $logger): void {
            foreach ($creatableRows as $row) {
                $unit = Unit::create($row);
                $logger->log('unit.created', $unit);
            }
        });

        $messageKey = $skippedUnitNumbers === []
            ? 'units.bulk.created_success'
            : 'units.bulk.created_with_skips';

        return redirect()
            ->route('buildings.show', $building)
            ->with('status', __($messageKey, [
                'count' => count($creatableRows),
                'skipped' => implode(', ', $skippedUnitNumbers),
            ]));
    }

    public function storeStandalone(Request $request, ActivityLogger $logger)
    {
        Gate::authorize('create', Unit::class);

        $organizationId = auth()->user()->organization_id;
        $submittedRows = collect($request->input('units', []))
            ->filter(fn ($row) => trim((string) data_get($row, 'unit_number', '')) !== '')
            ->values()
            ->all();

        if ($submittedRows === []) {
            throw ValidationException::withMessages([
                'units' => __('units.bulk.validation.no_rows'),
            ]);
        }

        $requestedBuildingId = $request->integer('building_id');
        if ($requestedBuildingId
            && Building::whereKey($requestedBuildingId)->exists()
            && ! Building::where('organization_id', $organizationId)->whereKey($requestedBuildingId)->exists()) {
            abort(403);
        }

        $validated = validator([
            'building_id' => $request->input('building_id'),
            'units' => $submittedRows,
        ], [
            'building_id' => ['required', Rule::exists('buildings', 'id')->where('organization_id', $organizationId)],
            'units' => ['required', 'array', 'min:1', 'max:200'],
            'units.*.unit_number' => ['required', 'string', 'max:50'],
            'units.*.type' => ['required', 'in:'.implode(',', self::TYPES)],
            'units.*.rent_amount' => ['required', 'numeric', 'min:0'],
            'units.*.rooms' => ['nullable', 'integer', 'min:0'],
            'units.*.size' => ['nullable', 'numeric', 'min:0'],
            'units.*.status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'units.*.notes' => ['nullable', 'string'],
        ], [], __('units.bulk.attributes'))->validate();

        $building = Building::where('organization_id', $organizationId)
            ->whereKey($validated['building_id'])
            ->firstOrFail();

        $rows = collect($validated['units'])
            ->map(fn (array $row) => [
                'building_id' => $building->id,
                'unit_number' => trim($row['unit_number']),
                'type' => $row['type'],
                'rent_amount' => $row['rent_amount'],
                'rooms' => $row['rooms'] ?? null,
                'size' => $row['size'] ?? null,
                'status' => $row['status'],
                'notes' => isset($row['notes']) ? trim($row['notes']) : null,
            ])
            ->values()
            ->all();

        $this->ensureNoDuplicateUnitNumbersInRequest($rows);
        $this->ensureNoExistingUnitNumbers($building, $rows);

        DB::transaction(function () use ($rows, $logger): void {
            foreach ($rows as $row) {
                $unit = Unit::create($row);
                $logger->log('unit.created', $unit);
            }
        });

        return redirect()
            ->route('units.index', ['building_id' => $building->id])
            ->with('status', __('units.bulk.manual_created_success'));
    }

    private function authorizeBulkCreation(Building $building): void
    {
        Gate::authorize('view', $building);
        Gate::authorize('create', Unit::class);
    }

    private function ensureNoDuplicateUnitNumbersInRequest(array $rows): void
    {
        $unitNumbers = collect($rows)
            ->pluck('unit_number')
            ->map(fn ($unitNumber) => trim((string) $unitNumber))
            ->filter(fn (string $unitNumber) => $unitNumber !== '')
            ->values();

        $duplicateInRequest = $unitNumbers->duplicates()->first();

        if ($duplicateInRequest !== null) {
            throw ValidationException::withMessages([
                'units' => __('units.bulk.validation.duplicate_in_request', ['unit' => $duplicateInRequest]),
            ]);
        }
    }

    private function filterCreatableRows(Building $building, array $rows): array
    {
        $this->ensureNoDuplicateUnitNumbersInRequest($rows);

        $existingUnitNumbers = Unit::withTrashed()
            ->where('building_id', $building->id)
            ->whereIn('unit_number', collect($rows)->pluck('unit_number')->all())
            ->pluck('unit_number')
            ->map(fn ($unitNumber) => (string) $unitNumber)
            ->all();

        $skipped = [];
        $creatableRows = collect($rows)
            ->reject(function (array $row) use ($existingUnitNumbers, &$skipped): bool {
                if (in_array((string) $row['unit_number'], $existingUnitNumbers, true)) {
                    $skipped[] = (string) $row['unit_number'];

                    return true;
                }

                return false;
            })
            ->values()
            ->all();

        return [$creatableRows, $skipped];
    }

    private function ensureNoExistingUnitNumbers(Building $building, array $rows): void
    {
        $existingUnitNumber = Unit::withTrashed()
            ->where('building_id', $building->id)
            ->whereIn('unit_number', collect($rows)->pluck('unit_number')->all())
            ->value('unit_number');

        if ($existingUnitNumber !== null) {
            throw ValidationException::withMessages([
                'units' => __('units.bulk.validation.duplicate_existing', ['unit' => $existingUnitNumber]),
            ]);
        }
    }
}
