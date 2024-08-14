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
                    Загальні вимоги:
                </h2>
                <p>Спершу оберіть тип реляційної бази даних та надайте параметри для підключення до неї. Також
                    надайте
                    параметри для підключення до бази даних MongoDB.</p>
                <p>Конвертер протестує підключення.</p>
                <p>Будь ласка, переконайтесь, що в обидві бази даних не вносяться будь які зміни (оновлення даних,
                    індексів, зв’язків) під час конфігурування або виконання конвертації.</p>

                <h2 class="text-md font-semibold flex items-center text-accent">
                    <x-icons.info />
                    Вимоги до реляційної бази даних:
                </h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Права доступу:</strong> Користувач бази даних повинен мати доступ на вибірку даних з
                        цільової бази даних.</li>

                    <li><strong>Зовнішні ключі:</strong> Всі зовнішні ключі (<code>foreign keys</code>) мають бути в
                        межах однієї
                        бази даних. Зовнішні ключі на інші бази даних будуть проігноровані.</li>
                    <li><strong>Консистентність даних:</strong> Якщо таблиця має зовнішній ключ (<code>foreign
                            key</code>), то всі
                        його поля мають бути або <code>nullable</code>, або <code>non-nullable</code>
                        (<code>required</code>).
                        Комбінація може порушити цілісність даних і алгоритм не зможе коректно обробити такий
                        зв'язок.</li>
                </ul>

                <h2 class="text-md font-semibold flex items-center text-accent">
                    <x-icons.info />
                    Вимоги до нереляційної бази даних:
                </h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Переконайтесь, що база даних MongoDB не містить важливі для Вас дані.</li>
                    <li><strong>Для уникнення конфліктів, конвертер буде видаляти колекції з такою ж назвою, як
                            таблиці реляційної бази даних.</strong></li>
                    <li><strong>Користувач MongoDB повинен мати роль <code>readWrite</code> або
                            <code>dbAdmin</code></strong> (для видалення конфліктних колекцій, створення нових
                        колекцій та вставки документів).</li>
                </ul>

            </div>
        </div>
    </x-modal>
</x-app-layout>
