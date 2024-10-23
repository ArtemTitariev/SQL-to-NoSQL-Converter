<div {{ $attributes->merge(['class' => 'font-bold']) }}>
    <span class="text-nowrap p-1 rounded bg-neutral-100 {{ $getBadgeClass() }}">
        {{ $getRelationDescription() }}
    </span>
</div>