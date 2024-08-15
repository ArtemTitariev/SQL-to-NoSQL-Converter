<div {{ $attributes->merge(['class' => '']) }}>
    <span class="p-1 rounded {{ $badgeClass }}">
        {{ __($status) }}
    </span>
</div>