<?php

namespace Le\SMPLang;

use Closure;

class SMPLang
{
    protected const backtickToDoubleQuotes = [
        '"' => '\\"', // escape all double quotes
        "\\`" => "[%{BACKTICK}%]", // to be unescaped
        "`" => '"', // for surrounding
        "[%{BACKTICK}%]" => "`", // restore backticks
    ];

    protected const singleToDoubleQuotes = [
        '"' => '\\"', // escape all double quotes
        "\\'" => "[%{SINGLE_QUOTE}%]", // to be unescaped
        "'" => '"', // for surrounding
        "[%{SINGLE_QUOTE}%]" => "'", // restore single quotes
    ];

    public function __construct(
        protected array $vars = []
    ) {
    }

    public function evaluate(string $expression, array $vars = []): mixed
    {
        if (empty($vars)) {
            return $this->eval($expression);
        }

        $instance = clone $this;
        $instance->vars = [...$instance->vars, ...$vars];
        $result = $instance->eval($expression);
        unset($instance);

        return $result;
    }

    protected function eval(string $input): mixed
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }
        if (is_numeric($input)) {
            return $input + 0;
        }

        $lowered = strtolower($input);
        if ($lowered === 'null') {
            return null;
        }
        if ($lowered === 'true') {
            return true;
        }
        if ($lowered === 'false') {
            return false;
        }

        // all the str_contains()s are for performance reasons
        // even though they might give false positives (e.g. operators in strings)

        // short ternary expression
        if (str_contains($input, '?:')) {
            $ternary = $this->parse($input, '?:');
            if (count($ternary) > 1) {
                foreach ($ternary as $index => $part) {
                    $part = $this->eval($part);
                    if ($part || $index === count($ternary) - 1) {
                        return $part;
                    }
                }
            }
        }

        // ternary expression
        if (str_contains($input, '?')) {
            $ternary = $this->parse($input, '?');
            (count($ternary) > 2) and throw new Exception('unexpected `?`');
            if (count($ternary) === 2) {
                $values = $this->parse($ternary[1], ':');
                (count($values) > 2) and throw new Exception('unexpected `:`');

                return $this->eval($ternary[0]) ? $this->eval($values[0]) : $this->eval($values[1] ?? '');
            }
        }

        // multiple OR expressions
        if (str_contains($input, '||')) {
            $or = $this->parse($input, '||');
            if (count($or) > 1) {
                foreach ($or as $item) {
                    if ($this->eval($item)) {
                        return true;
                    }
                }

                return false;
            }
        }

        // multiple AND expressions
        if (str_contains($input, '&&')) {
            $and = $this->parse($input, '&&');
            if (count($and) > 1) {
                foreach ($and as $item) {
                    if (! $this->eval($item)) {
                        return false;
                    }
                }

                return true;
            }
        }

        // comparison operators
        if (str_contains($input, '===')) {
            $cmp = $this->parse($input, '===');
            if (count($cmp) > 1) {
                $first = $this->eval(array_shift($cmp));
                foreach ($cmp as $item) {
                    if ($this->eval($item) !== $first) {
                        return false;
                    }
                }

                return true;
            }
        }
        if (str_contains($input, '!==')) {
            $cmp = $this->parse($input, '!==');
            if (count($cmp) > 1) {
                $first = $this->eval(array_shift($cmp));
                foreach ($cmp as $item) {
                    if ($this->eval($item) === $first) {
                        return false;
                    }
                }

                return true;
            }
        }
        if (str_contains($input, '==')) {
            $cmp = $this->parse($input, '==');
            if (count($cmp) > 1) {
                $first = $this->eval(array_shift($cmp));
                foreach ($cmp as $item) {
                    if ($this->eval($item) != $first) {
                        return false;
                    }
                }

                return true;
            }
        }
        if (str_contains($input, '!=')) {
            $cmp = $this->parse($input, '!=');
            if (count($cmp) > 1) {
                $first = $this->eval(array_shift($cmp));
                foreach ($cmp as $item) {
                    if ($this->eval($item) == $first) {
                        return false;
                    }
                }

                return true;
            }
        }
        if (str_contains($input, '>=')) {
            $cmp = $this->parse($input, '>=');
            if (count($cmp) > 1) {
                $first = $this->eval(array_shift($cmp));
                foreach ($cmp as $item) {
                    if ($this->eval($item) > $first) {
                        return false;
                    }
                }

                return true;
            }
        }
        if (str_contains($input, '<=')) {
            $cmp = $this->parse($input, '<=');
            if (count($cmp) > 1) {
                $first = $this->eval(array_shift($cmp));
                foreach ($cmp as $item) {
                    if ($this->eval($item) < $first) {
                        return false;
                    }
                }

                return true;
            }
        }
        if (str_contains($input, '>')) {
            $cmp = $this->parse($input, '>');
            if (count($cmp) > 1) {
                $first = $this->eval(array_shift($cmp));
                foreach ($cmp as $item) {
                    if ($this->eval($item) >= $first) {
                        return false;
                    }
                }

                return true;
            }
        }
        if (str_contains($input, '<')) {
            $cmp = $this->parse($input, '<');
            if (count($cmp) > 1) {
                $first = $this->eval(array_shift($cmp));
                foreach ($cmp as $item) {
                    if ($this->eval($item) <= $first) {
                        return false;
                    }
                }

                return true;
            }
        }

        // string concatenation
        if (str_contains($input, '~')) {
            $concat = $this->parse($input, '~');
            if (count($concat) > 1) {
                return array_reduce($concat, fn ($carry, $item) => $carry . $this->eval($item), '');
            }
        }

        // arithmetic operators
        if (str_contains($input, '+')) {
            $add = $this->parse($input, '+');
            if (count($add) > 1) {
                return array_reduce($add, fn ($carry, $item) => $carry + $this->eval($item), 0);
            }
        }
        if (str_contains($input, '-')) {
            $sub = $this->parse($input, '-');
            if (count($sub) > 1) {
                $first = $this->eval(array_shift($sub));

                return array_reduce($sub, fn ($carry, $item) => $carry - $this->eval($item), $first);
            }
        }
        if (str_contains($input, '%')) {
            // IMPORTANT: in SMPLang modulo is parsed like this: a*b%c*d == (a*b)%(c*d)
            $mod = $this->parse($input, '%');
            if (count($mod) > 1) {
                $last = $this->eval(array_pop($mod));

                return $this->eval(implode('%', $mod)) % $last;
            }
        }
        if (str_contains($input, '**')) {
            // IMPORTANT: in SMPLang pow is parsed like this: a*b**c*d == (a*b)**(c*d)
            $pow = $this->parse($input, '**');
            if (count($pow) > 1) {
                return $this->eval(array_shift($pow)) ** $this->eval(implode('**', $pow));
            }
        }
        if (str_contains($input, '*')) {
            $mul = $this->parse($input, '*');
            if (count($mul) > 1) {
                return array_reduce($mul, fn ($carry, $item) => $carry * $this->eval($item), 1);
            }
        }
        if (str_contains($input, '/')) {
            $div = $this->parse($input, '/');
            if (count($div) > 1) {
                $first = $this->eval(array_shift($div));

                return array_reduce($div, fn ($carry, $item) => $carry / $this->eval($item), $first);
            }
        }

        // backtick string
        if (str_starts_with($input, "`")) {
            (! str_ends_with($input, "`")) && throw new Exception('unexpected end of string');

            $rules = static::backtickToDoubleQuotes;
            $input = str_replace(array_keys($rules), array_values($rules), $input);
        }

        // single quote string
        if (str_starts_with($input, "'")) {
            (! str_ends_with($input, "'")) && throw new Exception('unexpected end of string');

            $rules = static::singleToDoubleQuotes;
            $input = str_replace(array_keys($rules), array_values($rules), $input);
        }

        // double quote string
        if (str_starts_with($input, '"')) {
            $output = json_decode($input);
            (json_last_error() !== 0) and throw new Exception('unexpected end of string');

            return $output;
        }

        // expression is negated
        if (str_starts_with($input, '!')) {
            return ! $this->eval(substr($input, 1));
        }

        // if the expression is just wrapped in brackets, remove them
        if (str_starts_with($input, '(')) {
            // check if the input ends with a trailing round bracket
            if (! str_ends_with($input, ')')) {
                throw new Exception("expected closing `)`");
            }

            return $this->eval(substr($input, 1, -1));
        }

        // array definition
        if (str_starts_with($input, '[')) {
            // check if the input ends with a trailing block bracket and get rid of both brackets
            (! str_ends_with($input, ']')) and throw new Exception("expected closing `]`");
            $input = substr($input, 1, -1);

            return $this->evaluateList($input, true);
        }

        // hash definition
        if (str_starts_with($input, '{')) {
            // check if the input ends with a trailing curly bracket and get rid of both brackets
            (! str_ends_with($input, '}')) and throw new Exception("expected closing `}`");
            $input = substr($input, 1, -1);

            return (object) $this->evaluateList($input); // keys are not evaluated
        }

        // callable call
        // if the expression ends with a round bracket (and it doesn't start with one)
        if (str_ends_with($input, ')')) {
            // parse the input delimiting by opening bracket
            // parameter string is in last part, without trailing and leading brackets
            $parts = $this->parse($input, '(', false);
            $params = substr(array_pop($parts), 1, -1);
            $before = implode('', $parts);

            $callable = $this->eval($before);
            if (! is_callable($callable)) {
                throw new Exception("`$before` is not callable");
            }
            $params = $this->evaluateList($params);

            return $callable(...$params);
        }

        // nested var [] access
        // if the expression ends with a block bracket (and it doesn't start with one)
        if (str_ends_with($input, ']')) {
            $parts = $this->parse($input, '[', false);
            $prop = substr(array_pop($parts), 1, -1);
            $before = implode('', $parts);

            return $this->resolveProperty($before, $this->eval($prop));
        }

        // nested var . access
        if (str_contains($input, '.')) {
            $parts = $this->parse($input, '.');
            $after = array_pop($parts);
            $before = implode('.', $parts);

            return $this->resolveProperty($before, $after);
        }

        // finally, if expression doesn't match any conditions above, assume it's a var
        if (! array_key_exists($input, $this->vars)) {
            throw new Exception("var `$input` not defined");
        }

        return $this->vars[$input];
    }

    protected function evaluateList(string $params, bool $evalKeys = false): array
    {
        $params = $this->parse($params, ',');
        foreach ($params as $param) {
            if (! str_starts_with($param, '...')) {
                $paramParts = $this->parse($param, ':');

                match (count($paramParts)) {
                    default => throw new Exception("unexpected `:`"),
                    1 => [$key, $value] = [ null, $this->eval(array_shift($paramParts)) ],
                    2 => [$key, $value] = [
                        $evalKeys ? $this->eval(array_shift($paramParts)) : array_shift($paramParts),
                        $this->eval(array_shift($paramParts))
                    ],
                };

                if ($key !== null) {
                    is_string($key) or throw new Exception("key must be a string");
                    $output[$key] = $value;
                } else {
                    $output[] = $value;
                }
                continue;
            }

            $packed = $this->eval(substr($param, 3));
            (! is_array($packed)) and throw new Exception("can't unpack `$param` - not an array");

            foreach ($packed as $item) {
                $output[] = $item;
            }
        }

        return $output ?? [];
    }

    protected function resolveProperty(string $expression, string $prop): mixed
    {
        $var = $this->eval($expression);

        return match (true) {
            is_array($var) && array_key_exists($prop, $var) => $var[$prop],
            is_object($var) && (property_exists($var, $prop) || method_exists($var, '__get')) => $var->$prop,
            is_object($var) && (method_exists($var, $prop) || method_exists($var, '__call')) => Closure::fromCallable([$var, $prop]),
            default => throw new Exception("element `$prop` doesn't exist in `$expression`"),
        };
    }

    protected function parse(string $input, string $delimiter, bool $omitDelimiter = true): array
    {
        $inQuotes = false;
        $depthRound = $depthBlock = $depthCurly = 0;
        $outputIndex = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $write = true;
            $char = $input[$i];
            $charLeft = $i > 0 ? $input[$i - 1] : null;

            if ($char === '"' && $charLeft !== '\\') {
                if ($inQuotes === '"') {
                    $inQuotes = false;
                } elseif (! $inQuotes) {
                    $inQuotes = '"';
                }
            }
            if ($char === "'" && $charLeft !== '\\') {
                if ($inQuotes === "'") {
                    $inQuotes = false;
                } elseif (! $inQuotes) {
                    $inQuotes = "'";
                }
            }
            if ($char === "`" && $charLeft !== '\\') {
                if ($inQuotes === "`") {
                    $inQuotes = false;
                } elseif (! $inQuotes) {
                    $inQuotes = "`";
                }
            }

            if (! $inQuotes) {
                // skip whitespaces, tabs and newlines
                if (in_array($char, [' ', "\n", "\r", "\t"])) {
                    continue;
                }

                ($char === ')' && $depthRound === 0) and throw new Exception('unexpected `)`');
                ($char === ']' && $depthBlock === 0) and throw new Exception('unexpected `]`');
                ($char === '}' && $depthCurly === 0) and throw new Exception('unexpected `}`');

                if ($depthRound === 0 && $depthBlock === 0 && $depthCurly === 0) {
                    $match = true;

                    for ($j = 0; $j < strlen($delimiter); $j++) {
                        if ($input[$i + $j] !== $delimiter[$j]) {
                            $match = false;

                            break;
                        }
                    }

                    if ($match) {
                        $outputIndex++;
                        $write = ! $omitDelimiter;
                        if ($omitDelimiter) {
                            $i += strlen($delimiter) - 1;
                        }
                    }
                }

                match ($char) {
                    '(' => $depthRound++, ')' => $depthRound--,
                    '[' => $depthBlock++, ']' => $depthBlock--,
                    '{' => $depthCurly++, '}' => $depthCurly--,
                    default => null,
                };
            }

            if ($write) {
                $output[$outputIndex] ??= '';
                $output[$outputIndex] .= $char;
            }
        }

        $inQuotes and throw new Exception("expected closing `$inQuotes`");
        ($depthRound > 0) and throw new Exception("expected closing `)`");
        ($depthBlock > 0) and throw new Exception("expected closing `]`");
        ($depthCurly > 0) and throw new Exception("expected closing `}`");

        return $output ?? [];
    }
}
