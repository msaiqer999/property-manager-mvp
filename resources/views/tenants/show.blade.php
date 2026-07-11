@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">{{ $tenant->full_name }}</h1>
        <p class="mt-1 text-sm text-brand-muted">
            @if($tenant->archived_at)
                <x-status-badge status="archived" :label="__('tenants.lifecycle.archived')" />
            @else
                <x-status-badge status="active" :label="__('tenants.lifecycle.active')" />
            @endif
        </p>
    </div>
    <div class="grid gap-2 sm:flex sm:flex-wrap">
        @can('create', \App\Models\Contract::class)
            @if(! $tenant->archived_at)
                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('contracts.create', ['tenant_id' => $tenant->id]) }}">{{ __('contracts.actions.create_for_tenant') }}</a>
            @endif
        @endcan
        @if(auth()->user()?->role?->can('view-reports'))
            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('reports.index', ['tenant_id' => $tenant->id]) }}">{{ __('reports.statement.view_statement') }}</a>
        @endif
        @can('update', $tenant)
            @if(! $tenant->archived_at)
                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('tenants.edit', $tenant) }}">{{ __('app.actions.edit') }}</a>
            @endif
        @endcan
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
    <div data-tenant-show-card class="rounded border bg-brand-surface p-4 text-sm shadow-sm">
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-brand-muted">{{ __('tenants.fields.phone') }}</dt><dd class="mt-1"><bdi dir="ltr">{{ $tenant->phone ?: __('payments.not_available') }}</bdi></dd></div>
            <div><dt class="text-brand-muted">{{ __('tenants.fields.email') }}</dt><dd class="mt-1"><bdi dir="ltr">{{ $tenant->email ?: __('payments.not_available') }}</bdi></dd></div>
            <div><dt class="text-brand-muted">{{ __('tenants.fields.id_number') }}</dt><dd class="mt-1"><bdi dir="ltr">{{ $tenant->id_number ?: __('payments.not_available') }}</bdi></dd></div>
            <div><dt class="text-brand-muted">{{ __('tenants.fields.nationality') }}</dt><dd class="mt-1">{{ $tenant->nationality ?: __('payments.not_available') }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-brand-muted">{{ __('tenants.fields.notes') }}</dt><dd class="mt-1">{{ $tenant->notes ?: __('payments.not_available') }}</dd></div>
        </dl>
        @if($tenant->archived_at)
            <div class="mt-4 border-t pt-4">
                <p>{{ __('tenants.lifecycle.archived_at') }}: <span dir="ltr">{{ $tenant->archived_at->toDateTimeString() }}</span></p>
                <p>{{ __('tenants.lifecycle.archived_by') }}: {{ $tenant->archivedBy?->name ?? 'N/A' }}</p>
                <p>{{ __('tenants.lifecycle.archive_reason') }}: {{ $tenant->archive_reason }}</p>
            </div>
        @endif
    </div>

    @can('archiveTenant', $tenant)
        @if(! $tenant->archived_at)
            <form method="post" action="{{ route('tenants.archive', $tenant) }}" class="rounded border bg-brand-surface p-4 text-sm shadow-sm">
                @csrf
                @method('patch')
                <p class="mb-3 text-brand-muted">{{ __('tenants.lifecycle.confirm_archive') }}</p>
                <label class="block font-medium">{{ __('tenants.lifecycle.archive_reason') }}
                    <textarea name="archive_reason" rows="5" required class="mt-1 w-full rounded border p-3">{{ old('archive_reason') }}</textarea>
                </label>
                @error('archive_reason')<p class="mt-1 text-sm text-state-danger">{{ $message }}</p>@enderror
                <button class="tap-target mt-3 min-h-11 w-full rounded bg-state-danger px-4 text-sm font-medium text-white">{{ __('tenants.lifecycle.archive') }}</button>
            </form>
        @endif
    @endcan
</div>

<h2 class="mb-2 mt-6 font-semibold">{{ __('tenants.sections.contracts') }}</h2>
<div class="grid gap-3 md:hidden">
    @foreach($tenant->contracts as $contract)
        <article class="rounded border bg-brand-surface p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <h3 class="font-semibold"><bdi dir="ltr">{{ $contract->contract_number }}</bdi></h3>
                <x-status-badge :status="$contract->status" :label="__('contracts.statuses.'.$contract->status)" />
            </div>
            <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('contracts.show', $contract) }}">{{ __('app.actions.view') }}</a>
        </article>
    @endforeach
</div>
<div class="hidden md:block">
    <x-table min-width="min-w-[34rem]">
        <tbody>
            @foreach($tenant->contracts as $contract)
                <tr class="border-t"><td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ $contract->contract_number }}</bdi></td><td class="p-3 whitespace-nowrap"><x-status-badge :status="$contract->status" :label="__('contracts.statuses.'.$contract->status)" /></td><td class="p-3 whitespace-nowrap"><a class="tap-target inline-flex items-center text-brand-primary" href="{{ route('contracts.show', $contract) }}">{{ __('app.actions.view') }}</a></td></tr>
            @endforeach
        </tbody>
    </x-table>
</div>
@endsection
