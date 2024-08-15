<x-app-layout>
    <x-header-content>
        {{ __('Create Convert') }}

        <x-slot name="modalButton">
            <x-help-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'infoModal')">
                {{ __('Instruction') }}
            </x-help-button>
        </x-slot>
    </x-header-content>

    <x-info-block class="mb-0">
        {{ __('texts.store_connection_params_policy') }}
    </x-info-block>

    <x-big-form-container>
        <div class="w-11/12 lg:w-8/12 mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-6 bg-light shadow sm:rounded-lg">
                <div class="max-w-full">
                    @include('convert.partials.create-form')
                </div>
            </div>
        </div>
    </x-big-form-container>

    <!-- Модальне вікно -->
    <x-modal name="infoModal" :show="false" maxWidth="2xl">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-lg font-semibold text-primary">{{ __('Instruction') }}</h1>
            </div>

            <div class="space-y-4">
                <h2 class="text-md font-semibold flex items-center text-accent">
                    <x-icons.info />
                    {{ __('modals.general_requirements') }}
                </h2>
                <p>{{ __('modals.provide_params') }}</p>
                <p>{{ __('modals.test_connections') }}</p>
                <p>{{ __('modals.ensure_no_changes') }}</p>

                <h2 class="text-md font-semibold flex items-center text-accent">
                    <x-icons.info />
                    {{ __('modals.sql_requirements') }}
                </h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>{{ __('modals.access_rights') }} </strong> {{ __('modals.sql_acces_rights') }}</li>

                    <li><strong>{{ __('modals.foreign_keys') }} </strong> {!! __('modals.sql_foreign_keys') !!}</li>
                    <li><strong>{{ __('modals.data_consistency') }}</strong> {!! __('modals.nosql_data_consistency') !!}</li>
                </ul>

                <h2 class="text-md font-semibold flex items-center text-accent">
                    <x-icons.info />
                    {{ __('modals.nosql_requirements') }}
                </h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>{{ __('modals.access_rights') }}</strong>
                        {!! __('modals.nosql_acces_rights') !!}</li>
                    <li><strong>{{ __('modals.lack_of_important_data:') }} </strong>
                        {{ __('modals.nosql_lack_of_important_data:') }}</li>

                    <li><strong>{{ __('modals.avoiding_conflicts:') }}</strong>
                        {{ __('modals.nosql_avoiding_conflicts:') }}</li>
                </ul>

            </div>
        </div>
    </x-modal>
</x-app-layout>
