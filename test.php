<?php

require_once __DIR__ . '/vendor/autoload.php';

class Test {
    public string $name = 'John';

    public function method(int $a): int
    {
        return $a * 100;
    }
}
$testObject = new Test();

$vars = [
    'text' => 'this is some string',
    'number' => 123,
    'positive' => true,
    'negative' => false,
    'array' => ['first', 'second', 'third'],
    'hash' => ['first' => 'same', 'second' => 'different', 'third' => 'same'],
    'nested' => ['closure' => fn() => fn(string $a): string => "you said: $a"],
    'object' => $testObject,
    'empty' => '',
    'custom_implode' => fn(string $a, string ...$b): string => implode($a, $b),
    'reverse' => strrev(...),
    'lowercase' => strtolower(...),
    'explode' => explode(...),
];

$smpl = new Le\SMPLang\SMPLang($vars);

if(class_exists(Symfony\Component\ExpressionLanguage\ExpressionLanguage::class)) {
    $sel = new Symfony\Component\ExpressionLanguage\ExpressionLanguage();
    $sel->register('reverse', fn() => null, fn($args, $str) => strrev($str));
    $sel->register('lowercase', fn() => null, fn($args, $str) => strtolower($str));
}


$tests = [
    /*
    'test_name' => [
        'expression',
        expected result,

        // if the expression is supposed to throw an exception:
        'fail' => true,

        // if the syntax is different for Symfony Expression Language:
        'sel' => 'sel-specific syntax',
        // or if Symfony ExpressionLanguage test should be skipped:
        'sel' => null,
    ]
    */
    'empty_expression' => [
        '',
        null,

        'sel' => null // sel throws an exception for empty expression
    ],
    'basic_concat' => [
        '"message: " ~ text',
        'message: this is some string'
    ],
    'basic_arithmetics' => [
        '(number - 10 * 4 / 2 - 3) % 10',
        0
    ],
    'arithmetics_comparisons' => [
        '(100 + number * 1 <= 200 || number < -1) === negative',
        true
    ],
    'boolean_expression1' => [
        'true && false && false || true && true',
        true && false && false || true && true
    ],
    'boolean_expression2' => [
        'true && false && (false || true) && true',
        true && false && (false || true) && true
    ],
    'closure_from_nested_closure' => [
        'nested.closure()("hello")',
        'you said: hello',

        'sel' => null // sel doesn't support closures in arrays
    ],
    'object_property' => [
        "object.name == 'John'",
        true
    ],
    'object_method' => [
        "object.method(number+7)",
        13000
    ],
    'array_unpacking' => [
        '["prepended", ...array, "appended"]',
        ['prepended', 'first', 'second', 'third', 'appended'],

        'sel' => null // sel doesn't support unpacking
    ],
    'parameter_unpacking' => [
        'custom_implode(" ", "zeroth", ...array, "fourth")',
        'zeroth first second third fourth',

        'sel' => null // sel doesn't support unpacking
    ],
    'function_return_value_unpacking' => [
        'custom_implode("! ", ...explode(" ", text)) ~ "!"',
        'this! is! some! string!',

        'sel' => null // sel doesn't support unpacking
    ],
    'ternary_and_concat' => [
        '(!positive ? "" : "not ") ~ "nice"',
        'not nice'
    ],
    'short_ternary1' => [
        'hash.first == hash.second ? "success"',
        null,

        'sel' => 'hash["first"] == hash["second"] ? "success"'
    ],
    'short_ternary2' => [
        '(hash.third ?: "they were different") === hash.first',
        true,

        'sel' => '(hash["third"] ?: "they were different") === hash["first"]',
    ],
    'complex_comparisons_logic' => [
        'hash.first == "same" && (hash.second == "different" || hash.second === null) && (hash.third == "same" || hash.third == "whatever")',
        true,

        'sel' => 'hash["first"] == "same" && (hash["second"] == "different" || hash.second === null) && (hash["third"] == "same" || hash["third"] == "whatever")',
    ],
    'negating_and_exact_comparison' => [
        '[empty == !positive, empty === negative]',
        [true, false]
    ],
    'concat_in_closure_call' => [
        'reverse(array.0 ~ " don\'t " ~ number)',
        "321 t'nod tsrif",

        'sel' => 'reverse(array[0] ~ " don\'t " ~ number)'
    ],
    'closure_call_in_closure_call_with_concat' => [
        'reverse(lowercase("StArT " ~ text))',
        "gnirts emos si siht trats",
    ],
    'string_containing_language_operators' => [ // SMPLs biggest weakenss perhaps?
        "lowercase('( { [ && || == === !== !=== ? ?: + - * / %')",
        '( { [ && || == === !== !=== ? ?: + - * / %'
    ],
    'concat_and_arithmetic' => [
        '1 + 2 ~ 5 + 3',
        '38'
    ],
    'unclosed_bracket' => [
        '(3 * 10',
        'fail' => true
    ],
    'nonexistent_variable' => [
        'nonexistent',
        'fail' => true
    ],
];

$passCount = $win = $winSel = 0;
foreach($tests as $index => $test) {
    $pass = $passSel = $caught = $caughtSel = false;

    $test[0] ??= $index;
    $test[1] ??= null;
    $test['fail'] ??= false;

    // sel-specific syntax expression is copied from smpl expression
    if(!array_key_exists('sel', $test)) $test['sel'] = $test[0];

    echo "TEST $index:\n";

    try {
        $start = hrtime(true);
        $result = $smpl->evaluate($test[0]);
        $end = hrtime(true);
    }
    catch (Le\SMPLang\Exception $e) {
        $end = hrtime(true);
        $caught = $e->getMessage();
    }

    if(isset($sel) && $test['sel'] !== null) {
        try {
            $startSel = hrtime(true);
            $resultSel = $sel->evaluate($test['sel'], $vars);
            $endSel = hrtime(true);
        }
        catch (Exception $e) {
            $endSel = hrtime(true);
            $caughtSel = $e->getMessage();
        }
    }

    echo "  SMPL: ";
    if (($test['fail'] && !$caught) || (!$test['fail'] && $caught) || (!$test['fail'] && !$caught && $result !== $test[1])) {
        echo "\033[31mFAILED\033[0m\n";
        echo "    input: $test[0]\n";

        if($test['fail']) echo "    expected: [thrown exception]\n";
        else echo "    expected: " . var_export($test[1], true) . "\n";

        if(!$caught) echo "    got: " . var_export($result, true) . "\n";
        else echo "    error: $caught\n";
    } else {
        $pass = true;
        $passCount++;
        $time = ($end - $start) / 1000000;
        echo "\033[32mOK ({$time}ms)\033[0m\n";
    }

    if(isset($sel)) {
        echo "  SEL: ";
        if ($test['sel'] === null) {
            echo "\033[34mSKIPPED\033[0m\n";
        } else {
            if (($test['fail'] && !$caughtSel) || (!$test['fail'] && $caughtSel) || (!$test['fail'] && !$caughtSel && $resultSel !== $test[1])) {
                echo "\033[31mFAILED\033[0m\n";
                echo "    input: {$test['sel']}\n";

                if ($test['fail']) echo "    expected: [thrown exception]\n";
                else echo "    expected: " . var_export($test[1], true) . "\n";
                if (!$caughtSel) echo "    got: " . var_export($resultSel, true) . "\n";
                else echo "    error: $caughtSel\n";
            } else {
                $passSel = true;
                $timeSel = ($endSel - $startSel) / 1000000;
                echo "\033[32mOK ({$timeSel}ms)\033[0m\n";
            }
        }
    }

    if($pass && $passSel) {
        if($timeSel <= $time) $winSel++; else $win++;
        echo "faster: " . ($timeSel <= $time ? "\033[31mEL\033[0m" : "\033[32mSMPL\033[0m") . " by " . abs($time - $timeSel) . "ms\n";
    }

    echo "\n";
}

echo "All tests completed, results:\n";

if(isset($sel)) {
    $totalWins = $win + $winSel;
    echo "  SMPL faster: $win / $totalWins\n";
}
echo "  SMPL tests passed: $passCount / " . count($tests) . "\n";