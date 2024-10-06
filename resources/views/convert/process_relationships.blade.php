<x-app-layout>
    <x-header-content>
        {{ __('Loading...') }}
    </x-header-content>

    <h1>Виконується первинна обробка зв'язків. Система переспрямує вас автоматично.</h1>

    <form action="{{ route('convert.step.store', [$convert, 'process_relationships']) }}" method="POST" id="form">
        @csrf
        <x-primary-button id="submit">{{ __('Save') }}</x-primary-button>
    </form>
    <script>
        $(document).ready(function() {
            console.log('ready');

            /** 
             * Testing Channels & Events & Connections
             */
            window.Echo.private("users.{{ auth()->user()->id }}.converts.{{ $convert->id }}.ProcessRelationships").listen("ProcessRelationships", (event) => {
                console.log('event');
                console.log(event);
                window.location.href = "{{ route('convert.resume', [$convert->id]) }}";
            });
        });
    </script>
</x-app-layout>