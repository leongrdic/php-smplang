<?php

namespace Le\SMPLang;

class SMPLang
{

    public function __construct(
        protected array $vars = []
    )
    {
    }

    public function evaluate(string $input): mixed
    {
        $input = trim($input);

        if($input === '') return null;
        if(is_numeric($input)) return $input + 0;

        $lowered = strtolower($input);
        if($lowered === 'null') return null;
        if($lowered === 'true') return true;
        if($lowered === 'false') return false;

        // all the str_contains()s are for performance reasons
        // even though they might give false positives (e.g. operators in strings)

        // short ternary expression
        if(str_contains($input, '?:')) {
            $ternary = $this->parse($input, '?:');
            if (count($ternary) > 1)
                foreach ($ternary as $index => $part) {
                    $part = $this->evaluate($part);
                    if ($part || $index === count($ternary) - 1) return $part;
                }
        }

        // ternary expression
        if(str_contains($input, '?')) {
            $ternary = $this->parse($input, '?');
            if (count($ternary) > 2) throw new Exception('unexpected `?`');
            if (count($ternary) === 2) {
                $values = $this->parse($ternary[1], ':');
                if (count($values) > 2) throw new Exception('unexpected `:`');
                $values[1] ??= '';

                return $this->evaluate($ternary[0]) ? $this->evaluate($values[0]) : $this->evaluate($values[1]);
            }
        }

        // multiple OR expressions
        if(str_contains($input, '||')) {
            $or = $this->parse($input, '||');
            if (count($or) > 1) {
                foreach ($or as $item) if ($this->evaluate($item)) return true;
                return false;
            }
        }

        // multiple AND expressions
        if(str_contains($input, '&&')) {
            $and = $this->parse($input, '&&');
            if (count($and) > 1) {
                foreach ($and as $item) if (!$this->evaluate($item)) return false;
                return true;
            }
        }

        // comparison expressions
        if(str_contains($input, '===')) {
            $cmp = $this->parse($input, '===');
            if (count($cmp) > 1) {
                $first = $this->evaluate(array_shift($cmp));
                foreach ($cmp as $item) if ($this->evaluate($item) !== $first) return false;
                return true;
            }
        }
        if(str_contains($input, '!==')) {
            $cmp = $this->parse($input, '!==');
            if (count($cmp) > 1) {
                $first = $this->evaluate(array_shift($cmp));
                foreach ($cmp as $item) if ($this->evaluate($item) === $first) return false;
                return true;
            }
        }
        if(str_contains($input, '==')) {
            $cmp = $this->parse($input, '==');
            if (count($cmp) > 1) {
                $first = $this->evaluate(array_shift($cmp));
                foreach ($cmp as $item) if ($this->evaluate($item) != $first) return false;
                return true;
            }
        }
        if(str_contains($input, '!=')) {
            $cmp = $this->parse($input, '!=');
            if (count($cmp) > 1) {
                $first = $this->evaluate(array_shift($cmp));
                foreach ($cmp as $item) if ($this->evaluate($item) == $first) return false;
                return true;
            }
        }
        if(str_contains($input, '>=')) {
            $cmp = $this->parse($input, '>=');
            if (count($cmp) > 1) {
                $first = $this->evaluate(array_shift($cmp));
                foreach ($cmp as $item) if ($this->evaluate($item) > $first) return false;
                return true;
            }
        }
        if(str_contains($input, '<=')) {
            $cmp = $this->parse($input, '<=');
            if (count($cmp) > 1) {
                $first = $this->evaluate(array_shift($cmp));
                foreach ($cmp as $item) if ($this->evaluate($item) < $first) return false;
                return true;
            }
        }
        if(str_contains($input, '>')) {
            $cmp = $this->parse($input, '>');
            if (count($cmp) > 1) {
                $first = $this->evaluate(array_shift($cmp));
                foreach ($cmp as $item) if ($this->evaluate($item) >= $first) return false;
                return true;
            }
        }
        if(str_contains($input, '<')) {
            $cmp = $this->parse($input, '<');
            if (count($cmp) > 1) {
                $first = $this->evaluate(array_shift($cmp));
                foreach ($cmp as $item) if ($this->evaluate($item) <= $first) return false;
                return true;
            }
        }

        // arithmetic expressions
        if(str_contains($input, '+')) {
            $add = $this->parse($input, '+');
            if (count($add) > 1) {
                $result = 0;
                foreach ($add as $item) $result += $this->evaluate($item);
                return $result;
            }
        }
        if(str_contains($input, '-')) {
            $sub = $this->parse($input, '-');
            if (count($sub) > 1) {
                $result = $this->evaluate(array_shift($sub));
                foreach ($sub as $item) $result -= $this->evaluate($item);
                return $result;
            }
        }
        if(str_contains($input, '**')) {
            $pow = $this->parse($input, '**');
            if (count($pow) > 1) {
                $result = $this->evaluate(array_shift($pow));
                foreach ($pow as $item) $result **= $this->evaluate($item);
                return $result;
            }
        }
        if(str_contains($input, '*')) {
            $mul = $this->parse($input, '*');
            if (count($mul) > 1) {
                $result = 1;
                foreach ($mul as $item) $result *= $this->evaluate($item);
                return $result;
            }
        }
        if(str_contains($input, '/')) {
            $div = $this->parse($input, '/');
            if (count($div) > 1) {
                $result = $this->evaluate(array_shift($div));
                foreach ($div as $item) $result /= $this->evaluate($item);
                return $result;
            }
        }
        if(str_contains($input, '%')) {
            $mod = $this->parse($input, '%');
            if (count($mod) > 1) {
                $result = $this->evaluate(array_shift($mod));
                foreach ($mod as $item) $result %= $this->evaluate($item);
                return $result;
            }
        }

        // string concatenation expression
        if(str_contains($input, '~')) {
            $concat = $this->parse($input, '~');
            if (count($concat) > 1) {
                $result = '';
                foreach ($concat as $item) $result .= $this->evaluate($item);
                return $result;
            }
        }

        // check if a string
        if(str_starts_with($input, "'")) {
            if(!str_ends_with($input, "'")) throw new Exception('unexpected end of string');

            // convert to a string that can be json decoded
            $input = str_replace('"', '\\"', $input); // escape all double quotes
            $input = str_replace("\\'", "[%{SINGLE_QUOTE}%]", $input); // to be unescaped
            $input = str_replace("'", '"', $input); // for surrounding
            $input = str_replace("[%{SINGLE_QUOTE}%]", "'", $input); // restore single quotes
        }

        if(str_starts_with($input, '"')){
            $output = json_decode($input);
            if(json_last_error() !== 0) throw new Exception('unexpected end of string');
            return $output;
        }

        // check if expression negated
        if(str_starts_with($input, '!'))
            return !$this->evaluate(substr($input, 1));

        // if the expression is just wrapped in brackets, remove them
        if(str_starts_with($input, '(')){
            // check if the input ends with a trailing round bracket
            if(!str_ends_with($input, ')'))
                throw new Exception("expected closing round bracket");

            return $this->evaluate(substr($input, 1, -1));
        }

        // check if the expression is an array
        if(str_starts_with($input, '[')) {
            // check if the input ends with a trailing block bracket and get rid of both brackets
            if(!str_ends_with($input, ']'))
                throw new Exception("expected closing block bracket");

            $input = substr($input, 1, -1);
            return $this->evaluateList($input);
        }

        // check if the expression is a callable call
        // bracket will only be in expression if it is a callable call, all other cases are handled above
        if(str_contains($input, '(')) {
            // parse the input delimiting by opening bracket
            $parts = $this->parse($input, '(', false);

            // parameter string is in last part, without trailing and leading brackets
            $params = substr(array_pop($parts), 1, -1);
            $before = implode('', $parts);

            // evaluate callable
            $callable = $this->evaluate($before);
            if(!is_callable($callable)) throw new Exception("`$before` is not callable");

            // evaluate each parameter
            $params = $this->evaluateList($params);

            return $callable(...$params);
        }

        // check if the expression is a nested variable
        if(str_contains($input, '.')) {
            $parts = $this->parse($input, '.');
            $after = array_pop($parts);
            $before = implode('', $parts);

            $var = $this->evaluate($before);
            return match(true){
                is_object($var) && method_exists($var, $after) => $var->$after(...),
                is_object($var) => $var->$after,
                is_array($var) && array_key_exists($after, $var) => $var[$after],
                default => throw new Exception("element `$after` not found in `$before`"),
            };
        }

        // finally, if expression isn't anything of above, it must be a variable
        if(!array_key_exists($input, $this->vars)) throw new Exception("variable `$input` not defined");
        return $this->vars[$input];
    }

    protected function evaluateList(string $params): array
    {
        $params = $this->parse($params, ',');
        foreach($params as $param) {
            if(!str_starts_with($param, '...')) {
                $output[] = $this->evaluate($param);
                continue;
            }

            $packed = $this->evaluate(substr($param, 3));
            if(!is_array($packed)) throw new Exception("can't unpack `$param` - not an array");
            foreach($packed as $item) $output[] = $item;
        }

        return $output ?? [];
    }

    protected function parse(string $input, string $delimiter, bool $omitDelimiter = true): array
    {
        $inQuotes = false;
        $depthRound = $depthBlock = $depthCurly = 0;
        $outputIndex = 0;

        for($i = 0; $i < strlen($input); $i++) {
            $write = true;
            $char = $input[$i];
            $charLeft = $i > 0 ? $input[$i - 1] : null;

            if($char === '"' && $charLeft !== '\\') {
                if($inQuotes === '"')   $inQuotes = false;
                else if(!$inQuotes)     $inQuotes = '"';
            }
            if($char === "'" && $charLeft !== '\\') {
                if($inQuotes === "'")   $inQuotes = false;
                else if(!$inQuotes)     $inQuotes = "'";
            }

            if(!$inQuotes) {
                // skip whitespaces, tabs and newlines
                if (in_array($char, [' ', "\n", "\r", "\t"])) continue;

                ($char === ')' && $depthRound === 0) and throw new Exception('unexpected `)`');
                ($char === ']' && $depthBlock === 0) and throw new Exception('unexpected `]`');
                ($char === '}' && $depthCurly === 0) and throw new Exception('unexpected `}`');

                if ($depthRound === 0 && $depthBlock === 0 && $depthCurly === 0) {
                    $match = true;

                    for($j = 0; $j < strlen($delimiter); $j++)
                        if($input[$i + $j] !== $delimiter[$j]) {
                            $match = false;
                            break;
                        }

                    if($match){
                        $outputIndex++;
                        $write = !$omitDelimiter;
                        if($omitDelimiter) $i += strlen($delimiter) - 1;
                    }
                }

                match($char) {
                    '(' => $depthRound++, ')' => $depthRound--,
                    '[' => $depthBlock++, ']' => $depthBlock--,
                    '{' => $depthCurly++, '}' => $depthCurly--,
                    default => null,
                };
            }

            if($write) {
                $output[$outputIndex] ??= '';
                $output[$outputIndex] .= $char;
            }
        }

        if($inQuotes) throw new Exception("expected closing `$inQuotes`");
        if($depthRound > 0) throw new Exception("round bracket not closed");
        if($depthBlock > 0) throw new Exception("square bracket not closed");
        if($depthCurly > 0) throw new Exception("curly bracket not closed");

        return $output ?? [];
    }

}