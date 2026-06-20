@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between gap-3"><h1 class="text-xl font-semibold">{{ $tenant->full_name }}</h1><a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('tenants.edit', $tenant) }}">{{ __('app.actions.edit') }}</a></div>
<div class="rounded border bg-white p-4 text-sm"><p><bdi dir="ltr">{{ $tenant->phone }}</bdi></p><p><bdi dir="ltr">{{ $tenant->email }}</bdi></p><p><bdi dir="ltr">{{ $tenant->id_number }}</bdi></p></div>
<h2 class="mb-2 mt-6 font-semibold">{{ __('tenants.sections.contracts') }}</h2>
<x-table min-width="min-w-[34rem]"><tbody>@foreach($tenant->contracts as $contract)<tr class="border-t"><td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ $contract->contract_number }}</bdi></td><td class="p-3 whitespace-nowrap">{{ __('contracts.statuses.'.$contract->status) }}</td><td class="p-3 whitespace-nowrap"><a class="tap-target inline-flex items-center text-blue-700" href="{{ route('contracts.show', $contract) }}">{{ __('app.actions.view') }}</a></td></tr>@endforeach</tbody></x-table>
@endsection
