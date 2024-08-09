<x-app-layout>
    <x-header-content>
        {{ __('Converts') }}
        <x-slot name="button">
            <x-link href="{{ route('converts.index') }}">
                {{ __('Continue') }}
            </x-link>
        </x-slot>
    </x-header-content>

    <x-container>
        <x-h-info>{{ __('Conversion Details') }}</x-h-info>

        <div class="mb-4">
            <!-- Convert Details -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                    <!-- SQL Database -->
                    <div class="p-4 bg-light rounded-lg shadow-sm flex items-center">
                        <div class="flex-shrink-0">

                            <img src="{{ asset("database-icons/{$convert->sqlDatabase->driver}.png") }}"
                                alt="{{ $convert->sqlDatabase->driver }}" class="w-16 h-16 object-cover">

                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-semibold text-secondary">{{ __('SQL Database') }}</h2>
                            <p class="mt-2"><strong>{{ __('Driver:') }}</strong> {{ $convert->sqlDatabase->driver }}
                            </p>
                            <p><strong>{{ __('Database:') }}</strong> {{ $convert->sqlDatabase->database }}</p>
                        </div>
                    </div>
                    <!-- Mongo Database -->
                    <div class="p-4 bg-light rounded-lg shadow-sm flex items-center">
                        <div class="flex-shrink-0">
                            <img src="{{ asset('database-icons/MongoDB.png') }}"
                                alt="{{ $convert->mongoDatabase->driver }}" class="w-16 h-16 object-cover">
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-semibold text-secondary">{{ __('MongoDB Database') }}</h2>
                            <p class="mt-2"><strong>{{ __('Database:') }}</strong> {{ $convert->mongoDatabase->database }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Convert Description -->
            @if ($convert->description)
                <div class="bg-white p-6 rounded-lg shadow-lg mt-4 mb-4">
                    <h2 class="text-xl font-bold text-info font-sans">{{ __('Description') }}</h2>
                    <p class="mt-2">{{ $convert->description }}</p>
                </div>
            @endif
        </div>

        <!-- Progress Information -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 text-info font-sans">{{ __('Progress Information') }}</h2>
            {{-- @if ($convert->progresses->isEmpty()) --}}
            @if ($convert->progresses->isEmpty())
            <x-no-records>
                {{ __('No records found.') }}
            </x-no-records>
            @else
                <div class="overflow-x-auto">
                    <x-table>
                        <x-table-header>
                            <x-table-header-cell>{{ __('Step') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Status') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Details') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Started At') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Finished At') }}</x-table-header-cell>
                        </x-table-header>
                        <tbody class="bg-white divide-y">
                            @foreach ($convert->progresses as $progress)
                                <x-table-row :class="$loop->even ? 'bg-light' : 'bg-white'">
                                    <x-table-cell>{{ $progress->step }}</x-table-cell>
                                    <x-table-cell>
                                        <x-status-badge :status="$progress->status" />
                                    </x-table-cell>
                                    <x-table-cell>{{ __($progress->details) }}</x-table-cell>
                                    <x-table-cell>{{ $progress->created_at->format('Y-m-d H:i:s') }}</x-table-cell>
                                    <x-table-cell>{{ $progress->updated_at->format('Y-m-d H:i:s') }}</x-table-cell>
                                </x-table-row>
                            @endforeach
                        </tbody>
                    </x-table>
                </div>
            @endif
        </div>
    </x-container>
</x-app-layout>
