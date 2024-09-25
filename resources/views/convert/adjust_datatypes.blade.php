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
                <h2 class="text-2xl font-medium text-info">
                    {{ session('message', __('Action Required for Missing Tables')) }}
                </h2>

                @if (!empty(session('missingTables')))
                    <p class="mt-6 text-lg text-danger">
                        {{ __('Обрані вами таблиці мають зв`язки з наступними таблицями, які ви не обрали:') }}
                    </p>
                    <div class="mt-2 text-lg text-gray font-bold">
                        {{ implode(', ', session('missingTables', [])) }}
                    </div>
                @endif

                <div class="mt-6">
                    <h3 class="text-2xl font-medium text-info">
                        {{ __('Please select an action:') }}
                    </h3>

                    <div class="flex justify-between">
                        <div class="mt-4 mx-1">
                            <x-primary-button onclick="selectMissingTables()" x-on:click="$dispatch('close')">
                                {{ __('Automatically Select These Tables') }}
                            </x-primary-button>
                        </div>

                        <div class="mt-4 mx-1">
                            <x-danger-button onclick="breakRelationsAndSumbit()">
                                {{ __('Break Relations and Continue') }}
                            </x-danger-button>
                        </div>

                        <div class="mt-4 mx-1" x-on:click="$dispatch('close')">
                            <x-secondary-button>
                                {{ __('Leave for Manual Editing') }}
                            </x-secondary-button>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>
    </section>


    <div class="container mx-auto p-6">

        <x-input-errors-block />

        <div class="mb-4 flex items-center space-x-2">
            <input type="text" id="search-input" placeholder="Пошук таблиць..."
                class="border-2 border-accent rounded px-4 py-2 flex-grow">

            <button id="select-all" onclick="selectAll()"
                class="bg-primary text-white rounded px-4 py-2 hover:bg-accent">
                Відмітити всі
            </button>

            <button id="deselect-all" onclick="deselectAll()"
                class="bg-secondary text-white rounded px-4 py-2 hover:bg-accent">
                Зняти всі
            </button>
        </div>

        <!-- Загальна форма для вибору таблиць і стовпців -->
        <form action="{{ route('convert.step.store', [$convert, 'adjust_datatypes']) }}" method="POST" id="form">
            @csrf
            <input type="hidden" name="break_relations" id="break-relations" value="no-break">

            <h1 class="text-3xl font-bold mb-6">Перелік таблиць</h1>

            @foreach ($tables as $table)
                <!-- Таблиці -->
                <div class="border p-4 rounded mb-4 table-container
                @if ($loop->odd) bg-light @endif
                 "
                    data-table-name="{{ $table->name }}">
                    <div class="flex justify-between items-center">

                        <!-- Чекбокс для вибору таблиці -->
                        <input type="checkbox" name="tables[]" value="{{ $table->name }}"
                            @if (is_array(old('tables')) && in_array($table->name, old('tables'))) checked 
                            @elseif (!is_array(old('tables'))) 
                                checked @endif
                            class="mr-2 text-xl table-check"
                            onchange="toggleNestedForm(this, 'table-{{ $table->name }}')">

                        <h2
                            class="text-xl @if ($loop->odd) text-primary @else text-secondary @endif font-semibold">
                            {{ $table->name }}</h2>
                        <!-- Кнопка розгортання -->
                        {{-- <button type="button" class="text-secondary"
                        onclick="toggleTable('table-{{ $table->name }}')">Розгорнути</button> --}}
                        <x-secondary-button onsubmit="return false;"
                            onclick="toggleTable('table-{{ $table->name }}')">
                            Розгорнути
                        </x-secondary-button>
                    </div>

                    {{-- {{ dd($table->columns) }} --}}
                    <!-- Підформа для стовпців таблиці -->
                    <div id="table-{{ $table->name }}"
                        class="max-h-0 overflow-hidden transition-all duration-300 ease-in-out hidden mt-4 nested-form">

                        {{-- <div class="mb-4">
                            <input type="text" id="search-input-{{ $table->name }}" placeholder="Пошук стовпців..."
                                class="border-2 border-accent rounded px-4 py-2 w-full">
                        </div> --}}

                        <table class="min-w-full border">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 border">Стовпець</th>
                                    <th class="px-4 py-2 border">Тип даних</th>
                                    <th class="px-4 py-2 border">Конвертувати як</th>
                                    <th class="px-4 py-2 border">Ключ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($table->columns as $column)
                                    <tr>
                                        <td class="px-4 py-2 border">{{ $column->name }}</td>
                                        <td class="px-4 py-2 border">{{ $column->type_name }} / {{ $column->type }}
                                        </td>
                                        <td class="px-4 py-2 border">
                                            <select name="columns[{{ $table->name }}][{{ $column->name }}]"
                                                class="border rounded w-full px-2 py-1">
                                                @foreach ($column->convertable_types as $c_type)
                                                    <option>{{ $c_type }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 border">

                                            @if ($table->primary_key && in_array($column->name, $table->primary_key))
                                                <span class="font-bold text-secondary">PK</span>
                                            @elseif (findEl($column->name, $table->foreignKeys->toArray()))
                                                <span class="font-bold text-accent">FK</span>
                                            @endif

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>


                        @if ($table->foreignKeys->count() > 0)
                            <h3 class="min-w-full text-center text-2-xl text-accent font-bold mt-4 mb-2">Зв`язки</h3>

                            <div id="table-{{ $table->name }}-relations"
                                class="overflow-hidden transition-all duration-300 ease-in-out mt-4 nested-form">
                                <table class="min-w-full border border-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 border">Локальні стовпці</th>
                                            <th class="px-4 py-2 border">Посилальна таблиця</th>
                                            <th class="px-4 py-2 border">Посилальні стовпці</th>
                                            <th class="px-4 py-2 border">Тип зв'язку</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($table->foreignKeys as $fk)
                                            <tr>
                                                <td class="px-4 py-2 border">
                                                    <ul>
                                                        @foreach ($fk->columns as $col)
                                                            <li>{{ $col }}</li>
                                                        @endforeach
                                                    </ul>
                                                </td>
                                                <td class="px-4 py-2 border">{{ $fk->foreign_table }}</td>
                                                <td class="px-4 py-2 border">
                                                    <ul>
                                                        @foreach ($fk->foreign_columns as $col)
                                                            <li>{{ $col }}</li>
                                                        @endforeach
                                                    </ul>
                                                </td>
                                                <td class="px-4 py-2 border">{{ $fk->relation_type }}</td>
                                            <tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
            {{-- {{ s }} --}}
            <!-- Кнопка для надсилання вибраних таблиць -->
            <button type="submit" class="mt-4 bg-primary text-white px-4 py-2 rounded">Зберегти</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-input');
            const tableContainers = document.querySelectorAll('.table-container');

            // console.log(searchInput.value, '\n',tableContainers);

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
        function selectAll() {
            const tableCheckboxes = document.querySelectorAll('.table-check');
            tableCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function deselectAll() {
            const tableCheckboxes = document.querySelectorAll('.table-check');
            tableCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    </script>

    <script>
        // Функція для розгортання/згортання таблиці
        function toggleTable(tableId) {
            const table = document.getElementById(tableId);
            if (table.classList.contains('hidden')) {
                table.classList.remove('hidden');
                table.classList.add('max-h-0');
                setTimeout(() => {
                    table.classList.remove('max-h-0');
                    // table.classList.add('max-h-screen');
                }, 10); // Дрібна затримка для активації анімації
            } else {
                // table.classList.remove('max-h-screen');
                table.classList.add('max-h-0');
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

        // Спочатку налаштувати стан вкладених форм на основі чекбоксів
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            const formId = 'table-' + checkbox.value;
            if (formId) {
                toggleNestedForm(checkbox, formId);
            }
        });
    </script>

    @if (session()->has('missingTables'))
        <script>
            function selectMissingTables() {
                const tables = @json(session('missingTables'));
                // const message = "{{ session('message', '') }}";

                // console.log(message);

                // Викликаємо функцію для автоматичного вибору таблиць
                // selectMissingTables(missingTables);

                if (!Array.isArray(tables)) {
                    tables = [tables];
                }
                // console.log(tables)

                tables.forEach(table => {
                    const checkbox = document.querySelector(`input[value="${table}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        toggleNestedForm(checkbox, 'table-' + table); // Відкриваємо вкладену форму
                    }
                });
            }

            function breakRelationsAndSumbit() {
                activateBreakRelationsMode()
                submitForm();
            }

            function activateBreakRelationsMode() {
                let input = document.querySelector('#break-relations');
                input.value = 'break';
            }

            function submitForm() {
                let form = document.querySelector('#form');
                form.submit();
            }
        </script>
    @endif

</x-app-layout>
