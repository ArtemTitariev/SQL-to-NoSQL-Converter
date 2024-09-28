@props(['disabled' => false])

<textarea {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'focus:border-secondary focus:ring-secondary rounded-md shadow-sm',
]) !!}>{{ $slot }}</textarea>
