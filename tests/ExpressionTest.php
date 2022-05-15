<?php

namespace Le\SMPLang\Tests;

use Le\SMPLang\SMPLang;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

it('empty expression', function () {
    $smpl = new SMPLang();
    assertNull($smpl->evaluate(''));
    assertNull($smpl->evaluate('null'));
    assertNull($smpl->evaluate('NULL'));
    assertNull($smpl->evaluate('NulL'));
});

it('deep in hash', function () {
    $smpl = new SMPLang([
        'hash' => ['first' => 'same', 'second' => 'different', 'third' => 'same', 'another' => ['deep' => 'text']],
    ]);

    assertEquals($smpl->evaluate('hash.another.deep'), 'text');
});

it('basic concat', function () {
    $smpl = new SMPLang([
        'text' => 'this is some string',
    ]);

    assertEquals($smpl->evaluate('"message: " ~ text'), 'message: this is some string');
});

it('basic arithmetics', function () {
    $smpl = new SMPLang([
        'number' => 123,
    ]);

    assertEquals($smpl->evaluate('(number - 10 * 4 / 2 - 3) % 10'), 0);

    $smpl = new SMPLang([
        'number' => '000123.0',
    ]);

    assertEquals($smpl->evaluate('(number - 10.0 * 04.0 / 2.0 - 3.0) % 10.0'), 0);
});

it('arithmetics comparisons', function () {
    $smpl = new SMPLang([
        'number' => 123,
        'negative' => false,
    ]);

    assertTrue($smpl->evaluate('(100 + number * 1 <= 200 || number < -1) === negative'));
});

it('boolean expressions', function () {
    $smpl = new SMPLang();
    assertTrue($smpl->evaluate('true && false && false || true && true'));
    assertFalse($smpl->evaluate('true && false && (false || true) && true'));
});

it('closure from nested closure', function () {
    $smpl = new SMPLang([
        'nested' => ['closure' => fn() => fn(string $a): string => "you said: $a"],
    ]);

    assertEquals($smpl->evaluate('nested.closure()("hello")'), 'you said: hello');
});

it('object property', function () {
    $smpl = new SMPLang([
        'object' => new class {
            public string $name = 'John';
        }
    ]);

    assertTrue($smpl->evaluate("object.name == 'John'"));
    assertTrue($smpl->evaluate('object.name == "John"'));
    assertEquals($smpl->evaluate('object.name'), 'John');
});

it('object method', function () {
    $smpl = new SMPLang([
        'object' => new class {
            public function method(int $number): int
            {
                return $number * 100;
            }
        }
    ]);

    assertEquals($smpl->evaluate("object.method(10)"), 1000);
});

// @todo add tests
