<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\ActivityLogger;
use App\Support\PaymentSchedule;
use App\Support\UnitOccupancy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Contract::class);

        $contracts = Contract::with(['tenant', 'unit.building'])
            ->where('organization_id', $this->organizationId())
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return view('contracts.index', compact('contracts'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Contract::class);
        $renewalSource = $this->renewalSource($request->query('renew_from'));

        if ($renewalSource === null) {
            return view('contracts.form', $this->formData(new Contract));
        }

        $durationDays = $renewalSource->start_date->diffInDays($renewalSource->end_date);
        $startDate = $renewalSource->end_date->copy()->addDay();
        $contract = new Contract([
            'tenant_id' => $renewalSource->tenant_id,
            'unit_id' => $renewalSource->unit_id,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays($durationDays),
            'rent_amount' => $renewalSource->rent_amount,
            'payment_frequency' => $renewalSource->payment_frequency,
            'deposit_amount' => 0,
            'status' => 'active',
        ]);

        return view('contracts.form', $this->formData($contract) + compact('renewalSource'));
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        Gate::authorize('create', Contract::class);
        $renewalSource = $this->renewalSource($request->input('renew_from'));
        $data = $this->validated($request, true, $renewalSource !== null);

        if ($renewalSource !== null) {
            $data['tenant_mode'] = 'existing';
            $data['tenant_id'] = $renewalSource->tenant_id;
            $data['unit_id'] = $renewalSource->unit_id;
            $data['status'] = 'active';
        }

        if ($data['tenant_mode'] === 'new') {
            Gate::authorize('create', Tenant::class);
            $this->rejectDuplicateTenant($data['new_tenant']);
        } else {
            $this->authorizeTenantInput($data['tenant_id']);
        }

        $this->authorizeUnitInput($data['unit_id']);
        $this->assertNoActiveOverlap($data['unit_id'], $data['start_date'], $data['end_date'], null, $data['status']);

        $contract = DB::transaction(function () use ($data, $logger) {
            $unit = $this->lockOwnedUnit($data['unit_id']);
            $this->assertNoActiveOverlap($unit->id, $data['start_date'], $data['end_date'], null, $data['status']);

            if ($data['tenant_mode'] === 'new') {
                $tenant = Tenant::create($this->tenantData($data['new_tenant']) + [
                    'organization_id' => $this->organizationId(),
                ]);
                $logger->log('tenant.created', $tenant);
                $data['tenant_id'] = $tenant->id;
            }

            unset($data['tenant_mode'], $data['new_tenant']);

            $contract = Contract::create($data + [
                'organization_id' => $this->organizationId(),
                'contract_number' => 'PENDING-'.uniqid(),
            ]);
            $contract->update(['contract_number' => $this->contractNumber($contract)]);
            PaymentSchedule::createFor($contract);
            UnitOccupancy::sync($unit);
            $logger->log('contract.created', $contract);

            return $contract;
        });

        return redirect()->route('contracts.show', $contract);
    }

    public function show(Contract $contract)
    {
        Gate::authorize('view', $contract);

        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        Gate::authorize('update', $contract);

        return view('contracts.form', $this->formData($contract));
    }

    public function update(Request $request, Contract $contract, ActivityLogger $logger)
    {
        Gate::authorize('update', $contract);
        $data = $this->validated($request);
        $this->assertImmutableContractInputs($request, $contract);
        $this->assertValidStatusTransition($contract, $data['status']);
        $scheduleFields = ['start_date', 'end_date', 'rent_amount', 'payment_frequency'];
        $scheduleChanged = collect($scheduleFields)->contains(fn ($field) => $this->scheduleFieldChanged($contract, $data, $field));

        if ($scheduleChanged && $this->hasRecordedPayments($contract)) {
            throw ValidationException::withMessages([
                'start_date' => __('contracts.validation.payment_terms_locked'),
            ]);
        }

        $this->assertNoActiveOverlap($contract->unit_id, $data['start_date'], $data['end_date'], $contract, $data['status']);

        DB::transaction(function () use ($contract, $data, $scheduleChanged) {
            $unit = $this->lockOwnedUnit($contract->unit_id);
            $this->assertNoActiveOverlap($unit->id, $data['start_date'], $data['end_date'], $contract, $data['status']);

            $contract->update($data);

            if ($scheduleChanged) {
                PaymentSchedule::replaceFor($contract);
            }

            UnitOccupancy::sync($unit);
        });
        $logger->log('contract.updated', $contract);

        return redirect()->route('contracts.show', $contract);
    }

    public function destroy(Contract $contract)
    {
        Gate::authorize('delete', $contract);

        abort(422, __('contracts.validation.cannot_delete'));
    }

    public function pdf(Contract $contract)
    {
        Gate::authorize('exportPdf', $contract);

        return Pdf::loadView('pdf.contract', compact('contract'))->download("contract-{$contract->contract_number}.pdf");
    }

    private function formData(Contract $contract): array
    {
        $units = Unit::with(['building', 'contracts' => fn ($q) => $q->where('status', 'active')->orderBy('start_date')])
            ->whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))
            ->orderBy('unit_number')
            ->get();

        $units->each(fn (Unit $unit) => $unit->availability_label = $this->availabilityLabel($unit));

        return [
            'contract' => $contract,
            'tenants' => Tenant::where('organization_id', $this->organizationId())->orderBy('full_name')->get(),
            'units' => $units,
        ];
    }

    private function authorizeTenantInput(int $tenantId): void
    {
        Gate::authorize('view', Tenant::findOrFail($tenantId));
    }

    private function authorizeUnitInput(int $unitId): void
    {
        Gate::authorize('view', Unit::findOrFail($unitId));
    }

    private function validated(Request $request, bool $creating = false, bool $renewal = false): array
    {
        if ($creating && ! $renewal) {
            $request->merge(['tenant_mode' => $request->input('tenant_mode', 'existing')]);
        }

        $rules = [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'payment_frequency' => ['required', 'in:monthly,quarterly,semi_annual,annual'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,expired,terminated'],
            'notes' => ['nullable', 'string'],
        ];

        if ($creating && ! $renewal) {
            $rules += [
                'unit_id' => ['required', 'exists:units,id'],
                'tenant_id' => ['required_if:tenant_mode,existing', 'nullable', 'exists:tenants,id'],
                'tenant_mode' => ['required', 'in:existing,new'],
                'new_tenant.full_name' => ['required_if:tenant_mode,new', 'nullable', 'string', 'max:255'],
                'new_tenant.phone' => ['nullable', 'string', 'max:50'],
                'new_tenant.email' => ['nullable', 'email', 'max:255'],
                'new_tenant.id_number' => ['nullable', 'string', 'max:100'],
                'new_tenant.nationality' => ['nullable', 'string', 'max:100'],
                'new_tenant.notes' => ['nullable', 'string'],
            ];
        }

        $data = $request->validate($rules, [], __('contracts.attributes'));
        $data['deposit_amount'] = $this->moneyOrZero($data['deposit_amount'] ?? null);

        return $data;
    }

    private function renewalSource(mixed $contractId): ?Contract
    {
        if ($contractId === null || $contractId === '') {
            return null;
        }

        $contract = Contract::with(['tenant', 'unit.building'])->findOrFail((int) $contractId);
        Gate::authorize('view', $contract);
        abort_unless($contract->isRenewalEligible(), 404);

        return $contract;
    }

    private function assertNoActiveOverlap(int $unitId, string $startDate, string $endDate, ?Contract $except = null, string $status = 'active'): void
    {
        if ($status !== 'active') {
            return;
        }

        $query = Contract::where('unit_id', $unitId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        if ($except !== null) {
            $query->whereKeyNot($except->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'unit_id' => __('contracts.validation.overlap'),
            ]);
        }
    }

    private function lockOwnedUnit(int $unitId): Unit
    {
        $unit = Unit::whereKey($unitId)->lockForUpdate()->firstOrFail();
        Gate::authorize('view', $unit);

        return $unit;
    }

    private function assertImmutableContractInputs(Request $request, Contract $contract): void
    {
        if ($request->filled('tenant_id')) {
            abort_unless((int) $request->input('tenant_id') === $contract->tenant_id, 403);
        }

        if ($request->filled('unit_id')) {
            abort_unless((int) $request->input('unit_id') === $contract->unit_id, 403);
        }
    }

    private function assertValidStatusTransition(Contract $contract, string $requestedStatus): void
    {
        if ($contract->status === $requestedStatus) {
            return;
        }

        if ($contract->status === 'active' && in_array($requestedStatus, ['expired', 'terminated'], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => __('contracts.validation.invalid_status_transition'),
        ]);
    }

    private function hasRecordedPayments(Contract $contract): bool
    {
        return $contract->payments()
            ->where(fn ($query) => $query->where('amount_paid', '>', 0)->orWhereNotNull('payment_date'))
            ->exists();
    }

    private function availabilityLabel(Unit $unit): string
    {
        $today = now()->toDateString();
        $currentContracts = $unit->contracts
            ->filter(fn (Contract $contract) => $contract->start_date->toDateString() <= $today && $contract->end_date->toDateString() >= $today);
        $currentContractIds = $currentContracts->pluck('id')->all();
        $currentEndDate = $currentContracts->max(fn (Contract $contract) => $contract->end_date->toDateString());

        $futureContracts = $unit->contracts
            ->reject(fn (Contract $contract) => in_array($contract->id, $currentContractIds, true))
            ->filter(fn (Contract $contract) => $contract->start_date->toDateString() > $today)
            ->sortBy(fn (Contract $contract) => $contract->start_date->toDateString())
            ->values();

        if ($unit->status === 'maintenance') {
            $label = __('contracts.availability.maintenance');
        } else {
            $label = $currentEndDate
                ? __('contracts.availability.occupied_until', ['date' => $currentEndDate])
                : __('contracts.availability.available_now');
        }

        if ($futureContracts->isNotEmpty()) {
            $futureContract = $futureContracts->first();
            $additionalFutureCount = $futureContracts->count() - 1;
            $label .= '; '.__('contracts.availability.future_contract', [
                'start' => $futureContract->start_date->toDateString(),
                'end' => $futureContract->end_date->toDateString(),
            ]);

            if ($additionalFutureCount > 0) {
                $label .= ' '.__('contracts.availability.more', ['count' => $additionalFutureCount]);
            }
        }

        return $label;
    }

    private function scheduleFieldChanged(Contract $contract, array $data, string $field): bool
    {
        if (in_array($field, ['start_date', 'end_date'], true)) {
            return Carbon::parse($contract->{$field})->toDateString() !== Carbon::parse($data[$field])->toDateString();
        }

        if ($field === 'rent_amount') {
            return number_format((float) $contract->rent_amount, 2, '.', '') !== number_format((float) $data['rent_amount'], 2, '.', '');
        }

        return (string) $contract->{$field} !== (string) $data[$field];
    }

    private function tenantData(array $data): array
    {
        return [
            'full_name' => trim($data['full_name']),
            'phone' => $this->nullableTrim($data['phone'] ?? null),
            'email' => $this->nullableLower($data['email'] ?? null),
            'id_number' => $this->nullableTrim($data['id_number'] ?? null),
            'nationality' => $this->nullableTrim($data['nationality'] ?? null),
            'notes' => $this->nullableTrim($data['notes'] ?? null),
        ];
    }

    private function rejectDuplicateTenant(array $data): void
    {
        $tenant = $this->tenantData($data);
        $query = Tenant::where('organization_id', $this->organizationId());

        $duplicateExists = false;

        if (! empty($tenant['id_number'])) {
            $duplicateExists = (clone $query)->where('id_number', $tenant['id_number'])->exists();
        }

        if (! $duplicateExists && ! empty($tenant['email'])) {
            $duplicateExists = (clone $query)
                ->where('full_name', $tenant['full_name'])
                ->whereRaw('LOWER(email) = ?', [$tenant['email']])
                ->exists();
        }

        if (! $duplicateExists && ! empty($tenant['phone'])) {
            $duplicateExists = (clone $query)
                ->where('full_name', $tenant['full_name'])
                ->where('phone', $tenant['phone'])
                ->exists();
        }

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'new_tenant.full_name' => __('contracts.validation.duplicate_tenant'),
            ]);
        }
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableLower(?string $value): ?string
    {
        $value = $this->nullableTrim($value);

        return $value === null ? null : strtolower($value);
    }

    private function moneyOrZero(mixed $value): mixed
    {
        return $value === null || $value === '' ? 0 : $value;
    }

    private function contractNumber(Contract $contract): string
    {
        $year = Carbon::parse($contract->start_date)->format('Y');

        return 'CN-'.$year.'-'.str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT);
    }
}
