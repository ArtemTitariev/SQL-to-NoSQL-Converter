<div {{ $attributes->merge(['class' => '']) }}>
    <span class="text-nowrap p-1 rounded {{ $badgeClass }}">
        {{ __($status) }}
    </span>
</div>