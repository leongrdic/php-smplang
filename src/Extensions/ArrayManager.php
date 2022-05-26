<?php

namespace Le\SMPLang\Extensions;

class ArrayManager
{
    public function __construct(public \Le\SMPLang\SMPLang $instance)
    {
        // ...
    }

    public function variables(): array
    {
        $phpFunctions = [
            'array_key_exists',
            'array_map',
            'array_sum',
            'array_keys',
            'array_merge',
            'array_filter',
            'array_search',
            'array_is_list',
        ];

        $vars = [];

        foreach ($phpFunctions as $phpFunction) {
            $vars[$phpFunction] = fn (...$args) => $phpFunction(...$args);
        }

        return $vars;
    }
}
