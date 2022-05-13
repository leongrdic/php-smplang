# SMPLang

SMPLang is a simple expression language for PHP. It's currently in a pre-release state and any contributions and tests are welcome.

The language is partly inspired by Symfony Expression Language but there are some major differences. For more details and a performance benchmark, see [test.php](test.php).

Install:
```
composer require leongrdic/smplang:dev-master
```

To use SMPLang, create a new instance of the SMPLang class and pass in an array of variables. These variables will be available to use in your expressions.

```php
$smpl = new Le\SMPLang\SMPLang([
    'variableName' => 'value',
];
```

You can then call the `evaluate()` method on your SMPLang instance, passing in an expression string. This will return the result of the expression.
```php
$result = $smpl->evaluate($expression);
```

Optionally, you can pass in a `$vars` array to provide additional variables (alongside the variables passed when initializing the object) to use in your expression:
```php
$result = $smpl->evaluate($expression, [
    'localVariable' => 123,
]);
```
Variables passed in this way will override variables passed in the constructor and will only be available in this specific expression in case multiple expressions are evaluated from the same object.


In case of an exception, `Le\SMPLang\Exception` will be thrown with short description of the error.

## Syntax

Variables are accessed only by their name. If a variable is not defined, an exception will be thrown.

Array elements are accessed with the following syntax: `array.key.0` (even numeric keys).

Object properties and methods are accessed with the following syntax: `object.property` or `object.method(parameters)`.

Closures/functions are called with the following syntax: `closure(parameter1, parameter2, ...)`

Arrays are defined with the following syntax: `[element1, element2, ...]`

Array unpacking (`...array`) is supported in array definitions and closure calls.

### Supported literals
- `null`
- booleans (`true` and `false`)
- strings (`"string"` or `'string'`)
- numbers (`1`, `1.2`, `-1`, `-1.2`)
- arrays (`[23, 'string']`)
- hashes aren't supported currently

### Arithmetic operators
- `+`: addition
- `-`: subtraction
- `*`: multiplication
- `/`: division
- `%`: modulo
- `**`: exponentiation

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

$result = $smpl->evaluate('foo ~ " " ~ arr.1 ~ " " ~ hash.a');
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
