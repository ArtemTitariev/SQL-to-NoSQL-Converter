@props(['iconColor' => 'text-info', 'border' => 'border-info', 'position' => 'bottom'])

@php
$positionClasses = [
    'top' => 'bottom-full left-1/2 transform -translate-x-1/2 mb-2',
    'bottom' => 'top-full left-1/2 transform -translate-x-1/2 mt-2',
    'right' => 'left-full top-1/2 transform -translate-y-1/2 ml-2',
    'left' => 'right-full top-1/2 transform -translate-y-1/2 mr-2',
];
@endphp

<div {{ $attributes->merge(['class' => 'relative flex items-center group']) }} style="padding: 10px;">
    <div class="relative flex items-center">
        <x-icons.help class="ml-2 w-5 h-5 cursor-pointer {{ $iconColor }}" />
        <div class="absolute invisible group-hover:visible group-hover:opacity-100 opacity-0 transition-opacity duration-200 bg-white text-gray border-2 {{ $border }} text-sm rounded-lg shadow-lg py-2 px-4 z-10 {{ $positionClasses[$position] }}">
            {{ $slot }}
        </div>
    </div>
</div>
