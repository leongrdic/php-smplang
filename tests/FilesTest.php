<?php

namespace Le\SMPLang\Tests;

use Le\SMPLang\SMPLang;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

test('is_file', function () {
    $smpl = new SMPLang([
        'path' => __FILE__,
    ]);

    assertTrue($smpl->evaluate('is_file(path)'));
});

test('is_dir', function () {
    $smpl = new SMPLang([
        'path' => __DIR__,
    ]);

    assertTrue($smpl->evaluate('is_dir(path)'));
});

test('file_exists', function () {
    $smpl = new SMPLang([
        'path' => __FILE__,
    ]);

    assertTrue($smpl->evaluate('file_exists(path)'));
});

test('file_get_contents', function () {
    $smpl = new SMPLang([
        'path' => __FILE__,
    ]);

    assertEquals($smpl->evaluate('file_get_contents(path)'), file_get_contents(__FILE__));
});
