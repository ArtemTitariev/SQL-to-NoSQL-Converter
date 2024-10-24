<x-progress-layout
    title="{{ __('Converting a relational database to a non-relational database is in progress. This may take some time.') }}"
    message="{{ __('Upon completion of the process, you will receive an email to the email address you provided during registration.') }}"
    additionalMessage="{{ __('You can track the progress on') }}" linkText="{{ __('the conversion page') }}"
    linkHref="{{ route('converts.show', [$convert->id]) }}">
    <script>
        $(document).ready(function() {
            console.log('ready');

        });
    </script>
</x-progress-layout>
