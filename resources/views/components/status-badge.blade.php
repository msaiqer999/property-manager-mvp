@props(['status', 'label' => null])

@php
    $statusKey = str_replace('_', '-', strtolower((string) $status));
    $variant = match ($statusKey) {
        'paid', 'active', 'completed', 'available', 'vacant', 'ready' => 'success',
        'pending', 'partial', 'needs-attention', 'ending-soon', 'maintenance', 'archived', 'voided', 'terminated' => 'warning',
        'overdue', 'partial-overdue', 'failed', 'expired', 'critical' => 'danger',
        'inactive', 'cancelled', 'rented' => 'neutral',
        default => 'info',
    };
@endphp

<span {{ $attributes->class(["badge-{$variant}"]) }}>
    {{ $label ?? $slot }}
</span>
