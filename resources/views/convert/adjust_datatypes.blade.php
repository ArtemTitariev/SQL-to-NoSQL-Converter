<x-app-layout>
    <x-header-content>
        {{ __('Adjust Data Types') }}
    </x-header-content>

    <x-info-block class="mb-0">
        {{ __('messages.select_tables_policy') }}
    </x-info-block>

    @php
        function findEl($element, $array): bool
        {
            foreach ($array as $subArray) {
                if (in_array($element, $subArray['columns'])) {
                    return true;
                }
            }

            return false;
        }
    @endphp

    @php
        $sqlDatabase = $convert
            ->sqlDatabase()
            ->with(['circularRefs'])
            ->first();

        $tables = $sqlDatabase
            ->tables()
            ->with(['columns', 'foreignKeys'])
            ->get();

        // $tb = $tables->last();

        // dd($tb->foreignKeys->toArray());

    @endphp

    <section class="space-y-6">
        <x-modal name="select-action" :show="!empty(session('missingTables'))" focusable>
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


    <div class="container mx-auto p-6">
        <x-input-errors-block />

        <div class="mt-2 mb-6 flex items-center space-x-2">
            <input type="text" id="search-input" placeholder="{{ __('Search for tables...') }}"
                class="border-2 border-accent rounded px-4 py-2 flex-grow">

            <button id="select-all" onclick="selectAll()"
                class="bg-primary text-white rounded px-4 py-2 hover:bg-accent">
                {{ __('Select all') }}
            </button>

            <button id="deselect-all" onclick="deselectAll()"
                class="bg-secondary text-white rounded px-4 py-2 hover:bg-accent">
                {{ __('Deselect all') }}
            </button>
        </div>

        {{-- Загальна форма для вибору таблиць і стовпців --}}
        <form action="{{ route('convert.step.store', [$convert, 'adjust_datatypes']) }}" method="POST" id="form">
            @csrf
            <input type="hidden" name="break_relations" id="break-relations" value="no-break">

            <h1 class="text-3xl font-bold mb-6">{{ __('Select the necessary tables and data types for the columns') }}
            </h1>

            {{-- Таблиці --}}
            @foreach ($tables as $table)
                <div class="border-2
                @if (in_array($table->name, session('missingTables', []))) border-danger @endif
                 hover:border-info p-4 rounded mb-6 table-container
                @if ($loop->odd) bg-light @endif
                 "
                    data-table-name="{{ $table->name }}">
                    <div class="flex justify-between items-center">

                        {{-- Чекбокс для вибору таблиці --}}
                        <input type="checkbox" name="tables[]" value="{{ $table->name }}"
                            @if (is_array(old('tables')) && in_array($table->name, old('tables'))) checked 
                            @elseif (!is_array(old('tables'))) 
                                checked @endif
                            class="mr-2 text-xl table-check"
                            onchange="toggleNestedForm(this, 'table-{{ $table->name }}')">

                        <h2
                            class="text-xl @if ($loop->odd) text-primary @else text-secondary @endif font-semibold">
                            {{ $table->name }}</h2>
                        <x-secondary-button id="button-table-{{ $table->name }}" onsubmit="return false;"
                            onclick="toggleTable('table-{{ $table->name }}')">
                            {{ __('Expand') }}
                        </x-secondary-button>
                    </div>
                    <div class="hidden font-semibold text-danger" id="errors-{{ $table->name }}">
                        {{ __('Relations: ') }}
                    </div>

                    {{-- Вибір стовпців таблиці --}}
                    <div id="table-{{ $table->name }}"
                        class="max-h-0 overflow-hidden transition-all duration-300 ease-in-out hidden mt-4 nested-form">

                        {{-- <div class="mb-4">
                            <input type="text" id="search-input-{{ $table->name }}" placeholder="Пошук стовпців..."
                                class="border-2 border-accent rounded px-4 py-2 w-full">
                        </div> --}}

                        <x-table class="overflow-x-auto border-gray-300">
                            <x-table-header>
                                <x-table-header-cell class="text-normal">{{ __('Column') }}</x-table-header-cell>
                                <x-table-header-cell class="text-normal">{{ __('Data Type') }}</x-table-header-cell>
                                <x-table-header-cell class="text-normal">{{ __('Convert As') }}</x-table-header-cell>
                                <x-table-header-cell class="text-normal">{{ __('Key') }}</x-table-header-cell>
                            </x-table-header>
                            <tbody class="bg-white divide-y">
                                @foreach ($table->columns as $column)
                                    <x-table-row class="border-gray-300">
                                        <x-table-cell>{{ $column->name }}</x-table-cell>
                                        <x-table-cell>{{ $column->type_name }} /
                                            {{ $column->type }}
                                        </x-table-cell>
                                        <x-table-cell>
                                            <select name="columns[{{ $table->name }}][{{ $column->name }}]"
                                                class="border rounded w-full px-2 py-1">
                                                @foreach ($column->convertable_types as $c_type)
                                                    <option>{{ $c_type }}</option>
                                                @endforeach
                                            </select>
                                        </x-table-cell>
                                        <x-table-cell>
                                            @if ($table->primary_key && in_array($column->name, $table->primary_key))
                                                <span class="font-bold text-secondary">PK</span>
                                            @elseif (findEl($column->name, $table->foreignKeys->toArray()))
                                                <span class="font-bold text-accent">FK</span>
                                            @endif
                                        </x-table-cell>
                                    </x-table-row>
                                @endforeach
                            </tbody>
                        </x-table>


                        @if ($table->foreignKeys->count() > 0)
                            <h3 class="min-w-full text-center text-xl text-accent font-bold mt-4 mb-2">
                                {{ __('Relations') }}</h3>

                            <div id="table-{{ $table->name }}-relations"
                                class="overflow-hidden transition-all duration-300 ease-in-out mt-4 nested-form">
                                <x-table class="border-gray-300">
                                    <x-table-header>
                                            <x-table-header-cell>{{ __('Local Fields') }}</x-table-header-cell>
                                            <x-table-header-cell>{{ __('Referenced Table') }}</x-table-header-cell>
                                            <x-table-header-cell>{{ __('Referenced Columns') }}</x-table-header-cell>
                                            <x-table-header-cell>{{ __('Relation Type') }}</x-table-header-cell>
                                    </x-table-header>
                                    <tbody>
                                        @foreach ($table->foreignKeys as $fk)
                                        <x-table-row class="border-gray-300">
                                                <x-table-cell>
                                                    <ul>
                                                        @foreach ($fk->columns as $col)
                                                            <li>{{ $col }}</li>
                                                        @endforeach
                                                    </ul>
                                                </x-table-cell>
                                                <x-table-cell>{{ $fk->foreign_table }}</x-table-cell>
                                                <x-table-cell>
                                                    <ul>
                                                        @foreach ($fk->foreign_columns as $col)
                                                            <li>{{ $col }}</li>
                                                        @endforeach
                                                    </ul>
                                                </x-table-cell>
                                                <x-table-cell>
                                                    @php
                                                        $relationType = $fk->relation_type;
                                                    @endphp
                                                    <x-relation-type-badge :relation-type="$relationType" />
                                                </x-table-cell>
                                            </x-table-row>
                                        @endforeach
                                    </tbody>
                                </x-table>
                            </div>
                        @endif
                        <div class="flex mt-4">
                            <x-secondary-button class="mr-0 ml-auto"
                                onclick="toggleTable('table-{{ $table->name }}')">
                                {{ __('Collapse') }}
                            </x-secondary-button>
                        </div>
                    </div>
                </div>
            @endforeach

            <x-primary-button id="submit">{{ __('Save') }}</x-primary-button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-input');
            const tableContainers = document.querySelectorAll('.table-container');

            const filterTables = (query) => {
                tableContainers.forEach(container => {
                    const tableName = container.getAttribute('data-table-name').toLowerCase();
                    if (tableName.includes(query)) {
                        container.classList.remove('hidden');
                    } else {
                        container.classList.add('hidden');
                    }
                });
            };

            searchInput.addEventListener('input', () => {
                let query = searchInput.value.toLowerCase();
                filterTables(query);
            });
        });
    </script>

    <script>
        // Функція для розгортання/згортання таблиці
        function toggleTable(tableId) {
            let table = document.getElementById(tableId);
            let button = document.getElementById('button-' + tableId);
            if (table.classList.contains('hidden')) {
                table.classList.remove('hidden');
                table.classList.add('max-h-0');
                button.innerText = '{{ __('Collapse') }}';
                setTimeout(() => {
                    table.classList.remove('max-h-0');
                    // table.classList.add('max-h-screen');
                }, 10); // Дрібна затримка для активації анімації
            } else {
                // table.classList.remove('max-h-screen');
                table.classList.add('max-h-0');
                button.innerText = '{{ __('Expand') }}';
                setTimeout(() => {
                    table.classList.add('hidden');
                }, 300); // Затримка для завершення анімації
            }
        }

        // Функція для вимкнення/включення вкладених форм
        function toggleNestedForm(checkbox, formId) {
            const formContainer = document.getElementById(formId);
            if (!formContainer) return;

            if (checkbox.checked) {
                formContainer.classList.remove('disabled');
                formContainer.querySelectorAll('input, select').forEach(input => input.disabled = false);
            } else {
                formContainer.classList.add('disabled');
                formContainer.querySelectorAll('input, select').forEach(input => input.disabled = true);
            }
        }

        function toggleAllNestedForms() {
            // Налаштувати стан вкладених форм на основі чекбоксів
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                const formId = 'table-' + checkbox.value;
                // if (formId) {
                toggleNestedForm(checkbox, formId);
                // }
            });
        }

        toggleAllNestedForms();
    </script>
    <script>
        // $(document).ready(function() {
        //     // Функція, яка перевіряє зв'язки таблиць
        //     function checkTableRelations() {
        //         // Отримуємо всі обрані чекбокси таблиць
        //         const selectedTables = $('input[name="tables[]"]:checked').map(function() {
        //             return $(this).val();
        //         }).get();

        //         // Обходимо всі таблиці
        //         $('.table-container').each(function() {
        //             const tableName = $(this).data('table-name'); // Ім'я таблиці
        //             const isChecked = selectedTables.includes(tableName); // Чи обрана таблиця

        //             // Отримуємо всі зв'язки для поточної таблиці
        //             const relatedTables = $(`#table-${tableName}-relations tr td:nth-child(2)`).map(
        //                 function() {
        //                     return $(this).text().trim(); // Отримуємо назви посилальних таблиць
        //                 }).get();

        //             // Якщо таблиця вибрана, перевіряємо її зв'язки
        //             if (isChecked) {
        //                 relatedTables.forEach(function(relatedTable) {
        //                     // Якщо зв'язана таблиця не обрана, підсвічуємо її
        //                     if (!selectedTables.includes(relatedTable)) {
        //                         $(`.table-container[data-table-name="${relatedTable}"]`).addClass(
        //                             'border-danger').addClass('border-4');
        //                     }
        //                 });
        //             }
        //         });

        //         // Тепер видаляємо border-danger з усіх таблиць, які не мають зв'язків з обраними
        //         $('.table-container').each(function() {
        //             const tableName = $(this).data('table-name');
        //             const hasDangerBorder = $(this).hasClass('border-danger');
        //             const relatedTables = $(`#table-${tableName}-relations tr td:nth-child(2)`).map(
        //                 function() {
        //                     return $(this).text().trim(); // Отримуємо назви посилальних таблиць
        //                 }).get();

        //             // Перевіряємо, чи є невибрані зв'язані таблиці
        //             const hasUnselectedRelated = relatedTables.some(function(relatedTable) {
        //                 return !selectedTables.includes(relatedTable);
        //             });

        //             // Якщо таблиця не вибрана і не має невибраних зв'язаних, видаляємо border-danger
        //             if (!isChecked && hasDangerBorder && !hasUnselectedRelated) {
        //                 $(this).removeClass('border-danger').removeClass('border-4');
        //             }
        //         });
        //     }

        //     // Викликаємо функцію при зміні стану чекбокса
        //     $('input[name="tables[]"]').change(function() {
        //         // Спочатку прибираємо всі border-danger
        //         $('.table-container').removeClass('border-danger').removeClass('border-4');
        //         checkTableRelations();
        //     });

        //     // Ініціалізуємо перевірку при завантаженні сторінки
        //     checkTableRelations();
        // });


        // Функція, яка перевіряє зв'язки таблиць
        function checkTableRelations() {
            // Отримуємо всі обрані чекбокси таблиць
            const selectedTables = $('input[name="tables[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            // Спочатку видаляємо всі червоні підсвічування
            $('.table-container').removeClass('border-danger hover:border-danger border-4');
            $('[id^="errors-"]').html("{{ __('Relations: ') }}").addClass('hidden'); // Очищуємо всі поля помилок

            // var isChecked = false;
            // Обходимо всі таблиці
            $('.table-container').each(function() {
                const tableName = $(this).data('table-name'); // Ім'я таблиці
                const isChecked = selectedTables.includes(tableName); // Чи обрана таблиця

                // Отримуємо всі зв'язки для поточної таблиці
                const relatedTables = $(`#table-${tableName}-relations tr td:nth-child(2)`).map(function() {
                    return $(this).text().trim(); // Отримуємо назви посилальних таблиць
                }).get();

                // Якщо таблиця вибрана, перевіряємо її зв'язки
                if (isChecked) {
                    relatedTables.forEach(function(relatedTable) {
                        // Якщо зв'язана таблиця не обрана, підсвічуємо її
                        if (!selectedTables.includes(relatedTable)) {
                            $(`.table-container[data-table-name="${relatedTable}"]`).addClass(
                                'border-danger hover:border-danger border-4');
                            // Відображаємо пов'язані таблиці у полі помилок
                            const errors = $(`#errors-${relatedTable}`);
                            errors.append(`${tableName}; `);
                            if (errors.hasClass('hidden')) {
                                errors.removeClass('hidden');
                            }
                        }
                    });
                }
            });

            toggleAllNestedForms();

            // // Тепер видаляємо border-danger з усіх таблиць, які не мають зв'язків з обраними
            // $('.table-container').each(function() {
            //     const tableName = $(this).data('table-name');
            //     const hasDangerBorder = $(this).hasClass('border-danger');
            //     // const isChecked = selectedTables.includes(tableName); // Чи обрана таблиця

            //     // Отримуємо всі зв'язки для поточної таблиці
            //     const relatedTables = $(`#table-${tableName}-relations tr td:nth-child(2)`).map(function() {
            //         return $(this).text().trim(); // Отримуємо назви посилальних таблиць
            //     }).get();

            //     // Перевіряємо, чи є невибрані зв'язані таблиці
            //     const hasUnselectedRelated = relatedTables.some(function(relatedTable) {
            //         return !selectedTables.includes(relatedTable);
            //     });

            //     // Якщо таблиця не вибрана і не має зв'язків з обраними, видаляємо border-danger
            //     if (!isChecked && hasDangerBorder && !hasUnselectedRelated) {
            //         $(this).removeClass('border-danger hover:border-danger border-4');
            //         $(`#errors-${tableName}`).html(''); // Очищуємо поле помилок
            //     }
            // });
        }

        function clearInputErrors() {
            // Спочатку прибираємо всі border-danger
            $('.table-container').removeClass('border-danger hover:border-danger border-4');
            // Очищуємо всі поля помилок
            $('[id^="errors-"]').html('');
            checkTableRelations();
        }

        $(document).ready(function() {
            // Викликаємо функцію при зміні стану чекбокса
            $('input[name="tables[]"]').change(function() {
                clearInputErrors();
            });

            // Ініціалізуємо перевірку при завантаженні сторінки
            checkTableRelations();
        });
    </script>

    <script>
        function selectAll() {
            const tableCheckboxes = document.querySelectorAll('.table-check');
            tableCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            clearInputErrors();
        }

        function deselectAll() {
            const tableCheckboxes = document.querySelectorAll('.table-check');
            tableCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            clearInputErrors();
        }
    </script>

    <script>
        function showModal() {
            window.dispatchEvent(new CustomEvent('open-modal', {
                detail: 'select-action' // name of modal
            }));
        }

        function getMissingTables() {
            // Збираємо всі таблиці, які мають клас 'border-danger'
            const missingTables = $('.table-container.border-danger').map(function() {
                return $(this).data(
                    'table-name'); // Припустимо, ім'я таблиці зберігається в атрибуті data-table-name
            }).get(); // Використовуємо .get() для перетворення в jQuery об'єкт в масив

            return missingTables;
        }

        function selectMissingTables() {

            @if (session()->has('missingTables'))
                const tables = @json(session('missingTables'));
            @else
                const tables = getMissingTables();
            @endif

            if (!Array.isArray(tables)) {
                tables = [tables];
            }

            tables.forEach(table => {
                const checkbox = document.querySelector(`input[value="${table}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    // toggleNestedForm(checkbox, 'table-' + table); // Відкриваємо вкладену форму
                }
            });

            clearInputErrors();
        }

        function breakRelationsAndSumbit() {
            activateBreakRelationsMode();
            submitForm();
        }

        function activateBreakRelationsMode() {
            let input = document.querySelector('#break-relations');
            input.value = 'break';
        }

        function submitForm() {
            let form = document.querySelector('#form');
            if (form) {
                form.requestSubmit();
            } else {
                console.error('Error sumbit form');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {

            $('#submit').on('click', function(event) {

                @if (session()->has('missingTables'))
                    const tables = @json(session('missingTables'));
                @else
                    const tables = getMissingTables();
                @endif

                if (tables.length != 0) {
                    $('#missing-tables-container').html(tables.join('; '));
                    showModal();
                    event.preventDefault(); // Відміняємо відправку
                }

            });
        });
    </script>

</x-app-layout>
