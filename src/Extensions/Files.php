<?php

namespace Le\SMPLang\Extensions;

class Files
{
    public function __construct(public \Le\SMPLang\SMPLang $instance)
    {
        // ...
    }

    public function variables(): array
    {
        $phpFunctions = [
            'is_file',
            'is_dir',
            'file_exists',
            'file_get_contents',
            'file_put_contents',
            'unlink',
        ];

        $vars = [];

        foreach ($phpFunctions as $phpFunction) {
            $vars[$phpFunction] = fn (...$args) => $phpFunction(...$args);
        }

        return $vars;
    }
}
