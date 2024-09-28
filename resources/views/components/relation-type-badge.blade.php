{{-- @props(['relation_type']) --}}

<div {{ $attributes->merge(['class' => 'font-bold']) }}>
    <span class="p-1 rounded bg-gray-200 {{ $badgeClass }}">
        {{ $getRelationDescription() }}
    </span>
</div>
