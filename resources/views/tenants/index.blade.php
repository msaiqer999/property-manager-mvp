@extends('layouts.app')

@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('tenants.title') }}</h1>
    @can('create', App\Models\Tenant::class)
        <a class="tap-target flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('tenants.create') }}">{{ __('tenants.add') }}</a>
    @endcan
</div>

<form class="mb-4 grid gap-3 rounded border bg-white p-3 sm:grid-cols-[1fr_12rem_auto]">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('tenants.search_placeholder') }}" class="tap-target min-h-11 w-full rounded border p-2">
    <select name="lifecycle" class="tap-target min-h-11 w-full rounded border p-2">
        <option value="active" @selected($lifecycle === 'active')>{{ __('tenants.lifecycle.active') }}</option>
        <option value="archived" @selected($lifecycle === 'archived')>{{ __('tenants.lifecycle.archived') }}</option>
        <option value="all" @selected($lifecycle === 'all')>{{ __('tenants.lifecycle.all') }}</option>
    </select>
    <button class="tap-target min-h-11 rounded bg-slate-900 px-4 text-white">{{ __('app.actions.search') }}</button>
</form>

<div data-mobile-tenants-list class="grid gap-3 md:hidden">
    @foreach($tenants as $tenant)
        <article data-tenant-mobile-card class="rounded border bg-white p-4 shadow-sm">
            <div class="grid gap-2">
                <h2 class="min-w-0 break-words text-base font-semibold">{{ $tenant->full_name }}</h2>
                @if($tenant->archived_at)
                    <span class="w-fit rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">{{ __('tenants.lifecycle.archived') }}</span>
                @else
                    <span class="w-fit rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">{{ __('tenants.lifecycle.active') }}</span>
                @endif
            </div>
            <dl class="mt-3 grid gap-2 text-sm">
                <div class="grid min-w-0 gap-1">
                    <dt class="text-slate-500">{{ __('tenants.fields.phone') }}</dt>
                    <dd class="min-w-0 break-words"><bdi dir="ltr">{{ $tenant->phone ?: __('payments.not_available') }}</bdi></dd>
                </div>
                @if($tenant->email)
                    <div class="grid min-w-0 gap-1">
                        <dt class="text-slate-500">{{ __('tenants.fields.email') }}</dt>
                        <dd class="min-w-0 break-words"><bdi dir="ltr">{{ $tenant->email }}</bdi></dd>
                    </div>
                @endif
                @if($tenant->id_number)
                    <div class="grid min-w-0 gap-1">
                        <dt class="text-slate-500">{{ __('tenants.fields.id_number') }}</dt>
                        <dd class="min-w-0 break-words"><bdi dir="ltr">{{ $tenant->id_number }}</bdi></dd>
                    </div>
                @endif
            </dl>
            <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('tenants.show', $tenant) }}">{{ __('app.actions.view') }}</a>
        </article>
    @endforeach
</div>

<div class="hidden md:block">
    <x-table min-width="min-w-[44rem]">
        <tbody>
            @foreach($tenants as $tenant)
                <tr class="border-t">
                    <td class="p-4 font-medium">{{ $tenant->full_name }}</td>
                    <td class="p-4 whitespace-nowrap"><bdi dir="ltr">{{ $tenant->phone }}</bdi></td>
                    <td class="p-4 whitespace-nowrap">@if($tenant->archived_at)<span class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">{{ __('tenants.lifecycle.archived') }}</span>@else<span class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">{{ __('tenants.lifecycle.active') }}</span>@endif</td>
                    <td class="p-4 whitespace-nowrap"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('tenants.show', $tenant) }}">{{ __('app.actions.view') }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</div>

{{ $tenants->links() }}
@endsection
