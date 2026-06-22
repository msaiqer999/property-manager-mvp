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
            <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('tenants.edit', $tenant) }}">{{ __('app.actions.edit') }}</a>
        @endif
    @endcan
</div>
<div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
<div class="rounded border bg-white p-4 text-sm"><p><bdi dir="ltr">{{ $tenant->phone }}</bdi></p><p><bdi dir="ltr">{{ $tenant->email }}</bdi></p><p><bdi dir="ltr">{{ $tenant->id_number }}</bdi></p>@if($tenant->archived_at)<div class="mt-4 border-t pt-4"><p>{{ __('tenants.lifecycle.archived_at') }}: <span dir="ltr">{{ $tenant->archived_at->toDateTimeString() }}</span></p><p>{{ __('tenants.lifecycle.archived_by') }}: {{ $tenant->archivedBy?->name ?? 'N/A' }}</p><p>{{ __('tenants.lifecycle.archive_reason') }}: {{ $tenant->archive_reason }}</p></div>@endif</div>
@can('archiveTenant', $tenant)
    @if(! $tenant->archived_at)
        <form method="post" action="{{ route('tenants.archive', $tenant) }}" class="rounded border bg-white p-4 text-sm">
            @csrf
            @method('patch')
            <p class="mb-3 text-slate-600">{{ __('tenants.lifecycle.confirm_archive') }}</p>
            <label class="block font-medium">{{ __('tenants.lifecycle.archive_reason') }}
                <textarea name="archive_reason" rows="5" required class="mt-1 w-full rounded border p-2">{{ old('archive_reason') }}</textarea>
            </label>
            @error('archive_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <button class="tap-target mt-3 w-full rounded bg-amber-700 px-4 text-sm font-medium text-white">{{ __('tenants.lifecycle.archive') }}</button>
        </form>
    @endif
@endcan
</div>
<h2 class="mb-2 mt-6 font-semibold">{{ __('tenants.sections.contracts') }}</h2>
<x-table min-width="min-w-[34rem]"><tbody>@foreach($tenant->contracts as $contract)<tr class="border-t"><td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ $contract->contract_number }}</bdi></td><td class="p-3 whitespace-nowrap">{{ __('contracts.statuses.'.$contract->status) }}</td><td class="p-3 whitespace-nowrap"><a class="tap-target inline-flex items-center text-blue-700" href="{{ route('contracts.show', $contract) }}">{{ __('app.actions.view') }}</a></td></tr>@endforeach</tbody></x-table>
@endsection
