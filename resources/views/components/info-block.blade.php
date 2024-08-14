<div {{ $attributes->merge(['class' => 'closest bg-info text-white my-4 py-6 px-4 shadow-md rounded-md max-w-7xl mx-auto sm:px-6 lg:px-8']) }}>
    <div class="flex justify-between items-center">
        <div class="info-content">
            {{ $slot }}
        </div>
        <button id="close-info-block" class="text-white bg-transparent border-none text-lg">
            <x-icons.close />
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const closeButton = document.getElementById('close-info-block');
        const infoBlock = closeButton.closest('.closest');
        closeButton.addEventListener('click', function() {
            infoBlock.style.display = 'none';
        });
    });
</script>
