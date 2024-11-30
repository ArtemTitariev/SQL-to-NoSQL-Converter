<section class="space-y-6">
    <x-modal class="z-60" name="select-table-action" :show="!empty(session('missingTables'))" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <h2 class="text-2xl font-medium text-info">
                    {{ session('message', __('Action Required for Missing Tables')) }}
                </h2>
            </div>

            <p class="mt-6 text-lg text-danger">
                {{ __('Selected tables have relationships with the following tables that are not selected:') }}
            </p>
            <div id="missing-tables-container" class="mt-2 text-lg text-customgray font-bold">
                {{ implode(', ', session('missingTables', [])) }}
            </div>

            <div class="mt-6">
                <h3 class="text-2xl font-medium text-info">
                    {{ __('Please select an action:') }}
                </h3>

                <div class="flex justify-between mt-4 space-x-2">
                    <x-primary-button onclick="selectMissingTables()" x-on:click="$dispatch('close')"
                        class="flex items-center">
                        <span class="flex-grow">
                            {{ __('Automatically Select These Tables') }}
                        </span>
                        <x-tooltip iconColor="text-primary" position="top" class="normal-case">
                            <p class="font-semibold text-info">
                                {{ __('Missing tables will be selected automatically.') }}</p>
                            <p class="text-customgray font-normal mt-2">{{ __('Adjustments can be continued.') }}
                            </p>
                        </x-tooltip>
                    </x-primary-button>

                    <x-danger-button onclick="breakRelationsAndSumbit()" class="flex items-center">
                        <span class="flex-grow">
                            {{ __('Break Relations and Continue') }}
                        </span>
                        <x-tooltip iconColor="text-primary" position="top" class="normal-case">
                            <p class="font-semibold text-info">
                                {{ __('Links to tables that are not selected will be broken.') }}</p>
                            <p class="text-customgray font-normal  mt-2">
                                {{ __('The system will automatically save these settings.') }}
                            </p>
                        </x-tooltip>
                    </x-danger-button>

                    <x-secondary-button x-on:click="$dispatch('close')" class="flex items-center">
                        <span class="flex-grow">
                            {{ __('Leave for Manual Editing') }}
                        </span>
                        <x-tooltip iconColor="text-primary" position="top" class="normal-case">
                            <p class="font-semibold text-info">
                                {{ __('Required tables can be selected manually.') }}</p>
                        </x-tooltip>
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </x-modal>
</section>
