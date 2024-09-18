<x-app-layout>
    <x-header-content>
        {{ __('Loading...') }}
    </x-header-content>

    <h1>Виконується аналіз схеми. Система переспрямує вас автоматично.</h1>

    <script>
        $(document).ready(function() {
            console.log('ready');

            /** 
             * Testing Channels & Events & Connections
             */
            // window.Echo.private("delivery.{{ auth()->user()->id }}").listen("PackageSent", (event) => {
            window.Echo.private("users.{{ auth()->user()->id }}.converts.{{ $convert->id }}.ReadSchema").listen("ReadSchema", (event) => {
                console.log('event');
                console.log(event);
                window.location.href = "{{ route('convert.resume', [$convert->id]) }}";
            });
            
            window.Echo.private("delivery.{{ auth()->user()->id }}").listen("PackageSent", (event) => {
                console.log(event);
            });
        });
    </script>
</x-app-layout>