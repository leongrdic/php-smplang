<?php

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

require_once 'vendor/autoload.php';

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
];

$smpl = new Le\SMPLang\SMPLang($vars);

$el = new ExpressionLanguage();
$el->register('reverse', fn() => null, fn($args, $str) => strrev($str));
$el->register('lowercase', fn() => null, fn($args, $str) => strtolower($str));


$tests = [
    'empty_expression' => [
        '',
        null,

        'el' => null // el throws an exception for empty expression
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

        'el' => null // el doesn't support closures in arrays
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

        'el' => null // el doesn't support unpacking
    ],
    'parameter_unpacking' => [
        'custom_implode(" ", "zeroth", ...array, "fourth")',
        'zeroth first second third fourth',

        'el' => null // el doesn't support unpacking
    ],
    'ternary_and_concat' => [
        '(!positive ? "" : "not ") ~ "nice"',
        'not nice'
    ],
    'short_ternary1' => [
        'hash.first == hash.second ? "success"',
        null,

        'el' => 'hash["first"] == hash["second"] ? "success"'
    ],
    'short_ternary2' => [
        '(hash.third ?: "they were different") === hash.first',
        true,

        'el' => '(hash["third"] ?: "they were different") === hash["first"]',
    ],
    'complex_comparisons_logic' => [
        'hash.first == "same" && (hash.second == "different" || hash.second === null) && (hash.third == "same" || hash.third == "whatever")',
        true,

        'el' => 'hash["first"] == "same" && (hash["second"] == "different" || hash.second === null) && (hash["third"] == "same" || hash["third"] == "whatever")',
    ],
    'negating_and_exact_comparison' => [
        '[empty == !positive, empty === negative]',
        [true, false]
    ],
    'concat_in_closure_call' => [
        'reverse(array.0 ~ " don\'t " ~ number)',
        "321 t'nod tsrif",

        'el' => 'reverse(array[0] ~ " don\'t " ~ number)'
    ],
    'closure_call_in_closure_call_with_concat' => [
        'reverse(lowercase("StArT " ~ text))',
        "gnirts emos si siht trats",
    ],
    "string_containing_language_operators" => [ // SMPLs biggest weakenss perhaps?
        "lowercase('( { [ && || == === !== !=== ? ?: + - * / %')",
        '( { [ && || == === !== !=== ? ?: + - * / %'
    ]
];

$win = $winEl = 0;
foreach($tests as $index => $test) {
    $fail = $failEl = false;

    if(!array_key_exists('el', $test)) $test['el'] = $test[0];
    if(!array_key_exists('el_expect', $test)) $test['el_expect'] = $test[1];

    echo "TEST $index:\n";

    try {
        $start = hrtime(true);
        $result = $smpl->evaluate($test[0]);
        $end = hrtime(true);

        if($test['el'] !== null) {
            $startEl = hrtime(true);
            $resultEl = $el->evaluate($test['el'], $vars);
            $endEl = hrtime(true);
        }else{
            $failEl = true;
        }
    }
    catch (Le\SMPLang\Exception $e) { $fail = $e->getMessage(); }
    catch (Exception $e) { $failEl = $e->getMessage(); }

    echo "  SMPL: ";
    if ($fail || $result !== $test[1]) {
        echo "\033[31mFAILED\033[0m\n";
        echo "    input: $test[0]\n";
        echo "    expected: " . var_export($test[1], true) . "\n";
        if(!$fail) echo "    got: " . var_export($result, true) . "\n";
        else echo "    error: $fail\n";
    } else {
        $time = ($end - $start) / 1000000;
        echo "\033[32mOK ({$time}ms)\033[0m\n";
    }

    echo "  EL: ";
    if($test['el'] === null) {
        echo "\033[34mSKIPPED\033[0m\n";
    } else if($failEl || $resultEl !== $test['el_expect']) {
        echo "\033[31mFAILED\033[0m\n";
        echo "    input: {$test['el']}\n";
        echo "    expected: " . var_export($test['el_expect'], true) . "\n";
        if(!$failEl) echo "    got: " . var_export($resultEl, true) . "\n";
        else echo "    error: $failEl\n";
    } else {
        $timeEl = ($endEl - $startEl) / 1000000;
        echo "\033[32mOK ({$timeEl}ms)\033[0m\n";
    }

    if(!$fail && !$failEl) {
        if($timeEl <= $time) $winEl++; else $win++;
        echo "  faster: " . ($timeEl <= $time ? "\033[31mEL\033[0m" : "\033[32mSMP\033[0m") . " by " . abs($time - $timeEl) . "\n";
    }

    echo "\n";
}

echo "SMPL faster: $win\n";
echo "EL faster: $winEl\n";