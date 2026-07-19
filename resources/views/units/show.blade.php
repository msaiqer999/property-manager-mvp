@extends('layouts.app')

@section('content')
@php
    $formatUnitMoney = fn ($value) => \App\Support\MoneyFormatter::forBuilding($unit->building, $value);
@endphp

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('units.fields.unit') }} <bdi dir="ltr">{{ $unit->unit_number }}</bdi></h1>
    <div class="grid gap-2 sm:flex sm:flex-wrap">
        @can('create', \App\Models\Contract::class)
            @if($unit->status === 'vacant')
                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('contracts.create', ['unit_id' => $unit->id]) }}">{{ __('contracts.actions.create_for_unit') }}</a>
            @else
                <span class="inline-flex min-h-11 items-center justify-center rounded border bg-brand-background px-4 text-center text-sm font-medium text-brand-muted">{{ __('contracts.actions.unit_unavailable_hint') }}</span>
            @endif
        @endcan
        @if(auth()->user()?->role?->can('view-reports'))
            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('reports.index', ['unit_id' => $unit->id]) }}">{{ __('reports.statement.view_statement') }}</a>
        @endif
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('units.edit', $unit) }}">{{ __('app.actions.edit') }}</a>
    </div>
</div>

<div data-unit-show-card class="grid gap-3 rounded border bg-brand-surface p-4 shadow-sm sm:grid-cols-2">
    <p><span class="font-medium">{{ __('units.labels.building') }}</span> {{ $unit->building->name }}</p>
    <p><span class="font-medium">{{ __('units.labels.status') }}</span> <x-status-badge :status="$unit->status" :label="__('units.statuses.'.$unit->status)" /></p>
    <p><span class="font-medium">{{ __('units.labels.type') }}</span> {{ __('units.types.'.$unit->type) }}</p>
    <p><span class="font-medium">{{ __('units.labels.rent') }}</span> <bdi dir="ltr">{{ $formatUnitMoney($unit->rent_amount) }}</bdi></p>
    <p><span class="font-medium">{{ __('units.fields.size') }}:</span> <bdi dir="ltr">{{ $unit->size !== null ? number_format((float) $unit->size, 2) : __('payments.not_available') }}</bdi></p>
    <p><span class="font-medium">{{ __('units.fields.rooms') }}:</span> <bdi dir="ltr">{{ $unit->rooms ?? __('payments.not_available') }}</bdi></p>
    <p class="sm:col-span-2"><span class="font-medium">{{ __('units.fields.notes') }}:</span> {{ $unit->notes ?: __('payments.not_available') }}</p>
</div>

<section class="mt-6 grid gap-4 lg:grid-cols-[1fr_22rem]">
    <div class="rounded border bg-brand-surface p-4 shadow-sm">
        <div class="mb-4">
            <h2 class="text-lg font-semibold">{{ __('unit_documents.title') }}</h2>
            <p class="mt-1 text-sm text-brand-muted">{{ __('unit_documents.description') }}</p>
        </div>

        @if($unit->unitDocuments->isEmpty())
            <div class="rounded border border-dashed border-brand-border bg-brand-background p-4 text-sm text-brand-muted">
                {{ __('unit_documents.empty') }}
            </div>
        @else
            <div class="grid gap-3">
                @foreach($unit->unitDocuments as $document)
                    <article class="rounded border border-brand-border p-3">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-medium text-brand-text">{{ $document->title }}</h3>
                                <p class="mt-1 text-sm text-brand-muted">{{ __('unit_documents.categories.'.$document->category) }}</p>
                            </div>
                            <div class="grid gap-2 sm:flex sm:flex-wrap sm:justify-end">
                                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('unit-documents.download', $document) }}">{{ __('unit_documents.download') }}</a>
                                @can('update', $unit)
                                    <form method="post" action="{{ route('unit-documents.destroy', $document) }}" onsubmit="return confirm('{{ __('unit_documents.confirm_delete') }}')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="tap-target inline-flex min-h-11 w-full items-center justify-center rounded border border-state-danger/25 px-4 text-center text-sm font-medium text-state-danger hover:bg-state-danger-soft sm:w-auto">
                                            {{ __('unit_documents.actions.delete') }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                        <dl class="mt-3 grid gap-2 text-sm text-brand-muted sm:grid-cols-2">
                            <div>
                                <dt class="font-medium text-brand-text">{{ __('unit_documents.fields.uploaded_by') }}</dt>
                                <dd>{{ $document->uploader?->name ?? __('payments.not_available') }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-brand-text">{{ __('unit_documents.fields.uploaded_at') }}</dt>
                                <dd><bdi dir="ltr">{{ $document->created_at?->toDateString() }}</bdi></dd>
                            </div>
                        </dl>
                        @if($document->notes)
                            <p class="mt-3 whitespace-pre-line text-sm text-brand-text">{{ $document->notes }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    @can('update', $unit)
        <form method="post" action="{{ route('unit-documents.store', $unit) }}" enctype="multipart/form-data" class="rounded border bg-brand-surface p-4 shadow-sm">
            @csrf
            <h2 class="text-lg font-semibold">{{ __('unit_documents.upload_title') }}</h2>
            <p class="mt-1 text-sm text-brand-muted">{{ __('unit_documents.help.allowed_types') }}</p>

            @if($errors->any())
                <div class="mt-4 rounded border border-state-danger/25 bg-state-danger-soft p-3 text-sm text-state-danger">
                    <ul class="list-inside list-disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-4 grid gap-3">
                <label class="block text-sm">
                    <span class="font-medium text-brand-text">{{ __('unit_documents.fields.title') }}</span>
                    <input class="mt-1 w-full rounded border border-brand-border px-3 py-2" name="title" value="{{ old('title') }}" required>
                </label>

                <label class="block text-sm">
                    <span class="font-medium text-brand-text">{{ __('unit_documents.fields.category') }}</span>
                    <select class="form-select-safe tap-target mt-1 w-full rounded border border-brand-border px-3 py-2" name="category" required>
                        @foreach(\App\Models\UnitDocument::CATEGORIES as $category)
                            <option value="{{ $category }}" @selected(old('category') === $category)>{{ __('unit_documents.categories.'.$category) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    <span class="font-medium text-brand-text">{{ __('unit_documents.fields.document') }}</span>
                    <input class="mt-1 w-full rounded border border-brand-border px-3 py-2 text-sm" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required>
                </label>

                <label class="block text-sm">
                    <span class="font-medium text-brand-text">{{ __('unit_documents.fields.notes') }}</span>
                    <textarea class="mt-1 w-full rounded border border-brand-border px-3 py-2" name="notes" rows="3">{{ old('notes') }}</textarea>
                </label>
            </div>

            <button class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded bg-brand-primary px-4 text-sm font-medium text-white" type="submit">
                {{ __('unit_documents.actions.upload') }}
            </button>
        </form>
    @endcan
</section>
@endsection
