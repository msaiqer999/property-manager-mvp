@extends('layouts.app')
@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between"><h1 class="text-xl font-semibold">{{ __('tenants.title') }}</h1><a class="tap-target flex items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('tenants.create') }}">{{ __('tenants.add') }}</a></div>
<form class="mb-4 grid gap-3 rounded border bg-white p-3 sm:grid-cols-[1fr_auto]"><input name="search" value="{{ request('search') }}" placeholder="{{ __('tenants.search_placeholder') }}" class="tap-target w-full rounded border p-2"><button class="tap-target rounded bg-slate-900 px-4 text-white">{{ __('app.actions.search') }}</button></form>
<x-table min-width="min-w-[34rem]"><tbody>@foreach($tenants as $tenant)<tr class="border-t"><td class="p-4 font-medium">{{ $tenant->full_name }}</td><td class="p-4 whitespace-nowrap"><bdi dir="ltr">{{ $tenant->phone }}</bdi></td><td class="p-4 whitespace-nowrap"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('tenants.show', $tenant) }}">{{ __('app.actions.view') }}</a></td></tr>@endforeach</tbody></x-table>
{{ $tenants->links() }}
@endsection
