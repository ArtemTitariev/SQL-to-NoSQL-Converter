<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-secondary leading-tight">
            {{ $slot }}
        </h2>
        <div class="flex items-center space-x-4">
            {{ $button ?? '' }}

            {{ $modalButton ?? '' }}
        </div>
    </div>    
</x-slot>