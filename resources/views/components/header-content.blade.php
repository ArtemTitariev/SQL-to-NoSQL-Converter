<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-secondary leading-tight">
            {{ $slot }}
        </h2>
        <div>
            {{ $button ?? '' }}
        </div>
    </div>    
</x-slot>