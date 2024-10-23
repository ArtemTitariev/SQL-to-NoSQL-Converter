<x-app-layout>
    <x-header-content>
        {{ __('Adjust Relationships') }}
    </x-header-content>

    <x-info-block class="mb-0">
        {{ __('messages.adjust_relationships_policy') }}
    </x-info-block>

    <script>
        const mongoManyToManyRelations = @json($mongoManyToManyRelations);
        const mongoRelationTypes = @json($mongoRelationTypes);
        const relationTypes = @json($relationTypes);

        const ONE_TO_ONE = relationTypes[0];
        const MANY_TO_ONE = relationTypes[2];

        const LINKING = mongoRelationTypes[0];
        const EMBEDDING = mongoRelationTypes[1];

        const LINKING_WITH_PIVOT = mongoManyToManyRelations[0];
        const HYBRID = mongoManyToManyRelations[2];

        const MAIN_IN_RELATED = @json(\App\Models\MongoSchema\LinkEmbedd::MAIN_IN_RELATED);
        const RELATED_IN_MAIN = @json(\App\Models\MongoSchema\LinkEmbedd::RELATED_IN_MAIN);

        const TESTING_MODE = 'testing';
        const DEFAULT_MODE = 'default';
    </script>

    @php
        $mongoDatabase = $convert->mongoDatabase;
        $collections = $mongoDatabase
            ->collections()
            ->whereHas('linksEmbeddsFrom')
            ->orWhereHas('manyToManyPivot')
            ->with([
                // 'fields',
                //  'linksEmbeddsFrom',
                //  'manyToManyPivot',
                'linksEmbeddsFrom.pkCollection',
                // 'linksEmbeddsTo.fkCollection',
                'manyToManyPivot.collection1',
                'manyToManyPivot.collection2',
            ])
            ->get();
    @endphp

    <section class="space-y-6" x-data="{ editModalOpen: false, isManyToMany: false }"
        x-on:open-modal.window="if ($event.detail.modalName === 'edit-relationship-modal') { 
            editModalOpen = true; 
            isManyToMany = $event.detail.isManyToMany; 
        }">
        <x-modal name="edit-relationship-modal" x-show="editModalOpen" focusable>
            <div class="p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h2 class="text-2xl font-medium text-info">
                        {{ __('Edit Relationship') }}
                    </h2>
                </div>

                <div id="loader"
                    class="hidden fixed top-0 left-0 right-0 bottom-0 bg-gray-900 bg-opacity-50 items-center justify-center">
                    <div
                        class="w-12 h-12 border-4 border-t-4 border-gray-200 border-t-blue-500 rounded-full animate-spin">
                    </div>
                </div>


                <div class="my-2 w-full">
                    <x-modal-errors-block id="error-block" />
                    <x-modal-warnings-block id="warning-block" />
                </div>

                <form id="relation-form" method="POST"
                    action="{{ route('convert.relationships.edit', ['convert' => $convert]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="relationData" id="relationData" value="">
                    <input type="hidden" name="mode" id="mode" value="default">

                    {{-- Used for JS only --}}
                    <input type="hidden" id="sqlRelation">

                    <div class="mt-6">
                        <h3 class="text-xl font-medium text-info">
                            {{ __('Relationship Information') }}
                        </h3>

                        <div id="linkEmbeddBlock" class="hidden space-y-4">
                            <div class="overflow-x-auto">
                                <x-table class="border-gray-300 break-all">
                                    <x-table-header>
                                        <x-table-header-cell>{{ __('Main Collection') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Related Collection') }}</x-table-header-cell>
                                    </x-table-header>
                                    <tbody class="bg-white divide-y">
                                        <x-table-row class="border-gray-300">
                                            <x-table-cell><span id="modalFkCollectionName">N/A</span></x-table-cell>
                                            <x-table-cell><span id="modalPkCollectionName">N/A</span></x-table-cell>
                                        </x-table-row>
                                    </tbody>
                                </x-table>
                            </div>
                            <div class="flex justify-around py-2">
                                <div>
                                    <p class="break-all max-w-80 py-2 mx-2"><strong>{{ __('Relation Type:') }}</strong>
                                    </p>
                                    <select id="modalRelationTypeLinkEmbedd" name="relationTypeLinkEmbedd"
                                        class="border rounded w-auto mx-2">
                                        @foreach (\App\Enums\MongoRelationType::cases() as $relation)
                                            <option value="{{ $relation->value }}">{{ __($relation->value) }}</option>
                                        @endforeach
                                        {{-- <option value="Linking">{{ __('Linking') }}</option>
                                <option value="Embedding">{{ __('Embedding') }}</option> --}}
                                    </select>
                                </div>

                                <div id="modalEmbeddingDirection" class="hidden break-normal">
                                    <p class="max-w-80 py-2 mx-2">
                                        <strong>{{ __('Direction of embedding:') }}</strong>
                                    </p>
                                    <div><input type="radio" id="mainInRelated" name="embeddingDirection"
                                            value="{{ \App\Models\MongoSchema\LinkEmbedd::MAIN_IN_RELATED }}"
                                            checked />
                                        <label
                                            for="mainInRelated">{{ __('Embed the main collection in a related one') }}</label>
                                    </div>

                                    <div class="mt-2">
                                        <input type="radio" id="relatedInMain" name="embeddingDirection"
                                            value="{{ \App\Models\MongoSchema\LinkEmbedd::RELATED_IN_MAIN }}" />
                                        <label
                                            for="relatedInMain">{{ __('Embed a related collection in the main one') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="manyToManyBlock" class="hidden space-y-4">
                            <div class="overflow-x-auto">
                                <x-table class="border-gray-300 break-all">
                                    <x-table-header>
                                        <x-table-header-cell>{{ __('First Collection') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Second Collection') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Pivot Collection') }}</x-table-header-cell>
                                    </x-table-header>
                                    <tbody class="bg-white divide-y">
                                        <x-table-row class="border-gray-300">
                                            <x-table-cell><span id="modalCollection1Name">N/A</span></x-table-cell>
                                            <x-table-cell><span id="modalCollection2Name">N/A</span></x-table-cell>
                                            <x-table-cell><span id="modalPivotCollectionName">N/A</span></x-table-cell>
                                        </x-table-row>
                                    </tbody>
                                </x-table>
                            </div>
                            <div class="flex justify-center py-2">
                                <p class="break-all max-w-80 py-2 mx-2"><strong>{{ __('Relation Type:') }}</strong></p>
                                <select id="modalRelationTypeManyToMany" name="relationTypeManyToMany"
                                    class="border rounded w-auto mx-2">
                                    @foreach (\App\Enums\MongoManyToManyRelation::cases() as $relation)
                                        <option value="{{ $relation->value }}">{{ __($relation->value) }}</option>
                                    @endforeach
                                    {{-- <option value="Linking with pivot">{{ __('Linking with pivot') }}</option>
                                <option value="Embedding">{{ __('Embedding') }}</option>
                                <option value="Hybrid">{{ __('Hybrid') }}</option> --}}
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <x-secondary-button @click="show = false">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-primary-button id="submit-btn">
                            {{ __('Save Changes') }}
                        </x-primary-button>
                    </div>
                </form>

                {{-- Повідомлення про успіх --}}
                <div id="success-notification"
                    class="fixed top-3 right-10 bg-green-50 text-success font-semibold border border-success rounded-lg p-4">
                    <span>{{ __('Changes saved successfully!') }}</span>
                </div>

                <script>
                    function hideAndClearErrors() {
                        $('#error-block').addClass('hidden');
                        $('#error-title').empty();
                        $('#error-list').empty();
                    }

                    function hideAndClearWarnings() {
                        $('#warning-block').addClass('hidden');
                        $('#warning-title').empty();
                        $('#warning-list').empty();
                    }

                    function clearModalMessages() {
                        hideAndClearErrors();
                        hideAndClearWarnings();

                        $('#success-notification').addClass('hidden');
                    }

                    function renderMessages(listSelector, titleSelector, titleText, messages, blockSelector) {
                        $(titleSelector).text(titleText);
                        $(listSelector).empty();

                        messages.forEach(message => {
                            // Створюємо основний елемент для повідомлення
                            let messageItem = $('<li>').text(message.message);
                            $(listSelector).append(messageItem);

                            // Додаємо рекомендацію, якщо вона є
                            if (message.recommendation) {
                                let recommendationItem = $('<div>').addClass('ml-6 text-sm text-customgray')
                                    .text(message.recommendation);
                                messageItem.append(recommendationItem);
                            }

                            // Додаємо обробку для типу many_to_many_link
                            if (message.type === 'many_to_many_link' && message.related_collections && message
                                .related_collections.length > 0) {
                                let relatedCollectionsText = message.related_collections.map(collectionPair => {
                                    return `(${collectionPair.first.name}, ${collectionPair.second.name}) {{ __('through') }} ${collectionPair.pivot.name}`;
                                }).join('; ');

                                let relatedCollections = $('<div>').addClass('ml-6 text-sm')
                                    .text('{{ __('Related collections') }}: ' + relatedCollectionsText);
                                messageItem.append(relatedCollections);
                            }
                            // Обробка для інших типів пов'язаних колекцій
                            else if (message.related_collections && message.related_collections.length > 0) {
                                let collectionsNames = message.related_collections.map(collection => collection.name).join(
                                    ', ');
                                let collectionsText = '{{ __('Related collections') }}: ' + collectionsNames;

                                let relatedCollections = $('<div>').addClass('ml-6 text-sm').text(collectionsText);
                                messageItem.append(relatedCollections);
                            }
                        });

                        $(blockSelector).removeClass('hidden');
                    }

                    function somethingWentWrong() {
                        $('#error-title').text("{{ __('Something went wrong. Please try again later.') }}");
                        $('#error-block').removeClass('hidden');
                    }

                    function handleResponse(responseContent) {
                        if (!responseContent.status) {
                            somethingWentWrong();
                        }
                        if (responseContent.status === "error" || responseContent.status === "warning") {
                            // Відображення помилок
                            if (responseContent.errors.length > 0) {
                                renderMessages('#error-list', '#error-title', '{{ __('Errors:') }}', responseContent.errors,
                                    '#error-block');
                            }

                            // Відображення попереджень, якщо вони є і немає помилок
                            if (responseContent.errors.length === 0 && responseContent.warnings.length > 0) {
                                renderMessages('#warning-list', '#warning-title', '{{ __('Warnings:') }}', responseContent.warnings,
                                    '#warning-block');
                            }
                        } else if (responseContent.status === 'success') {
                            // Відображення повідомлення про успіх
                            let notificationBlock = $('#success-notification');
                            let mode = $('#mode').val();

                            if (mode === DEFAULT_MODE) {
                                notificationBlock.text(responseContent.message || '{{ __('Changes saved successfully!') }}');
                            } else {
                                notificationBlock.text(responseContent.message || '{{ __('Validation was successful!') }}');
                            }

                            notificationBlock.removeClass('hidden');
                        } else if (responseContent.status === 'no_changes') {
                            // Логіка для випадку, якщо змін немає
                        } else {
                            somethingWentWrong();
                        }
                    }

                    function updateLinkEmbeddRelationInTable(updatedData) {
                        // __('From main')
                        // __('In main') 
                        // __('In related')
                        const row = document.querySelector(`[data-encrypted='${updatedData.id}']`).closest('tr');

                        if (row) {
                            row.querySelector('[data-current-relation-type]').lastElementChild.innerHTML = updatedData.relationType;
                            // row.querySelector('[data-current-embedding-direction]').innerText = updatedData.direction;
                        }
                    }

                    function updateManyToManyRelationInTable(updatedData) {
                        const row = document.querySelector(`[data-encrypted='${updatedData.id}']`).closest('tr');

                        if (row) {
                            row.querySelector('[data-current-relation-type]').lastElementChild.innerText = updatedData.relationType;
                        }
                    }

                    function submitForm() {
                        clearModalMessages();

                        // Дезейблимо кнопку та показуємо лоадер
                        $('#submit-btn').attr('disabled', true);
                        $('#loader').addClass('flex').removeClass('hidden');

                        // Збираємо дані з форми
                        let formData = $('#relation-form').serialize();

                        // AJAX запит
                        $.ajax({
                            url: $('#relation-form').attr('action'), // URL з атрибута action форми
                            method: 'PATCH', // Метод запиту
                            data: formData, // Дані форми
                            timeout: 5000
                        }).done(function(response) {
                            handleResponse(response); // Виклик функції обробки відповіді
                            let mode = $('#mode').val();
                            if (mode !== DEFAULT_MODE) {
                                return;
                            }

                            if (!$('#linkEmbeddBlock').hasClass('hidden')) {
                                const selectElement = document.getElementById('modalRelationTypeLinkEmbedd');
                                const selectedOption = selectElement.options[selectElement.selectedIndex];
                                // Отримуємо текст вибраного option
                                const relationType = selectedOption.innerText;

                                const embeddingDirection = document.querySelector(
                                        'input[name="embeddingDirection"]:checked')
                                    .value;

                                const updatedData = {
                                    id: document.getElementById('relationData').value,
                                    relationType: relationType,
                                    direction: embeddingDirection
                                };
                                // Оновлюємо рядок у таблиці
                                updateLinkEmbeddRelationInTable(updatedData);
                            }

                            if (!$('#manyToManyBlock').hasClass('hidden')) {
                                const selectElement = document.getElementById('modalRelationTypeManyToMany');
                                const selectedOption = selectElement.options[selectElement.selectedIndex];
                                // Отримуємо текст вибраного option
                                const relationType = selectedOption.innerText;

                                const updatedData = {
                                    id: document.getElementById('relationData').value,
                                    relationType: relationType,
                                };

                                updateManyToManyRelationInTable(updatedData)
                            }
                        }).fail(function(xhr, t, m) {
                            if (t === "timeout") {
                                $('#error-title').text("{{ __('Server is not responding. Please try again later.') }}");
                            } else {
                                // Обробка помилок
                                handleResponse(xhr.responseJSON);
                            }
                        }).always(function() {
                            // Ховаємо лоадер та активуємо кнопку
                            $('#submit-btn').removeAttr('disabled');
                            $('#loader').removeClass('flex').addClass('hidden');
                        });
                    }

                    $(document).ready(function() {
                        // Відправка форми при натисканні кнопки submit
                        $('#submit-btn').on('click', function(e) {
                            e.preventDefault(); // Запобігаємо стандартній поведінці
                            $('#mode').val(DEFAULT_MODE);
                            submitForm();
                        });

                        // Змінюємо значення mode при зміні select та відправляємо форму
                        $('select').on('change', function() {
                            $('#mode').val(TESTING_MODE);
                            submitForm();
                        });

                        // Змінюємо значення mode при натисканні на radio та відправляємо форму
                        $('input[type="radio"]').on('click', function() {
                            $('#mode').val(TESTING_MODE);
                            submitForm();
                        });
                    });
                </script>

                <div class="mt-6">
                    <h3 class="text-xl font-medium text-info">{{ __('Structure Preview') }}</h3>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Current JSON Structure --}}
                        <div>
                            <h4 class="text-lg font-medium">{{ __('Current Structure') }}</h4>
                            <div class="bg-gray-100 p-4 rounded shadow max-h-60 overflow-y-auto">
                                <pre id="currentJson" class="text-sm text-customgray"></pre>
                            </div>
                        </div>
                        {{-- Updated JSON Structure --}}
                        <div>
                            <h4 class="text-lg font-medium">{{ __('Updated Structure') }}</h4>
                            <div class="bg-gray-100 p-4 rounded shadow max-h-60 overflow-y-auto">
                                <pre id="updatedJson" class="text-sm text-customgray"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>
    </section>

    <div class="container mx-auto p-4">
        <x-input-errors-block />
    </div>


    <div class="sticky top-0 p-4 mb-4 flex justify-center space-x-2 bg-white z-10 shadow-md">
        <div class="container mx-auto p-4">
            <div class="flex flex-col space-y-3">
                <input type="text" id="search-input" placeholder="{{ __('Search for collections...') }}"
                    class="border-2 border-accent rounded px-4 py-2 w-full">

                <div class="flex justify-between">
                    <form action="{{ route('convert.step.store', [$convert, 'adjust_relationships']) }}"
                        method="POST">
                        @csrf
                        <x-primary-button>{{ __('Proceed to Next Step') }}</x-primary-button>
                    </form>

                    <button id="toggleButton" class="bg-info text-white rounded px-4 py-2 hover:bg-accent">
                        {{ __('Show / Hide Graph') }}
                    </button>
                </div>
            </div>

        </div>
    </div>

    <div id="container" class="grid grid-cols-1 gap-4 md:grid-cols-1 lg:grid-cols-[59%_38%]">
        <!-- Правий блок -->
        <div id="rightBlock"
            class="order-1 h-96 md:h-72 w-full lg:h-120 md:order-1 lg:order-2 p-4
                sticky top-22 justify-center bg-light">
            <div id="rightMessageBlock" class="flex justify-center my-6 px-2">
                <h3 class="font-bold text-2xl text-warning">
                    {{ __('Select a collection to see the graph.') }}
                </h3>
            </div>

            <div id="cy" style="width: 100%; height: 90%;">
            </div>
        </div>

        <div id="leftBlock" class="order-2 md:order-2 lg:order-1 p-4">
            @foreach ($collections as $collection)
                <div @class([
                    'collection-container', // For searching
                    'border-2',
                    'rounded-lg',
                    'p-4',
                    'mb-6',
                    'bg-light' => $loop->odd,
                    'bg-white' => $loop->even,
                    'shadow-sm',
                ]) data-collection-name="{{ $collection->name }}">

                    <div class="flex justify-between"
                        onclick="updateGraph({{ json_encode($collection->getFilteredDataForGraph()) }})">
                        <h2 @class([
                            'text-xl',
                            'font-semibold',
                            'mb-4',
                            'text-primary' => $loop->odd,
                            'text-secondary' => $loop->even,
                        ])>
                            {{ $collection->name }}
                        </h2>

                        <x-secondary-button id="button-collection-{{ $collection->name }}" onsubmit="return false;"
                            onclick="toggleCollection('collection-{{ $collection->name }}')">
                            {{ __('Expand') }}
                        </x-secondary-button>
                    </div>
                    <div id="collection-{{ $collection->name }}"
                        class="max-h-0 overflow-hidden transition-all duration-300 ease-in-out hidden mt-4 nested-form">
                        {{-- Links + Embeddings Section --}}
                        @if ($collection->linksEmbeddsFrom->isNotEmpty())
                            <h3 class="text-lg font-bold text-info mb-2">{{ __('Links and Embeddings') }}</h3>
                            <div class="overflow-x-auto">
                                <x-table class="border-gray-300">
                                    <x-table-header>
                                        <x-table-header-cell>{{ __('Edit') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Related Collection') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Relation Type') }}</x-table-header-cell>
                                        {{-- <x-table-header-cell>{{ __('Direction') }}</x-table-header-cell> --}}
                                        <x-table-header-cell>{{ __('SQL Relation') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Local Fields') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Foreign Fields') }}</x-table-header-cell>

                                    </x-table-header>
                                    <tbody class="bg-white divide-y">
                                        @foreach ($collection->linksEmbeddsFrom as $relationFrom)
                                            <x-table-row class="border-gray-300">
                                                <x-table-cell @class([
                                                    'edit-table-cell',
                                                    // 'disabled' =>
                                                    //     $relationFrom->sql_relation === \App\Enums\RelationType::COMPLEX ||
                                                    //     $relationFrom->sql_relation === \App\Enums\RelationType::SELF_REF,
                                                ])>
                                                    @if (
                                                        $relationFrom->sql_relation !== \App\Enums\RelationType::COMPLEX &&
                                                            $relationFrom->sql_relation !== \App\Enums\RelationType::SELF_REF)
                                                        <x-icon-link href="#" :icon="'icons.edit'"
                                                            data-encrypted="{{ $relationFrom->encryptIdentifier() }}"
                                                            data-fk-collection-name="{{ $collection->name }}"
                                                            data-pk-collection-name="{{ $relationFrom->pkCollection->name }}"
                                                            data-relation-type="{{ $relationFrom->relation_type }}"
                                                            data-sql-relation="{{ $relationFrom->sql_relation }}"
                                                            data-embed-in-main="{{ $relationFrom->embed_in_main ?? 'none' }}"
                                                            onclick="showModal(this)">
                                                        </x-icon-link>
                                                    @else
                                                        <x-tooltip iconColor="text-info" position="right">
                                                            <p class="font-semibold w-100">
                                                                {{ __('It is not allowed to edit complex realtionships and self-referencing links.') }}
                                                            </p>
                                                            </p>
                                                        </x-tooltip>
                                                    @endif
                                                </x-table-cell>
                                                <x-table-cell>{{ $relationFrom->pkCollection->name }}</x-table-cell>
                                                <x-table-cell data-current-relation-type="">
                                                    <x-mongo-relation-type-badge :relation-type="$relationFrom->relation_type" />
                                                </x-table-cell>
                                                {{-- <x-table-cell data-current-embedding-direction="">
                                                    {{ is_null($relationFrom->embed_in_main) ? __('From main') : ($relationFrom->embedInMain ? __('In main') : __('In related')) }}
                                                </x-table-cell> --}}
                                                <x-table-cell>
                                                    <x-relation-type-badge :relation-type="$relationFrom->sql_relation" />
                                                </x-table-cell>

                                                <x-table-cell>
                                                    <ul>
                                                        @foreach ($relationFrom->local_fields as $localField)
                                                            <li>{{ $localField }}</li>
                                                        @endforeach
                                                    </ul>
                                                </x-table-cell>

                                                <x-table-cell>
                                                    <ul>
                                                        @foreach ($relationFrom->foreign_fields as $foreignField)
                                                            <li>{{ $foreignField }}</li>
                                                        @endforeach
                                                    </ul>
                                                </x-table-cell>
                                            </x-table-row>
                                        @endforeach
                                    </tbody>
                                </x-table>
                            </div>
                        @endif

                        {{-- Many to Many Section --}}
                        @if ($collection->manyToManyPivot->isNotEmpty())
                            <h3 class="text-lg font-bold text-accent mt-6 mb-2">{{ __('Many To Many Relationships') }}
                            </h3>
                            <div class="overflow-x-auto">
                                <x-table class="border-gray-300">
                                    <x-table-header>
                                        <x-table-header-cell>{{ __('Edit') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Relation Type') }}</x-table-header-cell>

                                        <x-table-header-cell>{{ __('Local Fields 1') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Local Fields 2') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('First Collection') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Foreign Fields 1') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Second Collection') }}</x-table-header-cell>
                                        <x-table-header-cell>{{ __('Foreign Fields 2') }}</x-table-header-cell>
                                    </x-table-header>
                                    <tbody>
                                        @foreach ($collection->manyToManyPivot as $nnRelation)
                                            <x-table-row class="border-gray-300">
                                                <x-table-cell>
                                                    <x-icon-link href="#" :icon="'icons.edit'"
                                                        data-encrypted="{{ $nnRelation->encryptIdentifier() }}"
                                                        data-pivot-collection-name="{{ $collection->name }}"
                                                        data-collection1-name="{{ $nnRelation->collection1->name }}"
                                                        data-collection2-name="{{ $nnRelation->collection2->name }}"
                                                        data-relation-type="{{ $nnRelation->relation_type }}"
                                                        onclick="showModal(this, true)">
                                                    </x-icon-link>

                                                </x-table-cell>
                                                <x-table-cell data-current-relation-type="">
                                                    <x-many-to-many-relation-badge :relation-type="$nnRelation->relation_type" />
                                                </x-table-cell>

                                                <x-table-cell>
                                                    <ul>
                                                        @foreach ($nnRelation->local1_fields as $localField1)
                                                            <li>{{ $localField1 }}</li>
                                                        @endforeach
                                                    </ul>
                                                </x-table-cell>
                                                <x-table-cell>
                                                    <ul>
                                                        @foreach ($nnRelation->local2_fields as $localField2)
                                                            <li>{{ $localField2 }}</li>
                                                        @endforeach
                                                    </ul>
                                                </x-table-cell>
                                                <x-table-cell>{{ $nnRelation->collection1->name }}</x-table-cell>
                                                <x-table-cell>
                                                    <ul>
                                                        @foreach ($nnRelation->foreign1_fields as $foreignField1)
                                                            <li>{{ $foreignField1 }}</li>
                                                        @endforeach
                                                    </ul>
                                                </x-table-cell>
                                                <x-table-cell>{{ $nnRelation->collection2->name }}</x-table-cell>
                                                <x-table-cell>
                                                    <ul>
                                                        @foreach ($nnRelation->foreign2_fields as $foreignField2)
                                                            <li>{{ $foreignField2 }}</li>
                                                        @endforeach
                                                    </ul>
                                                </x-table-cell>
                                            </x-table-row>
                                        @endforeach
                                    </tbody>
                                </x-table>
                            </div>
                        @endif
                        <div class="flex mt-4">
                            <x-secondary-button class="mr-0 ml-auto"
                                onclick="toggleCollection('collection-{{ $collection->name }}')">
                                {{ __('Collapse') }}
                            </x-secondary-button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script src="https://unpkg.com/cytoscape/dist/cytoscape.min.js"></script>
    <script>
        function toggleCollection(collectionId) {
            const collection = document.getElementById(collectionId);
            let button = document.getElementById('button-' + collectionId);

            if (collection.classList.contains('hidden')) {
                collection.classList.remove('hidden');
                collection.classList.add('max-h-0');
                button.innerText = '{{ __('Collapse') }}';

                // Затримка для активації анімації
                setTimeout(() => {
                    collection.classList.remove('max-h-0');
                    collection.classList.add('max-h-1000'); // Максимальна висоту для анімації
                }, 10);
            } else {
                collection.classList.remove('max-h-1000');
                button.innerText = '{{ __('Expand') }}';

                // Затримка для завершення анімації
                setTimeout(() => {
                    collection.classList.add('max-h-0');
                    setTimeout(() => {
                        collection.classList.add('hidden'); // Приховати після завершення анімації
                    }, 300);
                }, 10); // Додати коротку затримку
            }
        }
    </script>

    <script>
        // show / hide graph

        function isGraphContainerHidden() {
            return document.getElementById('rightBlock').classList.contains('hidden');
        }

        function showGraphContainer() {
            const rightBlock = document.getElementById('rightBlock');
            const container = document.getElementById('container');

            rightBlock.classList.remove('hidden');
            container.classList.remove('grid-cols-1');
            container.classList.add('lg:grid-cols-[59%_38%]');
        }

        function hideGraphContainer() {
            const rightBlock = document.getElementById('rightBlock');
            const container = document.getElementById('container');

            rightBlock.classList.add('hidden');
            container.classList.remove('lg:grid-cols-[59%_38%]');
            container.classList.add('grid-cols-1');
        }

        document.addEventListener('DOMContentLoaded', () => {
            // const leftBlock = document.getElementById('leftBlock');
            // const rightBlock = document.getElementById('rightBlock');
            // const container = document.getElementById('container');

            const mediaQuery = window.matchMedia('(max-width: 1023px)');

            function handleMediaQueryChange(event) {
                if (event.matches) {
                    // Ширина екрану менше 768px
                    hideGraphContainer();
                }
            }

            // Додати слухач змін медіа-запиту
            mediaQuery.addEventListener('change', handleMediaQueryChange);

            // Перевіримо стан при завантаженні сторінки
            handleMediaQueryChange(mediaQuery);

            const toggleButton = document.getElementById('toggleButton');

            toggleButton.addEventListener('click', () => {
                if (isGraphContainerHidden()) {
                    showGraphContainer();
                } else {
                    hideGraphContainer();
                }
            });
        });
    </script>

    <script>
        // Search
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-input');
            const collectionContainers = document.querySelectorAll('.collection-container');

            const filterCollections = (query) => {
                collectionContainers.forEach(container => {
                    const collectionName = container.getAttribute('data-collection-name').toLowerCase();
                    if (collectionName.includes(query)) {
                        container.classList.remove('hidden');
                    } else {
                        container.classList.add('hidden');
                    }
                });
            };

            searchInput.addEventListener('input', () => {
                let query = searchInput.value.toLowerCase();
                filterCollections(query);
            });
        });
    </script>

    <script>
        // Modal -----------------------------------------------------------------------------------------------------------------------
        function showModal(element, isManyToMany = false) {
            event.preventDefault();
            clearModalMessages();

            let relation = {};
            document.getElementById('modalRelationTypeLinkEmbedd').removeEventListener('change', handleLinkEmbeddChange);
            document.getElementById('modalRelationTypeManyToMany').removeEventListener('change', handleManyToManyChange);

            const embeddingRadios = document.querySelectorAll('input[name="embeddingDirection"]');
            embeddingRadios.forEach(radio => {
                radio.removeEventListener('change', handleLinkEmbeddChange);
            });

            document.getElementById('modalEmbeddingDirection').classList.add('hidden');

            if (isManyToMany) {
                relation = {
                    encryptedData: element.getAttribute('data-encrypted'),
                    pivotCollectionName: element.getAttribute('data-pivot-collection-name'),
                    collection1Name: element.getAttribute('data-collection1-name'),
                    collection2Name: element.getAttribute('data-collection2-name'),
                    relationType: element.getAttribute('data-relation-type'),
                    sqlRelation: null,
                };

                document.getElementById('linkEmbeddBlock').classList.replace('block', 'hidden');
                document.getElementById('manyToManyBlock').classList.replace('hidden', 'block');

                // Відображення даних для ManyToMany
                document.getElementById('modalCollection1Name').textContent = relation.collection1Name;
                document.getElementById('modalCollection2Name').textContent = relation.collection2Name;
                document.getElementById('modalPivotCollectionName').textContent = relation.pivotCollectionName;

                let select = document.getElementById('modalRelationTypeManyToMany');
                select.value = relation.relationType;
                processDefaultSelectDataAttribute(select);

                select.addEventListener('change', handleManyToManyChange);
                handleManyToManyChange();
            } else {
                relation = {
                    encryptedData: element.getAttribute('data-encrypted'),
                    fkCollectionName: element.getAttribute('data-fk-collection-name'),
                    pkCollectionName: element.getAttribute('data-pk-collection-name'),
                    relationType: element.getAttribute('data-relation-type'),
                    sqlRelation: element.getAttribute('data-sql-relation'),
                    embedInMain: element.getAttribute('data-embed-in-main')
                };
                relation.embedDirection = relation.embedInMain === '1' ? RELATED_IN_MAIN : relation.embedInMain === '0' ?
                    MAIN_IN_RELATED : null;

                document.getElementById('linkEmbeddBlock').classList.replace('hidden', 'block');
                document.getElementById('manyToManyBlock').classList.replace('block', 'hidden');

                // Відображення даних для LinkEmbedd
                document.getElementById('modalPkCollectionName').textContent = relation.pkCollectionName;
                document.getElementById('modalFkCollectionName').textContent = relation.fkCollectionName;

                // if (relation.embedDirection !== null) {
                //     // console.log(relation.embedDirection);
                //     document.getElementById('modalEmbeddingDirection').classList.remove('hidden');
                // }

                embeddingRadios.forEach(radio => {
                    radio.addEventListener('change', handleLinkEmbeddChange);
                });

                let select = document.getElementById('modalRelationTypeLinkEmbedd');
                select.value = relation.relationType;

                processDefaultSelectDataAttribute(select);
                processDefaultRadiosDataAttribute(embeddingRadios, relation.embedDirection);

                select.addEventListener('change', handleLinkEmbeddChange);
                handleLinkEmbeddChange();
            }

            document.getElementById('relationData').value = relation.encryptedData;
            document.getElementById('sqlRelation').value = relation.sqlRelation;

            updateCurrentPreview(relation, isManyToMany);

            window.dispatchEvent(new CustomEvent('open-modal', {
                detail: 'edit-relationship-modal',
                isManyToMany: isManyToMany
            }));
        }

        function processDefaultSelectDataAttribute(select, radiobtn = null, isLinkEmbed = false) {
            Array.from(select.options).forEach(option => {
                if (option.value === select.value) {
                    option.dataset.default = "true";
                } else {
                    delete option.dataset.default;
                }
            });
        }

        function processDefaultRadiosDataAttribute(radios, embedDirection) {
            radios.forEach(radio => {
                delete radio.dataset.default;

                if (embedDirection === radio.value) {
                    radio.checked = true;
                    radio.dataset.default = "true";
                }
                // if (embedDirection === RELATED_IN_MAIN && radio.value === RELATED_IN_MAIN) {
                //     radio.checked = true;
                //     radio.dataset.default = "true";
                // } else if (embedDirection === MAIN_IN_RELATED && radio.value === MAIN_IN_RELATED) {
                //     radio.checked = true;
                //     radio.dataset.default = "true";
                // }
            });

        }

        function getSelectedRadioValue() {
            const selectedRadio = document.querySelector('input[name="embeddingDirection"]:checked');
            return selectedRadio ? selectedRadio.value : null;
        }

        function handleLinkEmbeddChange() {
            clearModalMessages();
            const select = document.getElementById('modalRelationTypeLinkEmbedd');
            const radios = document.querySelectorAll('input[name="embeddingDirection"]');

            updatePreviewLinkEmbedd(
                document.getElementById('modalPkCollectionName').textContent,
                document.getElementById('modalFkCollectionName').textContent,
                select.value,
                document.getElementById('sqlRelation').value,
                getSelectedRadioValue()
            );

            if (select.value === EMBEDDING) {
                document.getElementById('modalEmbeddingDirection').classList.remove('hidden');
            } else if (select.value === LINKING) {
                document.getElementById('modalEmbeddingDirection').classList.add('hidden');
            }

            // toggleSubmitButton(select, radios);
        }

        function handleManyToManyChange() {
            const select = document.getElementById('modalRelationTypeManyToMany');
            clearModalMessages();
            updatePreviewManyToMany(
                document.getElementById('modalCollection1Name').textContent,
                document.getElementById('modalCollection2Name').textContent,
                document.getElementById('modalPivotCollectionName').textContent,
                select.value
            );

            // toggleSubmitButton(select, null);
        }

        function toggleSubmitButton(select, radios = null) {
            const defaultSelectOption = select.querySelector('[data-default="true"]');
            const defaultRadio = radios === null ? radios : document.querySelector(
                'input[name="embeddingDirection"][data-default="true"]');

            let submitButton = document.getElementById('submit-btn');
            if (select.value !== defaultSelectOption.value || (
                    defaultRadio !== null && defaultRadio.value !== getSelectedRadioValue()
                )) {
                submitButton.disabled = false;
                submitButton.classList.remove('disabled');
            } else {
                submitButton.disabled = true;
                submitButton.classList.add('disabled');
            }
        }

        // Оновлення поточного прев'ю
        function updateCurrentPreview(relation, isManyToMany) {
            if (isManyToMany) {
                // Поточний зв'язок для ManyToMany
                let preview = getPreviewManyToMany(relation.collection1Name, relation.collection2Name,
                    relation.pivotCollectionName, relation.relationType);

                document.getElementById('currentJson').innerHTML = formatJsonWithSyntaxHighlighting(preview);
                document.getElementById('updatedJson').innerHTML = formatJsonWithSyntaxHighlighting(preview);

            } else {
                let preview = getPreviewLinkEmbedd(relation.pkCollectionName, relation.fkCollectionName,
                    relation.relationType, relation.sqlRelation, getSelectedRadioValue());

                // Оновлення поточного та оновленого прев'ю
                document.getElementById('currentJson').innerHTML = formatJsonWithSyntaxHighlighting(preview);
                document.getElementById('updatedJson').innerHTML = formatJsonWithSyntaxHighlighting(preview);
            }
        }

        // -----------------------------------
        function updatePreviewLinkEmbedd(pkCollectionName, fkCollectionName, relationType, sqlRelation,
            embeddingDirection) {

            const preview = getPreviewLinkEmbedd(pkCollectionName, fkCollectionName, relationType, sqlRelation,
                embeddingDirection);
            document.getElementById('updatedJson').innerHTML = formatJsonWithSyntaxHighlighting(preview);
        }

        // -------------------------------
        function updatePreviewManyToMany(collection1Name, collection2Name, pivotCollectionName, relationType) {
            const preview = getPreviewManyToMany(collection1Name, collection2Name, pivotCollectionName, relationType);
            document.getElementById('updatedJson').innerHTML = formatJsonWithSyntaxHighlighting(preview);
        }

        function getPreviewLinkEmbedd(pkCollectionName, fkCollectionName, relationType, sqlRelation, embeddingDirection) {
            let preview = '';

            if (relationType === LINKING) {
                preview =
                    `<-${fkCollectionName}->:
{
    "_id": "Object_id",
    "${pkCollectionName}_id": "Object_id",
    // {{ __('other fields') }}
},

<-${pkCollectionName}->:
{
    "_id": "Object_id",
    // {{ __('other fields') }}
}`;
            } else if (relationType === EMBEDDING) {

                if (embeddingDirection === MAIN_IN_RELATED) {
                    // [fkCollectionName, pkCollectionName] = [pkCollectionName, fkCollectionName];
                    let temp = fkCollectionName;
                    fkCollectionName = pkCollectionName;
                    pkCollectionName = temp;
                }
                // if embeddingDirection === 'relatedInMain' => ОК

                if (sqlRelation === ONE_TO_ONE) {
                    preview =
                        `<-${fkCollectionName}->:
{
    "_id": "Object_id",
    "${pkCollectionName}": {
        "_id": "Object_id",
        // {{ __('other embedded fields') }}
    },
    // {{ __('other fields') }}
}`;
                } else if (sqlRelation === MANY_TO_ONE) {
                    preview =
                        `<-${fkCollectionName}->: 
{
    "_id": "Object_id",
    "${pkCollectionName}": [
        {
            "_id": "Object_id",
            // {{ __('other embedded fields') }}
        },
        // {{ __('other embedded documents') }}
    ],
    // {{ __('other fields') }}
}`;
                }
            }
            return preview;
        }

        function getPreviewManyToMany(collection1Name, collection2Name,
            pivotCollectionName, relationType) {

            let preview = '';
            if (relationType === LINKING_WITH_PIVOT) {
                preview =
                    `<-${pivotCollectionName}->:
{
    "_id": "Object_id",
    "${collection1Name}_id": "Object_id",
    "${collection2Name}_id": "Object_id",
    // {{ __('other fields') }}
}

<-${collection1Name}->:
{
    "_id": "Object_id",
    // {{ __('other fields') }}
}

<-${collection2Name}->:
{
    "_id": "Object_id",
    // {{ __('other fields') }}
}`;
            } else if (relationType === EMBEDDING) {
                preview =
                    `<-${collection1Name}->:
{
    "_id": "Object_id",
    "${collection2Name}": [
        {
            "_id": "Object_id",
            // {{ __('other embedded fields') }}
        },
        // {{ __('other embedded documents') }}
    ],
    // {{ __('other fields') }}
}

<-${collection2Name}->:
{
    "_id": "Object_id",
    "${collection1Name}": [
        {
            "_id": "Object_id",
            // {{ __('other embedded fields') }}
        },
        // {{ __('other embedded documents') }}
    ],
    // {{ __('other fields') }}
}`;
            } else if (relationType === HYBRID) {
                preview =
                    `<-${collection1Name}->:
{
    "_id": "Object_id",
    "${collection2Name}_ids": [
        "Object1_id",
        "Object2_id",
        // {{ __('other referenced ids') }}
    ],
    // {{ __('other fields') }}
}

<-${collection2Name}->:
{
    "_id": "Object_id",
    "${collection1Name}_ids": [
        "Object1_id",
        "Object2_id",
        // {{ __('other referenced ids') }}
    ],
    // {{ __('other fields') }}
}`;
            }

            return preview;
        }


        function formatJsonWithSyntaxHighlighting(jsonString) {
            // // 1. Обробка спеціальних шаблонних рядків, таких як <-${collection1Name}->
            // jsonString = jsonString.replace(/"<-\${([^}]+)}->"/g,
            // '<span class="json-collection">"<-${1}->"</span>'); // Спеціальні шаблонні рядки

            // 2. Обробка звичайних рядків (це не коментарі та не шаблонні рядки)
            jsonString = jsonString.replace(/"([^"]+)"/g, '<span class="json-string">"$1"</span>'); // Рядки

            // 3. Обробка ключів (це все, що перед двокрапкою)
            jsonString = jsonString.replace(/"([^"]+)":/g, '<span class="json-key">"$1"</span>:'); // Ключі

            // 4. Обробка значень "Object_id"
            jsonString = jsonString.replace(/Object_id/g, '<span class="json-id">Object_id</span>'); // Object_id

            // 5. Обробка дужок { } [ ]
            jsonString = jsonString.replace(/{/g, '<span class="json-braces">{</span>'); // {
            jsonString = jsonString.replace(/}/g, '<span class="json-braces">}</span>'); // }
            jsonString = jsonString.replace(/\[/g, '<span class="json-braces">[</span>'); // [
            jsonString = jsonString.replace(/\]/g, '<span class="json-braces">]</span>'); // ]

            // 6. Обробка двокрапок ( : )
            jsonString = jsonString.replace(/:/g, '<span class="json-colon">:</span>'); // :

            // 7. Обробка коментарів (коментарі, які починаються з //)
            jsonString = jsonString.replace(/(\/\/[^\n]*)/g, '<span class="json-comment">$1</span>'); // Коментарі

            return jsonString;
        }
    </script>

    <style>
        .json-collection {
            color: #370fe9;
            font-weight: bold;
        }

        .json-key {
            color: #1e90ff;
            font-weight: bold;
        }

        .json-string {
            color: #a52a2a;
        }

        .json-id {
            color: #06b72c;
        }

        .json-comment {
            color: #047102;
            font-style: italic;
        }

        .json-braces {
            color: #f09c01;
        }

        .json-colon {
            color: #122ade;
        }
    </style>

    <script>
        // // Функція для генерації випадкового кольору
        // function getRandomColor() {
        //     const letters = '0123456789ABCDEF';
        //     let color = '#';
        //     for (let i = 0; i < 6; i++) {
        //         color += letters[Math.floor(Math.random() * 16)];
        //     }
        //     return color;
        // }

        // // Функція для визначення яскравості кольору
        // function getBrightness(hex) {
        //     const r = parseInt(hex.substr(1, 2), 16);
        //     const g = parseInt(hex.substr(3, 2), 16);
        //     const b = parseInt(hex.substr(5, 2), 16);
        //     return (r * 0.299 + g * 0.587 + b * 0.114);
        // }

        // const layout = {
        //     name: 'cose',
        //     padding: 80,
        //     fit: true,
        // }

        function updateGraph(data) {
            // Очистити попередні дані
            const nodes = [];
            const edges = [];

            const collectionName = data.collectionName;
            const linksEmbeddsFrom = data.linksEmbeddsFrom || [];
            const manyToManyPivot = data.manyToManyPivot || [];

            nodes.push({
                data: {
                    id: collectionName
                },
                style: {
                    'background-color': '#3271a8'
                }
            });

            if (linksEmbeddsFrom.length > 0) {
                // Додати вузли для linksEmbeddsFrom
                linksEmbeddsFrom.forEach(relation => {
                    nodes.push({
                        data: {
                            id: relation.pkCollectionName,
                        },
                        style: {
                            'background-color': '#b03813'
                        }
                    });
                    edges.push({
                        data: {
                            source: collectionName,
                            target: relation.pkCollectionName,
                            label: relation.relationType
                        }
                    });
                });
            }

            if (manyToManyPivot.length > 0) {
                // Додати вузли для manyToManyPivot
                manyToManyPivot.forEach(nnRelation => {
                    nodes.push({
                        data: {
                            id: nnRelation.collection1Name
                        },
                        style: {
                            'background-color': '#2ebf55'
                        }
                    });
                    nodes.push({
                        data: {
                            id: nnRelation.collection2Name,
                        },
                        style: {
                            'background-color': '#bd8128'
                        }
                    });

                    edges.push({
                        data: {
                            source: collectionName,
                            target: nnRelation.collection1Name,
                            label: "{{ __('Many-to-Many') }}"
                        }
                    });
                    edges.push({
                        data: {
                            source: collectionName,
                            target: nnRelation.collection2Name,
                            label: "{{ __('Many-to-Many') }}"
                        }
                    });
                });
            }

            document.getElementById('rightMessageBlock').classList.replace('flex', 'hidden');
            // Оновити граф
            const cy = cytoscape({
                container: document.getElementById('cy'),
                elements: {
                    nodes: nodes,
                    edges: edges
                },
                style: [{
                        selector: 'node',
                        style: {
                            'shape': 'rectangle',
                            'width': '100px',
                            'height': '50px',
                            'label': 'data(id)',
                            'text-valign': 'center',
                            'text-halign': 'center',
                            'font-size': '12px',
                            // 'background-color': 'data(background-color)',
                        }
                    },
                    {
                        selector: 'edge',
                        style: {
                            'width': 2,
                            'line-color': '#999',
                            'target-arrow-color': '#999',
                            'target-arrow-shape': 'triangle',
                            'curve-style': 'taxi',
                            'taxi-direction': 'rightward',
                            'label': 'data(label)',
                            'font-size': '12px',
                            'text-rotation': 'autorotate',
                            'text-margin-y': -10
                        }
                    }
                ],
                layout: {
                    // name: 'cose',
                    // name: 'grid',
                    // name: 'preset',
                    name: 'circle',
                    // name: 'concentric',
                    padding: 30,
                    fit: true,
                    // animate: true,
                    // animationDuration: 500,
                }
            });
        }
    </script>
</x-app-layout>
