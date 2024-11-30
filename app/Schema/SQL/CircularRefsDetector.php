<?php

namespace App\Schema\SQL;

final class CircularRefsDetector
{
    public static function detect($foreignKeys)
    {
        $graph = static::buildDependencyGraph($foreignKeys);

        return static::findAllCycles($graph);
    }

    private static function buildDependencyGraph($foreignKeys)
    {
        $graph = [];

        foreach ($foreignKeys as $table => $keys) {
            if (!isset($graph[$table])) {
                $graph[$table] = [];
            }

            foreach ($keys as $key) {
                $graph[$table][] = $key['foreign_table'];
            }
        }

        return $graph;
    }

    private static function findAllCycles($graph)
    {
        $allCycles = [];
        $path = [];

        foreach (array_keys($graph) as $node) {
            $visited = array_fill_keys(array_keys($graph), false);
            static::findCyclesUtil($node, $visited, $path, $graph, $allCycles);
        }

        return $allCycles;
    }

    private static function findCyclesUtil($node, &$visited, &$path, $graph, &$allCycles)
    {
        if (in_array($node, $path)) {
            $cycle = array_slice($path, array_search($node, $path));
            sort($cycle); // Сортуємо, щоб уникнути дублікатів циклів
            if (!in_array($cycle, $allCycles)) {
                $allCycles[] = $cycle;
            }
            return;
        }

        if ($visited[$node]) {
            return;
        }

        $visited[$node] = true;
        $path[] = $node;

        if (isset($graph[$node])) {
            foreach ($graph[$node] as $neighbor) {
                static::findCyclesUtil($neighbor, $visited, $path, $graph, $allCycles);
            }
        }

        array_pop($path);
    }
}
