@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mb-4 bg-red-50 text-danger border border-red-danger rounded-lg p-4']) }}>
        <div class="text-center font-bold mb-2">{{ __('Fix the following errors:') }}</div>
        <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

