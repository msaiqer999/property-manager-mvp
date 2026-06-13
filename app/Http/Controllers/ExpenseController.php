<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request)
    {
        $expenses = Expense::with(['building', 'unit'])
            ->where('organization_id', $this->organizationId())
            ->when($request->building_id, fn ($q, $id) => $q->where('building_id', $id))
            ->when($request->unit_id, fn ($q, $id) => $q->where('unit_id', $id))
            ->when($request->category, fn ($q, $category) => $q->where('category', $category))
            ->latest('expense_date')
            ->paginate(20);

        return view('expenses.index', ['expenses' => $expenses, 'buildings' => $this->buildings()]);
    }

    public function create()
    {
        abort_unless(auth()->user()->role->can('manage-expenses'), 403);
        return view('expenses.form', $this->formData(new Expense()));
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        abort_unless(auth()->user()->role->can('manage-expenses'), 403);
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
        $this->authorizeExpense($expense);
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        abort_unless(auth()->user()->role->can('manage-expenses'), 403);
        $this->authorizeExpense($expense);
        return view('expenses.form', $this->formData($expense));
    }

    public function update(Request $request, Expense $expense, ActivityLogger $logger)
    {
        abort_unless(auth()->user()->role->can('manage-expenses'), 403);
        $this->authorizeExpense($expense);
        $data = $this->validated($request);
        $this->authorizeExpenseInputs($data);

        if ($request->hasFile('invoice_image')) {
            $data['invoice_image'] = $request->file('invoice_image')->store('expense-invoices');
        }

        $expense->update($data);
        $logger->log('expense.updated', $expense);
        return redirect()->route('expenses.show', $expense);
    }

    public function destroy(Expense $expense)
    {
        abort_unless(auth()->user()->role->value === 'owner', 403);
        $this->authorizeExpense($expense);
        $expense->delete();
        return redirect()->route('expenses.index');
    }

    private function formData(Expense $expense): array
    {
        return ['expense' => $expense, 'buildings' => $this->buildings(), 'units' => Unit::whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))->get()];
    }

    private function buildings()
    {
        return Building::where('organization_id', $this->organizationId())->orderBy('name')->get();
    }

    private function authorizeExpense(Expense $expense): void
    {
        abort_unless($expense->organization_id === $this->organizationId(), 403);
    }

    private function authorizeExpenseInputs(array $data): void
    {
        abort_unless(Building::where('organization_id', $this->organizationId())->whereKey($data['building_id'])->exists(), 403);

        if (! empty($data['unit_id'])) {
            abort_unless(Unit::whereKey($data['unit_id'])->whereHas('building', fn ($q) => $q->where('organization_id', $this->organizationId()))->exists(), 403);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'category' => ['required', 'in:maintenance,electricity,water,cleaning,security,management,other'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'invoice_image' => ['nullable', 'image', 'max:4096'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
