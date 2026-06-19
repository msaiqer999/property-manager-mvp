@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ $expense->exists ? __('expenses.edit') : __('expenses.add') }}</h1>
<form method="post" enctype="multipart/form-data" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="grid max-w-2xl gap-4 rounded border bg-white p-4 shadow-sm md:grid-cols-2">
@csrf @if($expense->exists) @method('put') @endif
<label class="block text-sm font-medium">{{ __('expenses.form.building') }} <select name="building_id" class="tap-target mt-1 w-full rounded border p-2">@foreach($buildings as $building)<option value="{{ $building->id }}" @selected(old('building_id', $expense->building_id)==$building->id)>{{ $building->name }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">{{ __('expenses.form.unit') }} <select name="unit_id" class="tap-target mt-1 w-full rounded border p-2"><option value=""></option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $expense->unit_id)==$unit->id)>{{ $unit->unit_number }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">{{ __('expenses.form.category') }} <select name="category" class="tap-target mt-1 w-full rounded border p-2">@foreach(['maintenance','electricity','water','cleaning','security','management','other'] as $category)<option value="{{ $category }}" @selected(old('category', $expense->category)==$category)>{{ __('expenses.categories.'.$category) }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">{{ __('expenses.form.amount') }} <input name="amount" type="number" step="0.01" value="{{ old('amount', $expense->amount) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium">{{ __('expenses.form.date') }} <input name="expense_date" type="date" value="{{ old('expense_date', optional($expense->expense_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium">{{ __('expenses.form.invoice') }} <input name="invoice_image" type="file" accept="image/*" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium md:col-span-2">{{ __('expenses.form.notes') }} <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $expense->notes) }}</textarea></label>
<button class="tap-target w-full rounded bg-slate-900 px-4 text-white md:col-span-2">{{ __('expenses.form.save') }}</button>
</form>
@endsection
