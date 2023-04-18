# FunctionFlags

A library that aims to make it a bit easier to use PHP constants as function/method flags.

Flags can be defined and checked globally by calling `FunctionFlags`'s static methods.
```php
use FunctionFlags/FunctionFlags

// Define global flags for use anywhere for this runtime
FunctionFlags::define([
  "MY_FLAG",
  "OTHER_FLAG"
]);

// Returns true if MY_FLAG is passed
function foo(int $flags = null): bool {
  return FunctionFlags::isset(MY_FLAG);
}

foo(MY_FLAG); // true
foo(OTHER_FLAG|MY_FLAG); // true
foo(OTHER_FLAG); // false
foo(); // false
```

Flags can also be scoped to a class by creating a new `FunctionFlags` instance. Only flags defined in this instance will be matched
```php
use FunctionFlags/FunctionFlags

$flags1 = new FunctionFlags(["FOO", "BAR"]);
$flags2 = new FunctionFlags(["BAR", "BIZ"]);

// Returns true if FOO is passed and present in $flags1
function foo(int $flags = null): bool {
  return $flags2->isset(FOO);
}

foo(FOO); // true
foo(FOO|BIZ); // true
foo(BIZ); // false
foo(); // false
```

## Installation

Requires PHP 8.1 or newer

1. **Install composer package**
```
composer require victorwesterlund/functionflags
```

2. **Include FunctionFlags in your project**
```php
use FunctionFlags/FunctionFlags
```

3. **Define some flags** (Using static approach for demo)
```php
FunctionFlags::define([
  "MY_FLAG",
  "OTHER_FLAG"
]);
```

4. **Add a function which accepts flags**
```php
// 1. If your function takes more than 1 argument. The "flags" variable MUST be the last.
// 2. It's recommended to make your "flags" variable default to some value if empty to make flags optional.
function foo($bar = null, int $flags = null): bool {
  return FunctionFlags::isset(MY_FLAG);
}
```

5. **Use flags in function calls**
```php
// Your function can now accept flags. One or many using the Union operator `|`
foo("hello world", OTHER_FLAG|MY_FLAG);
```

## Methods

Static|Description
--|--
`FunctionFlags::define(string\|array)`|Flag(s) to define as string or array of string. This method must be called before using the flag(s).
`FunctionFlags::isset(constant)`|The constant you wish to check is set on your function or method call.

Instanced|Description
--|--
`new FunctionFlags(string\|array\|null)`|Flag(s) to define as string or array of string. This method must be called before using the flag(s).
`FunctionFlags->define(string\|array)`|Flag(s) to define as string or array of string. This method must be called before using the flag(s).
`FunctionFlags->isset(constant)`|The constant you wish to check is set on your function or method call.
