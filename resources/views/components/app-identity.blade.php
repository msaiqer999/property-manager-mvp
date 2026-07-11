@props(['href' => null, 'subtitle' => null])

@if($href)
    <a href="{{ $href }}" {{ $attributes->class(['app-identity']) }}>
        <span class="brand-mark" aria-hidden="true">PM</span>
        <span class="min-w-0">
            <span class="app-identity-text">{{ __('app.name') }}</span>
            @if($subtitle)
                <span class="app-identity-subtitle">{{ $subtitle }}</span>
            @endif
        </span>
    </a>
@else
    <div {{ $attributes->class(['app-identity']) }}>
        <span class="brand-mark" aria-hidden="true">PM</span>
        <span class="min-w-0">
            <span class="app-identity-text">{{ __('app.name') }}</span>
            @if($subtitle)
                <span class="app-identity-subtitle">{{ $subtitle }}</span>
            @endif
        </span>
    </div>
@endif
