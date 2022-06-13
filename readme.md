# SMPLang

[![Latest Stable Version](http://poser.pugx.org/leongrdic/smplang/v)](https://packagist.org/packages/leongrdic/smplang)
[![php-version](http://poser.pugx.org/leongrdic/smplang/require/php)](https://packagist.org/packages/leongrdic/smplang)
[![License](http://poser.pugx.org/leongrdic/smplang/license)](https://packagist.org/packages/leongrdic/smplang)

[![run-tests](https://github.com/leongrdic/php-smplang/actions/workflows/run-tests.yml/badge.svg)](https://github.com/leongrdic/php-smplang/actions/workflows/run-tests.yml)

SMPLang is a simple expression language written in PHP. It can be considered similar to PHP's native `eval()` function but SMPLang has its own syntax and the expressions are evaluated in sort-of a sandbox with access to only vars and functions/closures that you pass into it.

The language is partly inspired by Symfony Expression Language but there are some major differences like array unpacking, named arguments and easier function definition, thus SMPLang may not be a replacement for some use cases.

Install:
```
composer require leongrdic/smplang
```

To use SMPLang, create a new instance of the `Le\SMPLang\SMPLang` class and pass in an associative array of vars that you want to use in your expressions.

```php
$smpl = new Le\SMPLang\SMPLang([
    'variableName' => 'value',
];
```

You can then call the `evaluate()` method on the instance, passing in an expression string. This will return the result of the expression.
```php
$result = $smpl->evaluate($expression);
```

Optionally, you can pass in an associative array to the second parameter to provide additional local vars to use in the expression:
```php
$result = $smpl->evaluate($expression, [
    'localVariable' => 123,
]);
```

Vars passed this way will override vars passed in the constructor and will only be available in this specific expression, in case multiple expressions are evaluated from the same object.


In case of an exception, `Le\SMPLang\Exception` will be thrown with a short description of the error.

## Syntax

Vars are accessed by only their name. If a var is not provided in neither constructor or `evaluate()`, an exception will be thrown.

Array elements are accessed with one of the following syntaxes: `array.key.0` or `array['key'][0]` (which allows for dynamic array access).

Object properties and methods are accessed with the following syntax: `object.property` or `object.method(parameters)`.

Closures/functions are called with the following syntax: `closure(paramValue, ...)`. Named arguments are supported using the following syntax: `closure(param: value, ...)`.

Array unpacking (`...array`) is supported both in array definitions and closure calls.

Arrays can be defined using any of the following two syntaxes: `[element1, element2, ...]` or `{element1, element2, ...}`.

Arrays can also serve as hashes by providing keys: `{key1: value1, key2: value2, ...}` or `["key1": value1, "key2": "value2", ...]`. The latter syntax can be used to define arrays with dynamic keys by passing vars as keys.


### Supported literals
- `null`
- booleans (`true` and `false`)
- strings (`"string"` or `'string'` or <code>\`string\`</code>)
- numbers (`1`, `1.2`, `-1`, `-1.2`)
- arrays (`[23, 'string']` or `{foo: "bar", baz: 23}`)

### Arithmetic operators
- `+`: addition
- `-`: subtraction
- `*`: multiplication
- `/`: division
- `%`: modulo (`a*b%c*d == (a*b)%(c*d)`)
- `**`: exponentiation (`a*b**c*d == (a*b)**(c*d)`)

### Comparison operators
- `===`: strict equality
- `!==`: strict inequality
- `==`: equality
- `!=`: inequality
- `>`: greater than
- `<`: less than
- `>=`: greater than or equal to
- `<=`: less than or equal to

### Logical operators
- `&&`: logical and
- `||`: logical or
- `!`: logical not

### String concatenation
- `~`: string concatenation

### Ternary expressions
- `a ? b : c` 
- `a ?: b` (is equivalent to `a ? a : b`)
- `a ? b` (is equivalent to `a ? b : null`)


## Examples
```php
$smpl = new Le\SMPLang\SMPLang();
$result = $smpl->evaluate('(1 + 2 * 3) / 7');
// $result will be 1
```

```php
$smpl = new Le\SMPLang\SMPLang([
    'foo' => 'bar',
    'arr' => [1, 2, 3],
    'hash' => ['a' => 'b'],
]);

$result = $smpl->evaluate('foo ~ " " ~ arr[1] ~ " " ~ hash.a');
// $result will be "bar 2 b"
```

```php
$smpl = new Le\SMPLang\SMPLang([
    'prepend' => fn(string $a): string => "hello $a",
    'reverse' => strrev(...),
]);

$result = $smpl->evaluate('prepend("simple " ~ reverse("world"))');
// $result will be "hello simple dlrow"
```

```php
$smpl = new Le\SMPLang\SMPLang([
    'foo' => 'bar',
]);

$result = $smpl->evaluate('foo !== "bar" ? "yes" : "no"');
// $result will be "no"
```
