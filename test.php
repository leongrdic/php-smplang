<?php

require_once 'vendor/autoload.php';

class Test {
    public string $name = 'John';

    public function method(int $a)
    {
        return $a * 100;
    }
}
$testObject = new Test();
$testClosure = fn(string $a): string => "you said: $a";

$smpl = new Le\SMPLang\SMPLang([
    'string' => 'this is some string',
    'number' => 123,
    'positive' => true,
    'negative' => false,
    'array' => ['first', 'second', 'third'],
    'hash' => ['first' => 'value1', 'second' => 'value2', 'third' => 'value3'],
    'nested' => ['closure' => fn() => $testClosure],
    'object' => $testObject,
    'concat' => fn(string ...$a): string => implode('', $a),
]);

$tests = [
    [
        '',
        null
    ],
    [
        '"message: " ~ string',
        'message: this is some string'
    ],
    [
        '(number - 10 * 4 / 2 - 3) % 10',
        0
    ],
    [
        '(100 + number * 1 <= 200 || number < -1) === negative',
        true
    ],
    [
        'true && false && false || true && true',
        true && false && false || true && true
    ],
    [
        'true && false && (false || true) && true',
        true && false && (false || true) && true
    ],
    [
        'true && false && false || true && true',
        true && false && false || true && true
    ],
    [
        'nested.closure()("hello")',
        'you said: hello'
    ],
    [
        "object.name == 'John'",
        true
    ],
    [
        "object.method(10)",
        1000
    ],
    [
        '["prepended", ...array, "appended"]',
        ['prepended', 'first', 'second', 'third', 'appended']
    ],
    [
        'concat(...array, "fourth")',
        'firstsecondthirdfourth'
    ],
    // TERNARY
];

foreach($tests as $index => $test) {
    echo "TEST $index: ";
    try {
        $result = $smpl->evaluate($test[0]);
    }
    catch (Le\SMPLang\Exception $e) {
        echo "\033[31mFAILED\033[0m ({$e->getMessage()}\n";
        continue;
    }

    if($result === $test[1]){
        echo "\033[32mPASSED\033[0m\n";
        continue;
    }

    echo "\033[31mFAILED\033[0m\n";
    echo "  input: $test[0]\n";
    echo "  expected: " . var_export($test[1], true) . "\n";
    echo "  got: " . var_export($result, true) . "\n";

    // reset color
    echo "\033[0m";
}