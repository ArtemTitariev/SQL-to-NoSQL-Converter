@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-secondary text-start text-base font-medium text-secondary bg-light hover:text-primary hover:bg-gray-50 hover:border-secondary focus:outline-none focus:text-primary focus:border-primary transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-secondary bg-light hover:text-primary hover:bg-gray-50 hover:border-secondary focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
