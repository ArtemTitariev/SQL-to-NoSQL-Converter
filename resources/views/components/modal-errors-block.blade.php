<div {{ $attributes->merge(['class' => 'hidden mb-4 bg-red-50 text-danger border border-danger rounded-lg p-4']) }}>
    <div id="error-title" class="text-center text-lg font-bold mb-2">{{ __('Fix the following errors:') }}</div>
    <ul id="error-list" class="list-disc list-inside text-normal space-y-2">
    </ul>
</div>
