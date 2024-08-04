<x-app-layout>
    <x-header-content>
        {{ __('Converts') }}
        <x-slot name="button">
            <x-link href="{{ route('converts.create') }}" >
                {{ __('New Convert') }}
            </x-link>
        </x-slot>
    </x-header-content>

    <div class="container mx-auto p-6">
        <h1 class="text-2xl text-info font-semibold mb-4">{{ __('My Converts') }}</h1>
    
        @if(is_null($converts) || $converts->isEmpty())
            <div class="bg-light text-warning p-4 rounded-lg">
                <p class="font-medium">{{ __('No converts found.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray rounded-lg overflow-hidden">
                    <thead class="bg-light border-b border-gray text-secondary">
                        <tr>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">{{ __('ID') }}</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">{{ __('SQL Database') }}</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">{{ __('MongoDB Database') }}</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">{{ __('Description') }}</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">{{ __('Status') }}</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($converts as $convert)
                            <tr class="{{ $loop->even ? 'bg-white' : 'bg-light' }} border-b border-gray-200">
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->id }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->sqlDatabase->database ?? 'N/A' }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->mongoDatabase->database ?? 'N/A' }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->description ?? __('No description') }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">
                                    @switch($convert->status)
                                        @case('In progress')
                                            <span class="text-warning">{{ __('In progress') }}</span>
                                            @break
                                        @case('Completed')
                                            <span class="text-success">{{ __('Completed') }}</span>
                                            @break
                                        @case('Failed')
                                            <span class="text-danger">{{ __('Failed') }}</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="py-3 px-6 text-sm text-gray-900">
                                    {{-- <a href="{{ route('converts.show', $convert->id) }}" class="text-blue-500 hover:underline">View</a> --}}
                                    <a href="#" class="text-accent hover:underline">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
            </div>
        @endif
    </div>
</x-app-layout>
