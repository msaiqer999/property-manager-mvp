<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\ActivityLogger;
use App\Support\PaymentSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $data = $this->validated($request);
        $this->authorizeContractInputs($data);

        $contract = DB::transaction(function () use ($data) {
            $contract = Contract::create($data + ['organization_id' => $this->organizationId()]);
            PaymentSchedule::createFor($contract);
            $contract->unit()->update(['status' => 'rented']);

            return $contract;
        });
        $logger->log('contract.created', $contract);
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
        abort_unless(Tenant::where('organization_id', $this->organizationId())->whereKey($data['tenant_id'])->exists(), 403);
        abort_unless(Unit::whereKey($data['unit_id'])->whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))->exists(), 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'contract_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('contracts', 'contract_number')
                    ->where('organization_id', $this->organizationId())
                    ->ignore($request->route('contract')),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'payment_frequency' => ['required', 'in:monthly,quarterly,semi_annual,annual'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,expired,terminated'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
