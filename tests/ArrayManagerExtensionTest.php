<?php

namespace Le\SMPLang\Tests;

use Le\SMPLang\SMPLang;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

test('array_key_exists', function () {
    $smpl = new SMPLang([
        'array' => ['hello' => 'world'],
    ]);

    assertTrue($smpl->evaluate('array_key_exists("hello", array)'));
    assertFalse($smpl->evaluate('array_key_exists("world", array)'));
});

test('array_map', function () {
    $smpl = new SMPLang([
        'array' => ['foo', 'bar', 'baz'],
        'map' => fn ($item) => "hello, $item!",
    ]);

    assertEquals(
        $smpl->evaluate('array_map(map, array)'),
        ['hello, foo!', 'hello, bar!', 'hello, baz!']
    );
});

test('array_sum', function () {
    $smpl = new SMPLang([
        'array' => [1, 2, 3],
    ]);

    assertEquals($smpl->evaluate('array_sum(array)'), 6);
});

test('array_keys', function () {
    $smpl = new SMPLang([
        'array' => ['a' => 1, 'b' => 2, 'c' => 3],
    ]);

    assertEquals($smpl->evaluate('array_keys(array)'), ['a', 'b' ,'c']);
});

test('array_merge', function () {
    $smpl = new SMPLang([
        'first' => [1, 2, 'duplicate' => 3],
        'second' => [2, 4, 'duplicate' => 6],
    ]);

    assertEquals(
        $smpl->evaluate('array_merge(first, second)'),
        [0 => 1, 1 => 2, 'duplicate' => 6, 2 => 2, 3 => 4]
    );
});

test('array_filter', function () {
    $smpl = new SMPLang([
        'array' => [1, 2, 3, 4, 5],
        'filter' => fn ($item) => $item > 3,
    ]);

    assertEquals($smpl->evaluate('array_filter(array, filter)'), [3 => 4, 4 => 5]);
});

test('array_search', function () {
    $smpl = new SMPLang([
        'array' => [1, 2, 3, 4, 5],
    ]);

    assertEquals($smpl->evaluate('array_search(3, array)'), 2);
    assertFalse($smpl->evaluate('array_search(6, array)'));
});

test('array_is_list', function () {
    $smpl = new SMPLang([
        'array1' => [1, 2, 3, 4, 5],
        'array2' => [1, 'a' => 2, 3, 4, 5],
    ]);

    assertTrue($smpl->evaluate('array_is_list(array1)'));
    assertFalse($smpl->evaluate('array_is_list(array2)'));
});
