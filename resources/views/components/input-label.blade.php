@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm-1 text-primary']) }}>
    {{ $value ?? $slot }}
</label>
