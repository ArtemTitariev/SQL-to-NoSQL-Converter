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
                            @php
                                $relationLabels = [
                                    'Linking with pivot' => __('Linking with pivot'),
                                    'Embedding' => __('Embedding'),
                                    'Hybrid' => __('Array of references'),
                                ];
                            @endphp
                            <div class="flex justify-center py-2">
                                <p class="break-all max-w-80 py-2 mx-2"><strong>{{ __('Relation Type:') }}</strong></p>
                                <select id="modalRelationTypeManyToMany" name="relationTypeManyToMany"
                                    class="border rounded w-auto mx-2">
                                    @foreach (\App\Enums\MongoManyToManyRelation::cases() as $relation)
                                        <option value="{{ $relation->value }}">
                                            {{ $relationLabels[$relation->value] ?? __($relation->value) }}
                                        </option>
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
                        const row = document.querySelector(`[data-encrypted='${updatedData.id}']`).closest('tr');

                        if (row) {
                            row.querySelector('[data-current-relation-type]').lastElementChild.innerHTML = updatedData.relationType;
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
