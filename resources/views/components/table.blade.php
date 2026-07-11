@props(['minWidth' => 'min-w-full'])

<div data-mobile-table class="table-shell mobile-table scrollbar-soft -mx-4 max-w-[calc(100%+2rem)] overflow-x-auto border-y sm:mx-0 sm:max-w-full sm:rounded sm:border">
    <table {{ $attributes->class([$minWidth, 'w-full divide-y text-sm']) }}>
        {{ $slot }}
    </table>
</div>
