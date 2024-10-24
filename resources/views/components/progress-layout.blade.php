<x-app-layout>
    <x-header-content>
        {{ __('Processing...') }}

        <x-slot name="button">
            <x-link href="{{ route('converts.index') }}">
                <x-icons.left-arrow class="inline-block mr-2"></x-icons.left-arrow>
                {{ __('Back') }}
            </x-link>
        </x-slot>
    </x-header-content>

    <div class="flex flex-col items-center justify-center text-center p-4 flex-grow h-full">
        <!-- Динамічний індикатор прогресу -->
        <div class="my-20" id="progress-steps">
            <div class="flex space-x-4">
                <div id="step1" class="w-5 h-5 bg-gray-300 rounded-full animate-pulse"></div>
                <div id="step2" class="w-5 h-5 bg-gray-300 rounded-full"></div>
                <div id="step3" class="w-5 h-5 bg-gray-300 rounded-full"></div>
                <div id="step4" class="w-5 h-5 bg-gray-300 rounded-full"></div>
            </div>
        </div>

        <!-- Основний текст -->
        <h1 class="text-xl font-bold mb-6">
            {{ $title }}
        </h1>
        <h3 class="text-lg mb-6">
            {{ $message }}
        </h3>
        <h3 class="text-lg mb-6">
            {{ $additionalMessage }}
            <a class="font-medium text-secondary underline hover:no-underline"
                href="{{ $linkHref }}">{{ $linkText }}</a>.
        </h3>
    </div>

    {{ $slot }}

    <script>
        $(document).ready(function() {
            // Динамічна зміна кольору для індикатора кроків
            let steps = ['#step1', '#step2', '#step3', '#step4'];
            let currentStep = 0;

            setInterval(() => {
                // Скидаємо кольори
                steps.forEach(step => $(step).removeClass('bg-primary').addClass('bg-gray-300'));
                // Встановлюємо активний крок
                $(steps[currentStep]).removeClass('bg-gray-300').addClass('bg-primary');

                // Перемикаємо на наступний крок або повертаємось на початок
                currentStep = (currentStep + 1) % steps.length;
            }, 1000);
        });
    </script>
</x-app-layout>
