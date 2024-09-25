<x-app-layout>
    <x-header-content>
        {{ __('Adjust Relationships') }}
    </x-header-content>

    @php
        $mongoDB = $convert->mongoDatabase;
        $collections = $mongoDB
            ->collections()
            ->with(['fields'])
            ->get();
    @endphp

    @foreach ($collections as $collection)
        <div class="mt-2">
            {{ $collection->name }}

            <div class="flex space-between">

                @foreach ($collection->fields as $field)
                    <div class="py-2 px-2 border m-2">
                        <p>{{ $field->name }}</p>
                        <p>{{ $field->type }}</p>
                    </div>
                @endforeach

            </div>

        </div>
        <hr />
    @endforeach
</x-app-layout>
