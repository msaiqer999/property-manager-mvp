<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\ActivityLogger;
use App\Support\PaymentSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function create()
    {
        Gate::authorize('create', Contract::class);
        return view('contracts.form', $this->formData(new Contract()));
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        Gate::authorize('create', Contract::class);
        $data = $this->validated($request, true);

        if ($data['tenant_mode'] === 'new') {
            Gate::authorize('create', Tenant::class);
            $this->rejectDuplicateTenant($data['new_tenant']);
        } else {
            $this->authorizeTenantInput($data['tenant_id']);
        }

        $this->authorizeUnitInput($data['unit_id']);

        $contract = DB::transaction(function () use ($data, $logger) {
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
            $contract->unit()->update(['status' => 'rented']);
            $logger->log('contract.created', $contract);

            return $contract;
        });

        return redirect()->route('contracts.show', $contract);
    }

    public function show(Contract $contract)
    {
        $this->authorizeContract($contract);
        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        abort_if(auth()->user()->role->value === 'accountant' || auth()->user()->role->value === 'caretaker', 403);
        Gate::authorize('update', $contract);
        $this->authorizeContract($contract);
        return view('contracts.form', $this->formData($contract));
    }

    public function update(Request $request, Contract $contract, ActivityLogger $logger)
    {
        abort_if(auth()->user()->role->value === 'accountant' || auth()->user()->role->value === 'caretaker', 403);
        Gate::authorize('update', $contract);
        $this->authorizeContract($contract);
        $data = $this->validated($request);
        $this->authorizeContractInputs($data);
        DB::transaction(function () use ($contract, $data) {
            $scheduleFields = ['start_date', 'end_date', 'rent_amount', 'payment_frequency'];
            $scheduleChanged = collect($scheduleFields)->contains(fn ($field) => (string) $contract->{$field} !== (string) $data[$field]);

            $contract->update($data);

            if ($scheduleChanged) {
                PaymentSchedule::replaceFor($contract);
            }
        });
        $logger->log('contract.updated', $contract);
        return redirect()->route('contracts.show', $contract);
    }

    public function destroy(Contract $contract)
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        Gate::authorize('delete', $contract);
        $this->authorizeContract($contract);
        $contract->delete();
        return redirect()->route('contracts.index');
    }

    public function pdf(Contract $contract)
    {
        Gate::authorize('exportPdf', $contract);
        $this->authorizeContract($contract);
        return Pdf::loadView('pdf.contract', compact('contract'))->download("contract-{$contract->contract_number}.pdf");
    }

    private function formData(Contract $contract): array
    {
        return [
            'contract' => $contract,
            'tenants' => Tenant::where('organization_id', $this->organizationId())->orderBy('full_name')->get(),
            'units' => Unit::whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))->orderBy('unit_number')->get(),
        ];
    }

    private function authorizeContract(Contract $contract): void
    {
        Gate::authorize('view', $contract);
        abort_unless($contract->organization_id === $this->organizationId(), 403);
    }

    private function authorizeContractInputs(array $data): void
    {
        $this->authorizeTenantInput($data['tenant_id']);
        $this->authorizeUnitInput($data['unit_id']);
    }

    private function authorizeTenantInput(int $tenantId): void
    {
        abort_unless(Tenant::where('organization_id', $this->organizationId())->whereKey($tenantId)->exists(), 403);
    }

    private function authorizeUnitInput(int $unitId): void
    {
        abort_unless(Unit::whereKey($unitId)->whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))->exists(), 403);
    }

    private function validated(Request $request, bool $creating = false): array
    {
        if ($creating) {
            $request->merge(['tenant_mode' => $request->input('tenant_mode', 'existing')]);
        }

        $rules = [
            'unit_id' => ['required', 'exists:units,id'],
            'tenant_id' => [$creating ? 'required_if:tenant_mode,existing' : 'required', 'nullable', 'exists:tenants,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'payment_frequency' => ['required', 'in:monthly,quarterly,semi_annual,annual'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,expired,terminated'],
            'notes' => ['nullable', 'string'],
        ];

        if ($creating) {
            $rules += [
                'tenant_mode' => ['required', 'in:existing,new'],
                'new_tenant.full_name' => ['required_if:tenant_mode,new', 'nullable', 'string', 'max:255'],
                'new_tenant.phone' => ['nullable', 'string', 'max:50'],
                'new_tenant.email' => ['nullable', 'email', 'max:255'],
                'new_tenant.id_number' => ['nullable', 'string', 'max:100'],
                'new_tenant.nationality' => ['nullable', 'string', 'max:100'],
                'new_tenant.notes' => ['nullable', 'string'],
            ];
        }

        $data = $request->validate($rules);
        $data['deposit_amount'] = $this->moneyOrZero($data['deposit_amount'] ?? null);

        return $data;
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
                'new_tenant.full_name' => 'A tenant with matching details already exists. Select the existing tenant instead.',
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
