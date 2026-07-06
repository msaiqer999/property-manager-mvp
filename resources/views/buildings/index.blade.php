@extends('layouts.app')

@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('buildings.title') }}</h1>
    <a class="tap-target flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('buildings.create') }}">{{ __('buildings.add') }}</a>
</div>

@if($buildings->isEmpty())
    <section data-empty-state-buildings class="rounded border bg-white p-5 text-center shadow-sm sm:p-6">
        <h2 class="text-lg font-semibold text-slate-950">{{ __('app.empty_states.buildings.title') }}</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">{{ __('app.empty_states.buildings.body') }}</p>
        <a class="tap-target mt-4 inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('buildings.create') }}">{{ __('app.empty_states.buildings.action') }}</a>
    </section>
@else
    <div data-mobile-buildings-list class="grid gap-3 md:hidden">
        @foreach($buildings as $building)
            <article data-building-mobile-card class="rounded border bg-white p-4 shadow-sm">
                <h2 class="break-words text-base font-semibold">{{ $building->name }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $building->location ?: __('payments.not_available') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ __('buildings.sections.units') }}: <bdi dir="ltr">{{ $building->units_count }}</bdi></p>
                <div class="mt-4 grid gap-2">
                    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('buildings.show', $building) }}">{{ __('app.actions.view') }}</a>
                    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('units.create', ['building_id' => $building->id]) }}">{{ __('units.add') }}</a>
                    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('buildings.units.bulk.create', $building) }}">{{ __('units.bulk.add_multiple') }}</a>
                </div>
            </article>
        @endforeach
    </div>

    <div class="hidden md:block">
        <x-table min-width="min-w-[36rem]">
            <thead>
                <tr>
                    <th class="p-4 text-start">{{ __('buildings.fields.name') }}</th>
                    <th class="p-4 text-start">{{ __('buildings.fields.location') }}</th>
                    <th class="p-4 text-center">{{ __('buildings.fields.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($buildings as $building)
                    <tr class="border-t">
                        <td class="p-4 font-medium">{{ $building->name }}</td>
                        <td class="p-4">{{ $building->location }}</td>
                        <td class="p-4 text-center"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('buildings.show', $building) }}">{{ __('app.actions.view') }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </div>

    {{ $buildings->links() }}
@endif
@endsection
