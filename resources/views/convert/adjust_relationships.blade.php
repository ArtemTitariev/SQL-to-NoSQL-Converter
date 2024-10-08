<x-app-layout>
    <x-header-content>
        {{ __('Adjust Relationships') }}
    </x-header-content>

    @php
        $mongoDB = $convert->mongoDatabase;
        $collections = $mongoDB
            ->collections()
            ->whereHas('linksEmbeddsFrom')
            ->orWhereHas('manyToManyPivot')
            ->with(['fields', 'linksEmbeddsFrom', 'manyToManyPivot'])
            ->get();
    @endphp

    <button id="toggleButton" class="sticky top-0 mt-4 bg-gray-700 text-white p-2">Toggle</button>

    <div id="container" class="grid grid-cols-1 gap-4 md:grid-cols-1 lg:grid-cols-[60%_40%]">
        <!-- Правий блок -->
        <div id="rightBlock" class="order-1 md:order-1 lg:order-2 bg-green-100 p-4 flex">
            Правий блок (40%)
        </div>

        <div id="leftBlock" class="order-2 md:order-2 lg:order-1 p-4">
            {{-- <div class="container mx-auto py-8"> --}}
            @foreach ($collections as $collection)
                <div
                    class="border-2 rounded-lg p-4 mb-6 @if ($loop->odd) bg-light @else bg-white @endif shadow-sm">
                    <h2
                        class="text-xl font-semibold mb-4 @if ($loop->odd) text-primary @else text-secondary @endif">
                        {{ $collection->name }}</h2>

                    {{-- Links + Embeddings Section --}}
                    @if ($collection->linksEmbeddsFrom->isNotEmpty())
                        <h3 class="text-lg font-bold text-info mb-2">{{ __('Links + embeddings') }}</h3>
                        <div class="overflow-x-auto">
                            <x-table class="border-gray-300">
                                <x-table-header>
                                    <x-table-header-cell>Ред</x-table-header-cell>
                                    <x-table-header-cell>{{ __('PK Collection') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Relation Type') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('SQL Relation') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Local Fields') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Foreign Fields') }}</x-table-header-cell>

                                </x-table-header>
                                <tbody class="bg-white divide-y">
                                    @foreach ($collection->linksEmbeddsFrom as $relationFrom)
                                        <x-table-row class="border-gray-300">
                                            <x-table-cell><x-icon-link href="#"
                                                    :icon="'icons.edit'" /></x-table-cell>
                                            <x-table-cell>{{ $relationFrom->pkCollection->name }}</x-table-cell>
                                            <x-table-cell>
                                                <x-mongo-relation-type-badge :relation-type="$relationFrom->relation_type" />
                                            </x-table-cell>
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
                        <h3 class="text-lg font-bold text-accent mt-6 mb-2">{{ __('Many to many') }}</h3>
                        <div class="overflow-x-auto">
                            <x-table class="border-gray-300">
                                <x-table-header>
                                    <x-table-header-cell>Ред</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Relation Type') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Bidirectional') }}</x-table-header-cell>

                                    <x-table-header-cell>{{ __('Local Fields 1') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Local Fields 2') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Collection 1') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Foreign Fields 1') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Collection 2') }}</x-table-header-cell>
                                    <x-table-header-cell>{{ __('Foreign Fields 2') }}</x-table-header-cell>
                                </x-table-header>
                                <tbody>
                                    @foreach ($collection->manyToManyPivot as $nnRelation)
                                        <x-table-row class="border-gray-300">
                                            <x-table-cell><x-icon-link href="#"
                                                    :icon="'icons.edit'" /></x-table-cell>
                                            <x-table-cell>
                                                <x-many-to-many-relation-badge :relation-type="$nnRelation->relation_type" />
                                            </x-table-cell>
                                            <x-table-cell>{{ $nnRelation->is_bidirectional ? 'Yes' : 'No' }}</x-table-cell>

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
                </div>
            @endforeach
        </div>


    </div>

    <script>
        const toggleButton = document.getElementById('toggleButton');
        const leftBlock = document.getElementById('leftBlock');
        const rightBlock = document.getElementById('rightBlock');
        const container = document.getElementById('container');

        toggleButton.addEventListener('click', () => {
            rightBlock.classList.toggle('hidden');
            if (rightBlock.classList.contains('hidden')) {
                rightBlock.classList.toggle('flex');
                // Якщо правий блок приховано, змінюємо структуру на всю ширину
                container.classList.replace('lg:grid-cols-[60%_40%]', 'lg:grid-cols-1');
                leftBlock.classList.add('w-full');
            } else {
                rightBlock.classList.toggle('flex');
                // Якщо правий блок знову відображається, повертаємо структуру
                container.classList.replace('lg:grid-cols-1', 'lg:grid-cols-[60%_40%]');
                leftBlock.classList.remove('w-full');
            }
        });
    </script>

    <style>
        @media (min-width: 1024px) {

            /* Для великих екранів */
            #rightBlock {
                position: fixed;
                right: 0;
                width: 40%;
                height: 100%;
                /* display: flex; */
                justify-content: center;
                align-items: center;
            }
        }

        @media (max-width: 1024px) {

            /* Для малих екранів */
            #rightBlock {
                position: sticky;
                top: 50px;
            }
        }
    </style>
</x-app-layout>
