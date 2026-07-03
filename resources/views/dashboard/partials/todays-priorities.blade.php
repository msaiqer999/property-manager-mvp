@php
    $formatDashboardMoney = $formatDashboardMoney ?? fn ($value) => 'AED '.number_format((float) $value, 2);
    $todayPriorities = $todayPriorities ?? [
        [
            'label' => __('app.dashboard.attention_overdue_payments'),
            'count' => $overduePaymentCount ?? 0,
            'body' => ($overduePaymentCount ?? 0) > 0
                ? __('app.dashboard.priority_overdue_body', ['amount' => $formatDashboardMoney($overdueAmount ?? 0)])
                : __('app.dashboard.priority_no_overdue'),
            'meta' => isset($nearestOverduePaymentDate) && $nearestOverduePaymentDate
                ? __('app.dashboard.priority_nearest_due', ['date' => $nearestOverduePaymentDate->toDateString()])
                : null,
            'action' => __('app.dashboard.follow_up_overdue'),
            'href' => route('payments.index', ['overdue' => 1]),
        ],
        [
            'label' => __('app.dashboard.priority_due_soon'),
            'count' => $paymentsDueSoonCount ?? 0,
            'body' => ($paymentsDueSoonCount ?? 0) > 0
                ? __('app.dashboard.priority_due_soon_body')
                : __('app.dashboard.priority_no_due_soon'),
            'meta' => isset($nearestPaymentDueDate) && $nearestPaymentDueDate
                ? __('app.dashboard.priority_nearest_due', ['date' => $nearestPaymentDueDate->toDateString()])
                : null,
            'action' => __('app.dashboard.review_due_payments'),
            'href' => route('payments.index'),
        ],
        [
            'label' => __('app.dashboard.attention_expiring_contracts'),
            'count' => $expiringSoonCount ?? 0,
            'body' => ($expiringSoonCount ?? 0) > 0
                ? __('app.dashboard.priority_expiring_body')
                : __('app.dashboard.priority_no_expiring'),
            'meta' => isset($nearestContractExpiryDate) && $nearestContractExpiryDate
                ? __('app.dashboard.priority_nearest_expiry', ['date' => $nearestContractExpiryDate->toDateString()])
                : null,
            'action' => __('app.dashboard.review_expiring_contracts'),
            'href' => route('contracts.index'),
        ],
        [
            'label' => __('app.dashboard.attention_vacant_units'),
            'count' => $vacantUnits ?? 0,
            'body' => ($vacantUnits ?? 0) > 0
                ? __('app.dashboard.priority_vacant_body')
                : __('app.dashboard.priority_no_vacant'),
            'meta' => null,
            'action' => __('app.dashboard.rent_vacant_units'),
            'href' => route('units.index', ['status' => 'vacant']),
        ],
    ];
@endphp

<section data-dashboard-priorities class="mt-6 rounded border bg-white p-4 shadow-sm sm:p-5">
    <div class="mb-3">
        <h2 class="text-lg font-semibold sm:text-base">{{ __('app.dashboard.today_priorities') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('app.dashboard.daily_actions_title') }} &middot; {{ __('app.dashboard.needs_attention') }}</p>
    </div>
    <div class="grid items-stretch gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($todayPriorities as $item)
            <a href="{{ $item['href'] }}" class="tap-target group flex min-h-44 flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:bg-blue-50/40 focus:outline-none focus:ring-2 focus:ring-blue-200">
                <div>
                    <p class="text-sm font-semibold leading-5 text-slate-800">{{ $item['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold leading-none tracking-tight text-slate-950" dir="ltr"><bdi>{{ number_format((int) ($item['count'] ?? 0)) }}</bdi></p>
                    @if($item['meta'] ?? null)
                        <p class="mt-2 text-xs font-medium text-slate-500"><bdi dir="ltr">{{ $item['meta'] }}</bdi></p>
                    @endif
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $item['body'] }}</p>
                </div>
                <span class="mt-4 inline-flex min-h-10 items-center justify-center rounded border border-slate-200 bg-slate-50 px-3 text-center text-sm font-medium text-slate-800 transition group-hover:border-blue-200 group-hover:bg-white group-hover:text-blue-800">
                    {{ $item['action'] }}
                </span>
            </a>
        @endforeach
    </div>
    @if(collect($todayPriorities)->every(fn ($item) => (int) ($item['count'] ?? 0) === 0))
        <p class="mt-3 text-sm text-slate-500">{{ __('app.dashboard.no_attention_items') }}</p>
    @endif
</section>
