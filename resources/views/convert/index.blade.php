<x-app-layout>
    <x-header-content>
        {{ __('My Converts') }}
        <x-slot name="button">
            <x-link href="{{ route('converts.create') }}">
                {{ __('New Convert') }}
            </x-link>
        </x-slot>
    </x-header-content>

    <x-container>
        {{-- <x-h-info>{{ __('My Converts') }}</x-h-info> --}}

        @if (is_null($converts) || $converts->isEmpty())
            <x-no-records>
                {{ __('No converts found.') }}
            </x-no-records>
        @else
            <div class="overflow-x-auto">
                <x-table>
                    <x-table-header>
                        <x-table-row>
                            <x-table-header-cell>{{ __('ID') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Actions') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Status') }}</x-table-header-cell>

                            <x-table-header-cell>{{ __('SQL Database Driver') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('SQL Database') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('MongoDB Database') }}</x-table-header-cell>

                            <x-table-header-cell>{{ __('Description') }}</x-table-header-cell>


                        </x-table-row>
                    </x-table-header>
                    <tbody>
                        @foreach ($converts as $convert)
                            <x-table-row :class="$loop->even ? 'bg-light' : 'bg-white'">
                                <x-table-cell>{{ $convert->id }}</x-table-cell>
                                <x-table-cell>
                                    <x-icon-link href="{{ route('converts.show', $convert->id) }}" :icon="'icons.view'" />
                                </x-table-cell>
                                <x-table-cell><x-status-badge :status="$convert->status" /></x-table-cell>

                                <x-table-cell>{{ $convert->sqlDatabase->driver }}</x-table-cell>
                                <x-table-cell>{{ $convert->sqlDatabase->database }}</x-table-cell>
                                <x-table-cell>{{ $convert->mongoDatabase->database }}</x-table-cell>
                                <x-table-cell>{{ $convert->description ?? __('No description') }}</x-table-cell>


                            </x-table-row>
                        @endforeach
                    </tbody>
                </x-table>

            </div>
        @endif
    </x-container>
</x-app-layout>
