<?php

namespace Le\SMPLang\Tests;

use Le\SMPLang\SMPLang;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

test('empty expression', function () {
    $smpl = new SMPLang();
    assertNull($smpl->evaluate(''));
    assertNull($smpl->evaluate('null'));
    assertNull($smpl->evaluate('NULL'));
    assertNull($smpl->evaluate('NulL'));
});

test('string parsing', function () {
    $smpl = new SMPLang();

    assertEquals(
        $smpl->evaluate('" \' \" ` ) ( ] [ } { , "'),
        " ' \" ` ) ( ] [ } { , "
    );

    assertEquals(
        $smpl->evaluate("' \' \" ` ) ( ] [ } { , '"),
        " ' \" ` ) ( ] [ } { , "
    );

    assertEquals(
        $smpl->evaluate("` ' \" \` ) ( ] [ } { , `"),
        " ' \" ` ) ( ] [ } { , "
    );
});

test('string concat', function () {
    $smpl = new SMPLang([
        'text' => 'this is some string',
    ]);

    assertEquals($smpl->evaluate('"foo" ~ \'bar\' ~ `baz`'), 'foobarbaz');
    assertEquals($smpl->evaluate('"foo"~\'bar\'~`baz`'), 'foobarbaz');

    assertEquals($smpl->evaluate('"foo" ~ 1 + 2 ~ `baz`'), 'foo3baz');
    assertEquals($smpl->evaluate('"foo"~1+2~`baz`'), 'foo3baz');

    assertEquals($smpl->evaluate('"message: " ~ text'), 'message: this is some string');
    assertEquals($smpl->evaluate('"message: "~text'), 'message: this is some string');
    assertEquals($smpl->evaluate("'message: ' ~ text"), 'message: this is some string');
    assertEquals($smpl->evaluate("'message: '~text"), 'message: this is some string');
    assertEquals($smpl->evaluate('`message: ` ~ text'), 'message: this is some string');
    assertEquals($smpl->evaluate('`message: `~text'), 'message: this is some string');
});

test('number parsing', function () {
    $smpl = new SMPLang();

    assertEquals($smpl->evaluate('1'), 1);
    assertEquals($smpl->evaluate('-1'), -1);
    assertEquals($smpl->evaluate('1.0'), 1.0);
    assertEquals($smpl->evaluate('-1.0'), -1.0);
    assertEquals($smpl->evaluate('12.34'), 12.34);
    assertEquals($smpl->evaluate('-12.34'), -12.34);
});

test('basic arithmetics', function () {
    $smpl = new SMPLang();

    assertEquals($smpl->evaluate('1 + 2'), 3);
    assertEquals($smpl->evaluate('1 - 2'), -1);
    assertEquals($smpl->evaluate('2 * 3'), 6);
    assertEquals($smpl->evaluate('6 / 3'), 2);
    assertEquals($smpl->evaluate('13 % 5'), 3);
    assertEquals($smpl->evaluate('2 ** 3'), 8);

    assertEquals($smpl->evaluate('2 * 2 + 2'), 6);
    assertEquals($smpl->evaluate('2 * (2 + 2)'), 8);

    assertEquals($smpl->evaluate('4 / 2 - 1'), 1);
    assertEquals($smpl->evaluate('4 / (2 - 1)'), 4);

    $smpl = new SMPLang([
        'number' => 123,
    ]);

    assertEquals($smpl->evaluate('(number - 10 * 4 / 2 - 3) % 10'), 0);
    assertEquals($smpl->evaluate('(number-10*4/2-3)%10'), 0);

    $smpl = new SMPLang([
        'number' => '000123.0',
    ]);

    assertEquals($smpl->evaluate('(number - 10.0 * 04.0 / 2.0 - 3.0) % 10.0'), 0);
    assertEquals($smpl->evaluate('(number-10.0*04.0/2.0-3.0)%10.0'), 0);
});

test('arithmetics comparisons', function () {
    $smpl = new SMPLang([
        'number' => 123,
        'negative' => false,
    ]);

    assertTrue($smpl->evaluate('(100 + number * 1 <= 200 || number < -1) === negative'));
    assertTrue($smpl->evaluate('(100+number*1<=200||number<-1)===negative'));

    assertFalse($smpl->evaluate('0 > 1'));
    assertTrue($smpl->evaluate('0 < 1'));

    assertFalse($smpl->evaluate('1 < 1'));
    assertFalse($smpl->evaluate('1 > 1'));

    assertTrue($smpl->evaluate('1 <= 1'));
    assertTrue($smpl->evaluate('1 >= 1'));

    assertTrue($smpl->evaluate('0 <= 1'));
    assertTrue($smpl->evaluate('1 >= 0'));

    assertFalse($smpl->evaluate('1 <= 0'));
    assertFalse($smpl->evaluate('0 >= 1'));
});

test('boolean expressions', function () {
    $smpl = new SMPLang();

    assertTrue($smpl->evaluate('true'));
    assertTrue($smpl->evaluate('!false'));
    assertFalse($smpl->evaluate('!true'));
    assertFalse($smpl->evaluate('false'));

    assertTrue($smpl->evaluate('true && false && false || true && true'));
    assertTrue($smpl->evaluate('!false && !true && false || !false && true'));
    assertTrue($smpl->evaluate('true&&false&&false||true&&true'));
    assertTrue($smpl->evaluate('((true)&&false&&false||true&&true)'));
    assertFalse($smpl->evaluate('!((true)&&false&&false||true&&true)'));

    assertFalse($smpl->evaluate('true && false && (false || true) && true'));
    assertFalse($smpl->evaluate('!false && !true && (false || !false) && true'));
    assertFalse($smpl->evaluate('true&&false&&(false||true)&&true'));
    assertFalse($smpl->evaluate('((true)&&false&&(false||true)&&true)'));
    assertTrue($smpl->evaluate('!((true)&&false&&(false||true)&&true)'));
});

test('array and object definitions', function () {
    $smpl = new SMPLang([
        'key' => 'third'
    ]);

    assertEquals($smpl->evaluate('[]'), []);
    assertEquals($smpl->evaluate('[ "one", "two", 23, ]'), ["one", "two", 23]);
    assertEquals($smpl->evaluate('["first": "one", "second": "two", key: 23]'), ["first" => "one", "second" => "two", "third" => 23]);
    assertEquals($smpl->evaluate('["array": ["foo", `bar`]]'), ["array" => ["foo", "bar"]]);
    assertEquals($smpl->evaluate('["array": {\'foo\', "bar",},]'), ["array" => (object) ["foo", "bar"]]);

    assertEquals($smpl->evaluate('{}'), (object) []);
    assertEquals($smpl->evaluate('{ "one", "two", 23, }'), (object) ["one", "two", 23]);
    assertEquals($smpl->evaluate('{first: "one", second: "two", key: 23}'), (object) ["first" => "one", "second" => "two", "key" => 23]);
    assertEquals($smpl->evaluate('{array: ["foo", `bar`]}'), (object) ["array" => ["foo", "bar"]]);
    assertEquals($smpl->evaluate('{array: {\'foo\', "bar",},}'), (object) ["array" => (object) ["foo", "bar"]]);
});

test('access array elements', function () {
    $smpl = new SMPLang([
        'hash' => ['first' => 'same', 'second' => 'different', 'third' => 'same', 'another' => ['deep' => 'text']],
        'key' => 'first'
    ]);

    assertEquals($smpl->evaluate('hash.second'), 'different');
    assertEquals($smpl->evaluate('hash["second"]'), 'different');
    assertEquals($smpl->evaluate('hash[key]'), 'same');
    assertEquals($smpl->evaluate('hash.another'), ['deep' => 'text']);

    assertEquals($smpl->evaluate('hash.another.deep'), 'text');
    assertEquals($smpl->evaluate('hash["another"]["deep"]'), 'text');
    assertEquals($smpl->evaluate("hash['another']['deep']"), 'text');
    assertEquals($smpl->evaluate('hash[`another`][`deep`]'), 'text');
});

test('equal comparisons', function () {
    $smpl = new SMPLang();

    assertFalse($smpl->evaluate('true == false'));
    assertFalse($smpl->evaluate('true==false'));
    assertFalse($smpl->evaluate('true === false'));
    assertFalse($smpl->evaluate('true===false'));

    assertTrue($smpl->evaluate('true == true'));
    assertTrue($smpl->evaluate('true === true'));

    assertTrue($smpl->evaluate('false == false'));
    assertTrue($smpl->evaluate('false === false'));

    assertTrue($smpl->evaluate('"" == false'));
    assertFalse($smpl->evaluate('"" === false'));
    assertTrue($smpl->evaluate('0 == false'));
    assertFalse($smpl->evaluate('0 === false'));

    assertTrue($smpl->evaluate('"sth" == true'));
    assertFalse($smpl->evaluate('"sth" === true'));
    assertTrue($smpl->evaluate('1 == true'));
    assertFalse($smpl->evaluate('1 === true'));
});

test('closure calls', function () {
    $smpl = new SMPLang([
        'param' => ['hello'],
        'params' => ['hello', 'world'],
        'named_params' => ['a' => 'hello', 'b' => 'world'],
        'simple' => fn ($a, $b) => $a . '-' . $b,
        'nested' => ['closure' => fn ($a) => fn (string $b): string => "you said: $a then $b"],
        'returns_array' => fn ($value) => ['key' => $value],
    ]);

    assertEquals($smpl->evaluate('simple("hello", `world`)'), 'hello-world');
    assertEquals($smpl->evaluate('simple(a: "hello", b: `world`)'), 'hello-world');
    assertEquals($smpl->evaluate('simple(...params)'), 'hello-world');
    assertEquals($smpl->evaluate('simple(...named_params)'), 'hello-world');
    assertEquals($smpl->evaluate('simple(...param, \'world\')'), 'hello-world');

    assertEquals($smpl->evaluate('nested.closure("hello")(`world`)'), 'you said: hello then world');
    assertEquals($smpl->evaluate('nested[`closure`]("hello")(`world`)'), 'you said: hello then world');
    assertEquals($smpl->evaluate('returns_array("foo").key'), 'foo');
    assertEquals($smpl->evaluate('returns_array("foo")["key"]'), 'foo');
});

test('object property', function () {
    $smpl = new SMPLang([
        'object' => new class () {
            public string $name = 'John';
        },
        'prop_name' => 'name',
    ]);

    assertEquals($smpl->evaluate('object.name'), 'John');
    assertEquals($smpl->evaluate('object["name"]'), 'John');
    assertEquals($smpl->evaluate("object['name']"), 'John');
    assertEquals($smpl->evaluate('object[`name`]'), 'John');
    assertEquals($smpl->evaluate('object[prop_name]'), 'John');
    assertEquals($smpl->evaluate('object[ prop_name ]'), 'John');
});

test('object method', function () {
    $smpl = new SMPLang([
        'object' => new class () {
            public function method(int $number): int
            {
                return $number * 100;
            }
        },
        'method_name' => 'method',
        'number' => 12
    ]);

    assertEquals($smpl->evaluate("object.method(10)"), 1000);
    assertEquals($smpl->evaluate("object.method( 10 )"), 1000);
    assertEquals($smpl->evaluate("object['method']( 10 )"), 1000);
    assertEquals($smpl->evaluate("object[method_name](10)"), 1000);

    assertEquals($smpl->evaluate("object.method( number )"), 1200);
    assertEquals($smpl->evaluate("object[method_name](number)"), 1200);
});

test('ternary', function () {
    $smpl = new SMPLang();

    assertEquals($smpl->evaluate("true?'yes':'no'"), 'yes');
    assertEquals($smpl->evaluate("true ? 'yes' : 'no'"), 'yes');

    assertEquals($smpl->evaluate("false?'yes':'no'"), 'no');
    assertEquals($smpl->evaluate("false ? 'yes' : 'no'"), 'no');
});

test('short ternary', function () {
    $smpl = new SMPLang();

    assertEquals($smpl->evaluate("true?'yes'"), 'yes');
    assertEquals($smpl->evaluate("true ? 'yes'"), 'yes');

    assertNull($smpl->evaluate("false?'yes'"));
    assertNull($smpl->evaluate("false ? 'yes'"));


    assertEquals($smpl->evaluate("'yes'?:'no'"), 'yes');
    assertEquals($smpl->evaluate("'yes' ?: 'no'"), 'yes');

    assertEquals($smpl->evaluate("false?:'no'"), 'no');
    assertEquals($smpl->evaluate("false ?: 'no'"), 'no');
});
