<a {{ $attributes->merge(['href' => '#', 'class' => 'text-accent hover:text-info hover:underline']) }}>
    @isset($icon)
        <x-dynamic-component :component="$icon" class="inline-block" />
    @endisset
    {{ $slot }}
</a>
