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

Variables passed this way will override variables passed in the constructor and will only be available in this specific expression in case multiple expressions are evaluated from the same object.


In case of an exception, `Le\SMPLang\Exception` will be thrown with a short description of the error.

## Syntax

Variables are accessed only by their name. If a variable is not defined, an exception will be thrown.

Array elements are accessed with one of the following syntaxes: `array.key.0` or `array['key'][0]` (which allows for dynamic array access).

Object properties and methods are accessed with the following syntax: `object.property` or `object.method(parameters)`.

Closures/functions are called with the following syntax: `closure(paramValue, ...)`. Named arguments are supported using the following syntax: `closure(param: value, ...)`.

Array unpacking (`...array`) is supported both in array definitions and closure calls.

Arrays can be defined using any of the following two syntaxes: `[element1, element2, ...]` or `{element1, element2, ...}`.

Arrays can also serve as hashes by providing keys: `{key1: value1, key2: value2, ...}` or `["key1": value1, "key2": "value2", ...]`. The latter syntax can be used to define arrays with dynamic keys by passing variables as keys.


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
