<?php

namespace Le\SMPLang\Tests;

use Le\SMPLang\SMPLang;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

test('isset', function () {
    $smpl = new SMPLang([
        'hello' => 'world',
    ]);

    assertTrue($smpl->evaluate('isset("hello")'));
    assertFalse($smpl->evaluate('isset("world")'));
});

test('unset', function () {
    $smpl = new SMPLang([
        'hello' => 'world',
    ]);

    $smpl->evaluate('unset("world")');
    $smpl->evaluate('unset("hello")');
    assertFalse($smpl->evaluate('isset("hello")'));
});
