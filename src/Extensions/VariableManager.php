<?php

namespace Le\SMPLang\Extensions;

class VariableManager
{
    public function __construct(public \Le\SMPLang\SMPLang $instance)
    {
        // ...
    }

    public function variables(): array
    {
        return [
            "isset" => function ($variable): bool {
                return isset($this->instance->vars[$variable]);
            },
            "unset" => function ($variable) {
                unset($this->instance->vars[$variable]);
            },
        ];
    }
}
