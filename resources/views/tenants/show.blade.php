@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">{{ $tenant->full_name }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            @if($tenant->archived_at)
                <span class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">{{ __('tenants.lifecycle.archived') }}</span>
            @else
                <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">{{ __('tenants.lifecycle.active') }}</span>
            @endif
        </p>
    </div>
    @can('update', $tenant)
        @if(! $tenant->archived_at)
            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('tenants.edit', $tenant) }}">{{ __('app.actions.edit') }}</a>
        @endif
    @endcan
</div>

<div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
    <div data-tenant-show-card class="rounded border bg-white p-4 text-sm shadow-sm">
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-slate-500">{{ __('tenants.fields.phone') }}</dt><dd class="mt-1"><bdi dir="ltr">{{ $tenant->phone ?: __('payments.not_available') }}</bdi></dd></div>
            <div><dt class="text-slate-500">{{ __('tenants.fields.email') }}</dt><dd class="mt-1"><bdi dir="ltr">{{ $tenant->email ?: __('payments.not_available') }}</bdi></dd></div>
            <div><dt class="text-slate-500">{{ __('tenants.fields.id_number') }}</dt><dd class="mt-1"><bdi dir="ltr">{{ $tenant->id_number ?: __('payments.not_available') }}</bdi></dd></div>
            <div><dt class="text-slate-500">{{ __('tenants.fields.nationality') }}</dt><dd class="mt-1">{{ $tenant->nationality ?: __('payments.not_available') }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">{{ __('tenants.fields.notes') }}</dt><dd class="mt-1">{{ $tenant->notes ?: __('payments.not_available') }}</dd></div>
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
            <form method="post" action="{{ route('tenants.archive', $tenant) }}" class="rounded border bg-white p-4 text-sm shadow-sm">
                @csrf
                @method('patch')
                <p class="mb-3 text-slate-600">{{ __('tenants.lifecycle.confirm_archive') }}</p>
                <label class="block font-medium">{{ __('tenants.lifecycle.archive_reason') }}
                    <textarea name="archive_reason" rows="5" required class="mt-1 w-full rounded border p-3">{{ old('archive_reason') }}</textarea>
                </label>
                @error('archive_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="tap-target mt-3 min-h-11 w-full rounded bg-amber-700 px-4 text-sm font-medium text-white">{{ __('tenants.lifecycle.archive') }}</button>
            </form>
        @endif
    @endcan
</div>

<h2 class="mb-2 mt-6 font-semibold">{{ __('tenants.sections.contracts') }}</h2>
<div class="grid gap-3 md:hidden">
    @foreach($tenant->contracts as $contract)
        <article class="rounded border bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <h3 class="font-semibold"><bdi dir="ltr">{{ $contract->contract_number }}</bdi></h3>
                <span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ __('contracts.statuses.'.$contract->status) }}</span>
            </div>
            <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('contracts.show', $contract) }}">{{ __('app.actions.view') }}</a>
        </article>
    @endforeach
</div>
<div class="hidden md:block">
    <x-table min-width="min-w-[34rem]">
        <tbody>
            @foreach($tenant->contracts as $contract)
                <tr class="border-t"><td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ $contract->contract_number }}</bdi></td><td class="p-3 whitespace-nowrap">{{ __('contracts.statuses.'.$contract->status) }}</td><td class="p-3 whitespace-nowrap"><a class="tap-target inline-flex items-center text-blue-700" href="{{ route('contracts.show', $contract) }}">{{ __('app.actions.view') }}</a></td></tr>
            @endforeach
        </tbody>
    </x-table>
</div>
@endsection
