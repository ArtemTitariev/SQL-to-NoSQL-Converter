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
        <h1 class="text-2xl text-info font-semibold mb-4">Convert List</h1>
    
        @if(is_null($converts) || $converts->isEmpty())
            <div class="bg-light text-warning p-4 rounded-lg">
                <p class="font-medium">No converts found.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray rounded-lg overflow-hidden">
                    <thead class="bg-light border-b border-gray text-secondary">
                        <tr>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">ID</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">User</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">SQL Database</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">Mongo Database</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">Description</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">Status</th>
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($converts as $convert)
                            <tr class="{{ $loop->even ? 'bg-white' : 'bg-light' }} border-b border-gray-200">
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->id }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->user->name ?? 'N/A' }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->sqlDatabase->connection_name ?? 'N/A' }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->mongoDatabase->connection_name ?? 'N/A' }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">{{ $convert->description ?? 'No description' }}</td>
                                <td class="py-3 px-6 text-sm text-gray-900">
                                    @switch($convert->status)
                                        @case('In progress')
                                            <span class="text-warning">In progress</span>
                                            @break
                                        @case('Completed')
                                            <span class="text-success">Completed</span>
                                            @break
                                        @case('Failed')
                                            <span class="text-danger">Failed</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="py-3 px-6 text-sm text-gray-900">
                                    {{-- <a href="{{ route('converts.show', $convert->id) }}" class="text-blue-500 hover:underline">View</a> --}}
                                    <a href="#" class="text-accent hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
            </div>
        @endif
    </div>
</x-app-layout>
