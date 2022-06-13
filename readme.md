# SMPLang

[![release](http://poser.pugx.org/leongrdic/smplang/v)](https://packagist.org/packages/leongrdic/smplang)
[![php-version](http://poser.pugx.org/leongrdic/smplang/require/php)](https://packagist.org/packages/leongrdic/smplang)
[![license](http://poser.pugx.org/leongrdic/smplang/license)](https://packagist.org/packages/leongrdic/smplang)

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

## Expression syntax

Vars are accessed by only their name. If a var is not defined in neither constructor or `evaluate()`, an exception will be thrown.

### Supported literals
- `null`
- booleans (`true` and `false`)
- strings (`"string"` or `'string'` or <code>\`string\`</code>)
- numbers (`1`, `1.2`, `-1`, `-1.2`)
- arrays (`[23, 'string']` or `["key": 23, 'key2': 'string']`)
- objects (`{foo: "bar", baz: 23}`)


### Arrays

Array definition: `[element1, element2]`

Associative array definition: `["key": element, string_variable: element2]`

You can define associative arrays with dynamic keys by using string vars in place of keys.

Array unpacking is supported: `[element1, ...array, ...array2]`

Access array elements using either of the following syntaxes: `array.key.0` or `array['key'][0]` (which allows for dynamic array access).

### Objects

Object definition: `{foo: "bar", baz: 23}` (supports array unpacking)

Object property access: `object.property`

Object method call: `object.method(parameters)`

### Function / closure call

Call a function or closure var: `closure_var(param1, param2)`.

Named arguments: `foo(search: value, count: 1)`.

Function / closure calls support array unpacking: `bar(param1, ...array, ...array2)`



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
