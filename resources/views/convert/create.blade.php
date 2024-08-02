<x-app-layout>
    <x-header-content>
        {{ __('Create convert') }}
    </x-header-content>

    <div class="py-12">
        <div class="w-11/12 lg:w-8/12 mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-6 bg-light shadow sm:rounded-lg">
                <div class="max-w-full">
                    @include('convert.partials.create-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
