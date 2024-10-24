<x-progress-layout
    title="{{ __('The initial processing of the relationships is underway. This may take a few minutes.') }}"
    message="{{ __('When the process is complete, the system will redirect you automatically.') }}"
    additionalMessage="{{ __('You can leave this page and return to the configuration later.') }} {{ __('You can track the progress on') }}"
    linkText="{{ __('the conversion page') }}" linkHref="{{ route('converts.show', [$convert->id]) }}">
    <script>
        $(document).ready(function() {
            console.log('ready');
            window.Echo.private("users.{{ auth()->user()->id }}.converts.{{ $convert->id }}.ProcessRelationships")
                .listen("ProcessRelationships", (event) => {
                    console.log(event);
                    window.location.href = "{{ route('convert.resume', [$convert->id]) }}";
                });
        });
    </script>
</x-progress-layout>
