<section>
    <div class="container mx-auto">
        <x-input-errors-block />
        <form method="post" action="{{ route('converts.store') }}">
            @csrf


            {{-- RDB type --}}
            <div>
                <x-input-label for="driver" :value="__('Select Relation Database Type')" class="" />
                <div class="flex flex-wrap gap-4 mt-2">
                    @foreach ($supportedDatabases as $type => $name)
                        <label class="cursor-pointer flex items-center">
                            <input type="radio" id="driver" name="sql_database[driver]" value="{{ $type }}"
                                class="hidden peer"
                                {{ $type === old('sql_database.driver', 'mysql') ? 'checked' : '' }} />

                            <div
                                class="border-gray-300 text-secondary focus:border-secondary focus:ring-secondary rounded-md shadow-sm cursor-pointer p-4 peer-checked:bg-primary peer-checked:text-light flex items-center gap-4">
                                <img src="{{ asset('database-icons/' . $name . '.png') }}"
                                    alt="{{ $name }} Logo" class="h-24 w-24 object-contain">
                                <h5 class="font-bold">{{ $name }} {{ __('database') }}</h5>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="py-2 mt-5">
                <div>
                    {{-- SQL Database fields --}}
                    <h2 class="text-l font-semibold text-secondary">{{ __('SQL Database Connection Params') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 mt-4 gap-6">
                        {{-- Common fields for SQL databases --}}
                        @foreach ($commonFields as $field => $label)
                            <div class="common-fields">
                                <x-input-label for="{{ $label }}" :value="__($label)" />
                                <x-text-input id="{{ $label }}" name="sql_database[{{ $label }}]"
                                    value="{{ old('sql_database.' . $label) }}"
                                    type="{{ $label === 'password' ? $label : 'text' }}" class="mt-1 block w-full"
                                    autofocus />
                                {{-- required --}}
                                <x-input-error class="mt-2" :messages="$errors->get('sql_database.' . $label)" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Specific fields for SQL databases --}}
                    <div class="mt-5">
                        <h2 class="text-l font-semibold text-secondary">{{ __('SQL Database Specific Params') }}</h2>
                        <div class="">
                            {{-- {{ dd($dbSpecificFields) }} --}}
                            @foreach ($dbSpecificFields as $dbType => $fields)
                                <div id="{{ $dbType }}-fields"
                                    class="db-specific-fields grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
                                    @foreach ($fields as $field => $label)
                                        <div>
                                            <x-input-label for="{{ $label }}" :value="__($label)" />
                                            <x-text-input id="{{ $label }}"
                                                name="sql_database[{{ $label }}]"
                                                value="{{ old('sql_database.' . $label) }}" type="text"
                                                class="mt-1 block w-full" />
                                            <x-input-error class="mt-2" :messages="$errors->get('sql_database.' . $label)" />
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- MongoDB fields --}}
            <div class="py-2 mt-5">
                <h2 class="text-l font-semibold text-secondary">{{ __('MongoDB Connection Params') }}</h2>
                <div class="mt-4 gap-6">
                    <x-input-label for="mongo_dsn" :value="__('DSN')" />
                    <x-text-input id="mongo_dsn" name="mongo_database[dsn]" value="{{ old('mongo_database.dsn') }}"
                        type="text" class="mt-1 block w-full" required />
                    <x-input-error class="mt-2" :messages="$errors->get('mongo_database.dsn')" />
                </div>
                <div class="mt-4 gap-6">
                    <x-input-label for="mongo_database" :value="__('Database')" />
                    <x-text-input id="mongo_database" name="mongo_database[database]"
                        value="{{ old('mongo_database.database') }}" type="text" class="mt-1 block w-full"
                        required />
                    <x-input-error class="mt-2" :messages="$errors->get('mongo_database.database')" />
                </div>
            </div>

            <div class="py-2 mt-5">
                <h2 class="text-l font-semibold text-secondary">{{ __('Additional') }}</h2>
                <div class="mt-4 gap-6">
                    <x-input-label for="description" :value="__('Provide short description')" />
                    <x-textarea id="description" name="description"
                        value="{{ old('description') }}" type="text" class="mt-1 block w-full"
                     />
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>
            </div>
            <div class="flex items-center mt-5 ">
                <x-primary-button>{{ __('Test connections and save') }}</x-primary-button>
            </div>
        </form>
    </div>
</section>

<script>
    const radioButtonsSelector = 'input[name="sql_database[driver]"]';

    document.addEventListener('DOMContentLoaded', function() {
        const radioButtons = document.querySelectorAll(radioButtonsSelector);
        const commonFields = document.querySelectorAll('.common-fields');
        const mysqlFields = document.querySelectorAll('#mysql-fields');
        const pgsqlFields = document.querySelectorAll('#pgsql-fields');

        const show = (field) => {
            field.style.display = 'grid';
        }
        const hide = (field) => {
            field.style.display = 'none';
        }

        const showFields = (selectedDbType) => {
            // Показати загальні поля
            commonFields.forEach(field => {
                show(field);
            });

            // Показати відповідні поля і сховати інші
            if (selectedDbType === 'mysql') {
                mysqlFields.forEach(field => {
                    show(field);
                });
                pgsqlFields.forEach(field => {
                    hide(field);
                });
            } else if (selectedDbType === 'pgsql') {
                pgsqlFields.forEach(field => {
                    show(field);
                });
                mysqlFields.forEach(field => {
                    hide(field);
                });

                //тут для інших БД

            } else {
                mysqlFields.forEach(field => {
                    hide(field);
                });
                pgsqlFields.forEach(field => {
                    hide(field);
                });
            }
        };

        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                const selectedDbType = this.value;
                showFields(selectedDbType);
            });
        });

        // Викликати showFields з поточний вибраним значенням 
        // одразу після завантаження сторінки
        const selectedDbType = document.querySelector(radioButtonsSelector + ':checked').value;
        showFields(selectedDbType);
    });
</script>
