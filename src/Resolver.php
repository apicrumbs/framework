<?php

namespace ApiCrumbs\Framework;

class Resolver
{
    public static function sort(array $crumbs): array
    {
        $sorted = [];
        $visited = [];

        $visit = function ($name) use (&$visit, &$crumbs, &$sorted, &$visited) {
            if (isset($visited[$name])) return;
            $visited[$name] = true;

            foreach ($crumbs[$name]->getDependencies() as $dep) {
                if (isset($crumbs[$dep])) {
                    $visit($dep);
                }
            }
            $sorted[] = $crumbs[$name];
        };

        foreach (array_keys($crumbs) as $name) {
            $visit($name);
        }

        return $sorted;
    }
}