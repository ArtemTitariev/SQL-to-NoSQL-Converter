{{-- @props(['relation_type']) --}}

<div {{ $attributes->merge(['class' => 'font-bold']) }}>
    <span class="text-nowrap p-1 rounded bg-gray-200 {{ $getBadgeClass() }}">
        {{ $getRelationDescription() }}
    </span>
</div>
