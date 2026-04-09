# TNull

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TNull](./TNull.md)

**Location:** `framework/Collections/TNull.php`
**Namespace:** `Prado\Collections`

## Overview

TNull implements the Null Object pattern, providing a reusable singleton that represents "nothing". Acts as a type-safe, well-behaved substitute for PHP's native `null`.

## Key Features

- Singleton pattern - only one instance per process
- Implements `ISingleton` (see `framework/ISingleton.php`), `JsonSerializable`, `Stringable`
- JSON encodes as `null`
- `__toString()` returns empty string

## Factory Methods

```php
$n = TNull::null();      // Preferred factory method
$n = TNull::singleton(); // ISingleton-compliant alternative
```

## Static Predicates

```php
TNull::is_null($v);  // true when $v is PHP null or TNull
TNull::empty($v);    // true when $v is TNull, null, false, 0, '', or []
```

## Wrap/Unwrap

```php
$obj = TNull::wrap(null);            // PHP null → TNull singleton
$raw = TNull::unwrap(TNull::null()); // TNull → PHP null
```

## Callable

```php
$fn = TNull::null();
$result = $fn();  // null - can be used as no-op callable
```

## JSON Serialization

```php
echo json_encode(['value' => TNull::null()]);  // {"value":null}
```

## Why Use TNull?

[`TList`](TList.md), [`TMap`](TMap.md), and other Prado collection classes **can** accept and handle `null` values natively. `TNull` provides an additional layer — a reusable singleton "null object" that can be used when a typed, non-PHP-null placeholder is needed (e.g., to avoid checking for `null` separately from `TNull` instances).
