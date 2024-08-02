@props(['disabled' => false, 'name', 'id', 'value' => null, 'label' => null])

<label for="{{ $id }}" class="inline-flex items-center">
    <input
        type="checkbox"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge(['class' => 'rounded border-gray-300 text-primary shadow-sm focus:ring-primary']) !!}
    />
    @if ($label)
        <span class="ms-2 text-sm-2 text-primary">
            {{ $label }}
        </span>
    @endif
</label>
