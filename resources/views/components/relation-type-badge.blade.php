{{-- @props(['relation_type']) --}}

<div {{ $attributes->merge(['class' => 'font-bold']) }}>
    <span class="text-nowrap p-1 rounded bg-light {{ $getBadgeClass() }}">
        {{ $getRelationDescription() }}
    </span>
</div>
