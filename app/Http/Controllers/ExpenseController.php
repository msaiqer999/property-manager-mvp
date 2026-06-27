<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Expense::class);

        $lifecycle = $this->lifecycleFilter($request);
        $this->authorizeFilterOwnership($request);

        $expenses = Expense::with(['building', 'unit'])
            ->where('organization_id', $this->organizationId())
            ->when($lifecycle === 'active', fn ($q) => $q->notVoided())
            ->when($lifecycle === 'voided', fn ($q) => $q->onlyVoided())
            ->when($request->building_id, fn ($q, $id) => $q->where('building_id', $id))
            ->when($request->unit_id, fn ($q, $id) => $q->where('unit_id', $id))
            ->when($request->category, fn ($q, $category) => $q->where('category', $category))
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'buildings' => $this->buildings(),
            'units' => $this->units(),
            'lifecycle' => $lifecycle,
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Expense::class);

        return view('expenses.form', $this->formData(new Expense([
            'building_id' => request()->integer('building_id') ?: null,
        ])));
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        Gate::authorize('create', Expense::class);
        $this->authorizeSubmittedOwnership($request);
        $data = $this->validated($request);
        $this->authorizeExpenseInputs($data);
        $data += ['organization_id' => $this->organizationId(), 'created_by' => auth()->id()];

        if ($request->hasFile('invoice_image')) {
            $data['invoice_image'] = $request->file('invoice_image')->store('expense-invoices');
        }

        $expense = Expense::create($data);
        $logger->log('expense.created', $expense);

        return redirect()->route('expenses.show', $expense);
    }

    public function show(Expense $expense)
    {
        Gate::authorize('view', $expense);

        $expense->load('voidedBy');

        return view('expenses.show', compact('expense'));
    }

    public function downloadInvoice(Expense $expense)
    {
        Gate::authorize('view', $expense);

        $path = $this->validatedPrivatePath($expense->invoice_image, 'expense-invoices');

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, $this->safeDownloadName('expense-invoice', $expense->id, $path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function edit(Expense $expense)
    {
        Gate::authorize('update', $expense);
        $this->ensureExpenseNotVoided($expense);

        return view('expenses.form', $this->formData($expense));
    }

    public function update(Request $request, Expense $expense, ActivityLogger $logger)
    {
        Gate::authorize('update', $expense);
        $this->ensureExpenseNotVoided($expense);

        $this->authorizeSubmittedOwnership($request);
        $data = $this->validated($request);
        $this->authorizeExpenseInputs($data);

        if ($request->hasFile('invoice_image')) {
            Gate::authorize('uploadInvoice', $expense);
            $data['invoice_image'] = $request->file('invoice_image')->store('expense-invoices');
        }

        $expense->update($data);
        $logger->log('expense.updated', $expense);

        return redirect()->route('expenses.show', $expense);
    }

    public function voidExpense(Request $request, Expense $expense, ActivityLogger $logger)
    {
        Gate::authorize('voidExpense', $expense);

        if ($expense->voided_at !== null) {
            abort(422, __('expenses.lifecycle.already_voided'));
        }

        $reason = $this->voidReason($request);

        DB::transaction(function () use ($expense, $logger, $reason) {
            $updated = Expense::whereKey($expense->id)
                ->where('organization_id', $this->organizationId())
                ->whereNull('voided_at')
                ->update([
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                    'void_reason' => $reason,
                ]);

            if ($updated !== 1) {
                abort(422, __('expenses.lifecycle.already_voided'));
            }

            $expense->refresh();
            $logger->log('expense.voided', $expense, $reason);
        });

        return redirect()->route('expenses.show', $expense);
    }

    public function destroy(Expense $expense)
    {
        Gate::authorize('delete', $expense);

        abort(422, __('expenses.lifecycle.cannot_delete_financial_record'));
    }

    private function formData(Expense $expense): array
    {
        return [
            'expense' => $expense,
            'buildings' => $this->buildings(),
            'units' => $this->units(),
        ];
    }

    private function buildings()
    {
        return Building::where('organization_id', $this->organizationId())->orderBy('name')->get();
    }

    private function units(?int $buildingId = null)
    {
        return Unit::whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))
            ->when($buildingId, fn ($q) => $q->where('building_id', $buildingId))
            ->orderBy('unit_number')
            ->get();
    }

    private function authorizeExpenseInputs(array $data): void
    {
        Gate::authorize('view', Building::findOrFail($data['building_id']));

        if (! empty($data['unit_id'])) {
            $unit = Unit::findOrFail($data['unit_id']);

            Gate::authorize('view', $unit);

            if ((int) $unit->building_id !== (int) $data['building_id']) {
                throw ValidationException::withMessages([
                    'unit_id' => __('validation.exists', ['attribute' => __('expenses.form.unit')]),
                ]);
            }
        }
    }

    private function authorizeSubmittedOwnership(Request $request): void
    {
        if ($request->filled('building_id')) {
            $building = Building::find($request->input('building_id'));

            if ($building !== null) {
                Gate::authorize('view', $building);
            }
        }

        if ($request->filled('unit_id')) {
            $unit = Unit::find($request->input('unit_id'));

            if ($unit !== null) {
                Gate::authorize('view', $unit);
            }
        }
    }

    private function authorizeFilterOwnership(Request $request): void
    {
        $building = null;

        if ($request->filled('building_id')) {
            $building = Building::findOrFail($request->input('building_id'));
            Gate::authorize('view', $building);
        }

        if ($request->filled('unit_id')) {
            $unit = Unit::findOrFail($request->input('unit_id'));
            Gate::authorize('view', $unit);

            if ($building !== null && (int) $unit->building_id !== (int) $building->id) {
                abort(403);
            }
        }
    }

    private function lifecycleFilter(Request $request): string
    {
        $lifecycle = $request->query('lifecycle', 'active');

        return in_array($lifecycle, ['active', 'voided', 'all'], true) ? $lifecycle : 'active';
    }

    private function ensureExpenseNotVoided(Expense $expense): void
    {
        if ($expense->voided_at !== null) {
            abort(422, __('expenses.lifecycle.cannot_edit_voided'));
        }
    }

    private function voidReason(Request $request): string
    {
        $data = $request->validate([
            'void_reason' => ['required', 'string', 'max:1000'],
        ]);

        $reason = trim(preg_replace('/\s+/u', ' ', $data['void_reason']));

        if ($reason === '') {
            throw ValidationException::withMessages([
                'void_reason' => __('validation.required', ['attribute' => __('expenses.attributes.void_reason')]),
            ]);
        }

        return $reason;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'building_id' => ['required', Rule::exists('buildings', 'id')->where('organization_id', $this->organizationId())],
            'unit_id' => [
                'nullable',
                Rule::exists('units', 'id')->where(fn ($query) => $query->where('building_id', $request->input('building_id'))),
            ],
            'category' => ['required', 'in:maintenance,electricity,water,cleaning,security,management,other'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'invoice_image' => ['nullable', 'image', 'max:4096'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function validatedPrivatePath(?string $storedPath, string $prefix): string
    {
        $rawPath = trim((string) $storedPath);

        if ($rawPath === ''
            || str_contains($rawPath, '\\')
            || str_starts_with($rawPath, '/')
            || preg_match('/^[A-Za-z]:\//', $rawPath)
        ) {
            abort(404);
        }

        $path = preg_replace('#/+#', '/', $rawPath);
        $segments = explode('/', $path);

        if (in_array('..', $segments, true)
            || in_array('', $segments, true)
            || ! Str::startsWith($path, $prefix.'/')
        ) {
            abort(404);
        }

        return $path;
    }

    private function safeDownloadName(string $label, int $id, string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $extension = preg_match('/^[A-Za-z0-9]{1,10}$/', $extension) ? strtolower($extension) : 'bin';

        return "{$label}-{$id}.{$extension}";
    }
}
