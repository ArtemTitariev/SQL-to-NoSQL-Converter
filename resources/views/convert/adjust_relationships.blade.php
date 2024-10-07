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

    <div class="container mx-auto py-8">
        @foreach ($collections as $collection)
            <div
                class="border-2 rounded-lg p-4 mb-6 @if ($loop->odd) bg-light @else bg-white @endif shadow-sm">
                <h2
                    class="text-xl font-semibold mb-4 @if ($loop->odd) text-primary @else text-secondary @endif">
                    {{ $collection->name }}</h2>

                {{-- Links + Embeddings Section --}}
                @if ($collection->linksEmbeddsFrom->isNotEmpty())
                    <h3 class="text-lg font-bold text-primary mb-2">{{ __('Links + embeddings') }}</h3>
                    <div class="overflow-x-auto">
                        <x-table class="border-gray-300">
                            <x-table-header>
                                <x-table-header-cell>{{ __('Local Fields') }}</x-table-header-cell>
                                <x-table-header-cell>{{ __('PK Collection') }}</x-table-header-cell>
                                <x-table-header-cell>{{ __('Foreign Fields') }}</x-table-header-cell>
                                <x-table-header-cell>{{ __('SQL Relation') }}</x-table-header-cell>
                                <x-table-header-cell>{{ __('Relation Type') }}</x-table-header-cell>
                            </x-table-header>
                            <tbody class="bg-white divide-y">
                                @foreach ($collection->linksEmbeddsFrom as $relationFrom)
                                    <x-table-row class="border-gray-300">
                                        <x-table-cell>
                                            <ul>
                                                @foreach ($relationFrom->local_fields as $localField)
                                                    <li>{{ $localField }}</li>
                                                @endforeach
                                            </ul>
                                        </x-table-cell>
                                        <x-table-cell>{{ $relationFrom->pkCollection->name }}</x-table-cell>
                                        <x-table-cell>
                                            <ul>
                                                @foreach ($relationFrom->foreign_fields as $foreignField)
                                                    <li>{{ $foreignField }}</li>
                                                @endforeach
                                            </ul>
                                        </x-table-cell>
                                        <x-table-cell>
                                            {{-- {{ $relationFrom->sql_relation }} --}}
                                            <x-relation-type-badge :relation-type="$relationFrom->sql_relation" />
                                        </x-table-cell>
                                        <x-table-cell>
                                            {{-- {{ $relationFrom->relation_type }} --}}
                                            <x-mongo-relation-type-badge :relation-type="$relationFrom->relation_type" />
                                        </x-table-cell>
                                    </x-table-row>
                                @endforeach
                            </tbody>
                        </x-table>
                    </div>
                @endif

                {{-- Many to Many Section --}}
                @if ($collection->manyToManyPivot->isNotEmpty())
                    <h3 class="text-lg font-bold text-secondary mt-6 mb-2">{{ __('Many to many') }}</h3>
                    <x-table class="border-gray-300">
                        <x-table-header>
                            <x-table-header-cell>{{ __('Local Fields 1') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Local Fields 2') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Collection 1') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Foreign Fields 1') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Collection 2') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Foreign Fields 2') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Relation Type') }}</x-table-header-cell>
                            <x-table-header-cell>{{ __('Bidirectional') }}</x-table-header-cell>
                        </x-table-header>
                        <tbody>
                            @foreach ($collection->manyToManyPivot as $nnRelation)
                                <x-table-row class="border-gray-300">
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
                                    <x-table-cell>
                                        {{-- {{ $nnRelation->relation_type }} --}}
                                        <x-many-to-many-relation-badge :relation-type="$nnRelation->relation_type" />
                                    </x-table-cell>
                                    <x-table-cell>{{ $nnRelation->is_bidirectional ? 'Yes' : 'No' }}</x-table-cell>
                                </x-table-row>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
