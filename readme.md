# SMPLang

[![release](http://poser.pugx.org/leongrdic/smplang/v)](https://packagist.org/packages/leongrdic/smplang)
[![php-version](http://poser.pugx.org/leongrdic/smplang/require/php)](https://packagist.org/packages/leongrdic/smplang)
[![license](http://poser.pugx.org/leongrdic/smplang/license)](https://packagist.org/packages/leongrdic/smplang)
[![run-tests](https://github.com/leongrdic/php-smplang/actions/workflows/run-tests.yml/badge.svg)](https://github.com/leongrdic/php-smplang/actions/workflows/run-tests.yml)

[![try](https://img.shields.io/badge/Try%20it%20out-on%20PHPSandbox-%237E29CE)](https://play.phpsandbox.io/leongrdic/smplang?input=%24smpl%20%3D%20new%20%5CLe%5CSMPLang%5CSMPLang%28%5B%0A%20%20%27foo%27%20%3D%3E%205%0A%5D%29%3B%0A%0A%24result%20%3D%20%24smpl-%3Eevaluate%28%27%281%20%2B%20foo%20%2A%204%29%20%2F%207%27%29%3B%0A%0Aprint_r%28%24result%29%3B)


SMPLang is a simple expression language written in PHP. It can be considered similar to PHP's native `eval()` function but SMPLang has its own syntax and the expressions are evaluated in sort-of a sandbox with access to only vars and functions/closures that you pass into it.

The language is partly inspired by Symfony Expression Language but there are some major differences like array unpacking, named arguments and easier function definition, thus SMPLang may not be a replacement for some use cases.

Install:
```
composer require leongrdic/smplang
```

To use SMPLang, create a new instance of the `\Le\SMPLang\SMPLang` class and pass in an associative array of vars that you want to use in your expressions.

```php
$smpl = new \Le\SMPLang\SMPLang([
    'variableName' => 'value',
]);
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


In case of an exception, `\Le\SMPLang\Exception` will be thrown with a short description of the error.

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
$smpl = new \Le\SMPLang\SMPLang();
$result = $smpl->evaluate('(1 + 2 * 3) / 7');
// $result will be 1
```
[![try](https://img.shields.io/badge/Try%20it%20out-on%20PHPSandbox-%237E29CE)](https://play.phpsandbox.io/leongrdic/smplang?input=%24smpl%20%3D%20new%20%5CLe%5CSMPLang%5CSMPLang%28%29%3B%0A%24result%20%3D%20%24smpl-%3Eevaluate%28%27%281%20%2B%202%20%2A%203%29%20%2F%207%27%29%3B%0Aprint_r%28%24result%29%3B)


```php
$smpl = new \Le\SMPLang\SMPLang([
    'foo' => 'bar',
    'arr' => [1, 2, 3],
    'hash' => ['a' => 'b'],
]);

$result = $smpl->evaluate('foo ~ " " ~ arr[1] ~ " " ~ hash.a');
// $result will be "bar 2 b"
```
[![try](https://img.shields.io/badge/Try%20it%20out-on%20PHPSandbox-%237E29CE)](https://play.phpsandbox.io/leongrdic/smplang?input=%24smpl%20%3D%20new%20%5CLe%5CSMPLang%5CSMPLang%28%5B%0A%20%20%20%20%27foo%27%20%3D%3E%20%27bar%27%2C%0A%20%20%20%20%27arr%27%20%3D%3E%20%5B1%2C%202%2C%203%5D%2C%0A%20%20%20%20%27hash%27%20%3D%3E%20%5B%27a%27%20%3D%3E%20%27b%27%5D%2C%0A%5D%29%3B%0A%0A%24result%20%3D%20%24smpl-%3Eevaluate%28%27foo%20~%20%22%20%22%20~%20arr%5B1%5D%20~%20%22%20%22%20~%20hash.a%27%29%3B%0Aprint_r%28%24result%29%3B)


```php
$smpl = new \Le\SMPLang\SMPLang([
    'prepend' => fn(string $a): string => "hello $a",
    'reverse' => strrev(...),
]);

$result = $smpl->evaluate('prepend("simple " ~ reverse("world"))');
// $result will be "hello simple dlrow"
```
[![try](https://img.shields.io/badge/Try%20it%20out-on%20PHPSandbox-%237E29CE)](https://play.phpsandbox.io/leongrdic/smplang?input=%24smpl%20%3D%20new%20%5CLe%5CSMPLang%5CSMPLang%28%5B%0A%20%20%20%20%27prepend%27%20%3D%3E%20fn%28string%20%24a%29%3A%20string%20%3D%3E%20%22hello%20%24a%22%2C%0A%20%20%20%20%27reverse%27%20%3D%3E%20strrev%28...%29%2C%0A%5D%29%3B%0A%0A%24result%20%3D%20%24smpl-%3Eevaluate%28%27prepend%28%22simple%20%22%20~%20reverse%28%22world%22%29%29%27%29%3B%0Aprint_r%28%24result%29%3B)


```php
$smpl = new \Le\SMPLang\SMPLang([
    'foo' => 'bar',
]);

$result = $smpl->evaluate('foo !== "bar" ? "yes" : "no"');
// $result will be "no"
```
[![try](https://img.shields.io/badge/Try%20it%20out-on%20PHPSandbox-%237E29CE)](https://play.phpsandbox.io/leongrdic/smplang?input=%24smpl%20%3D%20new%20%5CLe%5CSMPLang%5CSMPLang%28%5B%0A%20%20%20%20%27foo%27%20%3D%3E%20%27bar%27%2C%0A%5D%29%3B%0A%0A%24result%20%3D%20%24smpl-%3Eevaluate%28%27foo%20%21%3D%3D%20%22bar%22%20%3F%20%22yes%22%20%3A%20%22no%22%27%29%3B%0Aprint_r%28%24result%29%3B)
