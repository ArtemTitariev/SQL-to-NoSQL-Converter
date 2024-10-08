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

    <button id="toggleButton" class="sticky top-0 mt-4 bg-gray-700 text-white p-2">Toggle</button>

    <div id="container" class="grid grid-cols-1 gap-4 md:grid-cols-1 lg:grid-cols-[60%_40%]">
        <!-- Правий блок -->
        <div id="rightBlock" class="order-1 md:h-80 md:order-1 lg:order-2 bg-green-100 p-4 flex">
            <div id="cy" style="width: 100%; height: 90%;"></div>
        </div>

        <div id="leftBlock" class="order-2 md:order-2 lg:order-1 p-4">
            @foreach ($collections as $collection)
                <div class="border-2 rounded-lg p-4 mb-6 @if ($loop->odd) bg-light @else bg-white @endif shadow-sm"
                    data-collection-name="{{ $collection->name }}">

                    <div class="flex justify-between" onclick="updateGraph({{ json_encode($collection) }})">
                        <h2
                            class="text-xl font-semibold mb-4 @if ($loop->odd) text-primary @else text-secondary @endif">
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

    <script>
        // document.querySelectorAll('[data-collection-name]').forEach(item => {
        //     item.addEventListener('click', event => {
        //         const collectionName = item.getAttribute('data-collection-name');
        //         updateGraph(collectionName);
        //     });
        // });

        // Функція для генерації випадкового кольору
        function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }

        // Функція для визначення яскравості кольору
        function getBrightness(hex) {
            const r = parseInt(hex.substr(1, 2), 16);
            const g = parseInt(hex.substr(3, 2), 16);
            const b = parseInt(hex.substr(5, 2), 16);
            return (r * 0.299 + g * 0.587 + b * 0.114);
        }

        const layout = {
            name: 'cose',
            padding: 80,
            fit: true,
        }

        function updateGraph(data) {
            // Очистити попередні дані
            const nodes = [];
            const edges = [];

            console.log(data);
            // return;

            const collectionName = data.name;
            const linksEmbeddsFrom = data.links_embedds_from || [];
            const manyToManyPivot = data.many_to_many_pivot || [];

            console.log(collectionName, linksEmbeddsFrom, manyToManyPivot);

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
                            id: relation.pk_collection.name,
                        },
                        style: {
                            'background-color': '#bfaf1f'
                        }
                    });
                    edges.push({
                        data: {
                            source: collectionName,
                            target: relation.pk_collection.name,
                            label: relation.relation_type
                        }
                    });
                });
            }

            if (manyToManyPivot.length > 0) {
                // Додати вузли для manyToManyPivot
                manyToManyPivot.forEach(nnRelation => {
                    nodes.push({
                        data: {
                            id: nnRelation.collection1.name,
                            'background-color': getRandomColor()
                        }
                    });
                    nodes.push({
                        data: {
                            id: nnRelation.collection2.name,
                            'background-color': getRandomColor()
                        }
                    });

                    edges.push({
                        data: {
                            source: collectionName,
                            target: nnRelation.collection1.name,
                            // label: nnRelation.relation_type // або інша відповідна властивість
                            label: "Many-to-Many"
                        }
                    });
                    edges.push({
                        data: {
                            source: collectionName,
                            target: nnRelation.collection2.name,
                            // label: nnRelation.relation_type // або інша відповідна властивість
                            label: "Many-to-Many"
                        }
                    });
                });
            }

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
                    name: 'cose',
                    padding: 10
                }
            });
        }
    </script>
</x-app-layout>
