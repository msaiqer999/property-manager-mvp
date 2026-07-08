@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-sm text-slate-500">{{ auth()->user()->organization->name }}</p>
        <h1 class="text-2xl font-semibold">{{ __('feedback.index_title') }}</h1>
    </div>
</div>

<div class="overflow-hidden rounded border bg-white shadow-sm">
    @if($feedback->isEmpty())
        <section data-feedback-empty-state class="p-6 text-center sm:p-8">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('feedback.empty_title') }}</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">{{ __('feedback.empty_body') }}</p>
        </section>
    @else
    <div class="hidden overflow-x-auto md:block">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3 text-start">{{ __('feedback.created_at') }}</th>
                    <th class="p-3 text-start">{{ __('feedback.user') }}</th>
                    <th class="p-3 text-start">{{ __('feedback.type') }}</th>
                    <th class="p-3 text-start">{{ __('feedback.status') }}</th>
                    <th class="p-3 text-start">{{ __('feedback.page_url') }}</th>
                    <th class="p-3 text-start">{{ __('feedback.message') }}</th>
                    <th class="p-3 text-start">{{ __('feedback.screenshot_note_short') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feedback as $item)
                    <tr class="border-t align-top">
                        <td class="p-3"><bdi>{{ $item->created_at->toDateTimeString() }}</bdi></td>
                        <td class="p-3">{{ $item->user?->name }}</td>
                        <td class="p-3">{{ __('feedback.types.'.$item->type) }}</td>
                        <td class="p-3">{{ __('feedback.statuses.'.$item->status) }}</td>
                        <td class="max-w-xs break-words p-3"><bdi>{{ $item->page_url }}</bdi></td>
                        <td class="p-3">{{ $item->message }}</td>
                        <td class="p-3">{{ $item->screenshot_note ?: __('payments.not_available') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-4 text-center text-slate-500" colspan="7">{{ __('feedback.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid gap-3 p-3 md:hidden">
        @forelse($feedback as $item)
            <article class="rounded border p-3 text-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ __('feedback.types.'.$item->type) }}</p>
                        <p class="text-slate-500">{{ $item->user?->name }} · <bdi>{{ $item->created_at->toDateString() }}</bdi></p>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ __('feedback.statuses.'.$item->status) }}</span>
                </div>
                <p class="mt-3">{{ $item->message }}</p>
                @if($item->screenshot_note)
                    <p class="mt-2 rounded bg-slate-50 p-2 text-xs text-slate-600">{{ $item->screenshot_note }}</p>
                @endif
                <p class="mt-2 break-words text-xs text-slate-500"><bdi>{{ $item->page_url }}</bdi></p>
            </article>
        @empty
            <p class="text-sm text-slate-500">{{ __('feedback.empty') }}</p>
        @endforelse
    </div>
    @endif
</div>

<div class="mt-4">
    {{ $feedback->links() }}
</div>
@endsection
