<?php

namespace App\Services;

use App\Models\Convert;

class StepDataProvider
{
    public function getDataForStep(Convert $convert, string $step)
    {
        if (method_exists($this, $step)) {
            return $this->{$step}($convert);
        }

        return [];
    }

    protected function adjust_datatypes(Convert $convert)
    {
        $sqlDatabase = $convert
            ->sqlDatabase()
            ->with(['circularRefs'])
            ->first();

        $tables = $sqlDatabase
            ->tables()
            ->with(['columns', 'foreignKeys'])
            ->get();

        return compact('sqlDatabase', 'tables');
    }

    protected function adjust_relationships(Convert $convert)
    {
        $mongoDatabase = $convert->mongoDatabase;
        $collections = $mongoDatabase
            ->collections()
            ->where(function (\Illuminate\Database\Eloquent\Builder $query) {
                $query->whereHas('linksEmbeddsFrom')
                    ->orWhereHas('manyToManyPivot');
            })
            ->with([
                'linksEmbeddsFrom.pkCollection',
                'manyToManyPivot.collection1',
                'manyToManyPivot.collection2',
            ])
            ->get();

        return compact('mongoDatabase', 'collections');
    }
}
