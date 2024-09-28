@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'focus:border-secondary focus:ring-secondary rounded-md shadow-sm']) !!}>
