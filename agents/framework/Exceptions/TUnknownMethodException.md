# TUnknownMethodException

### Directories
[./](../INDEX.md) > [Exceptions](./INDEX.md) > [TUnknownMethodException](./TUnknownMethodException.md)

**Location:** `framework/Exceptions/TUnknownMethodException.php`
**Namespace:** `Prado\Exceptions`

## Overview

Raised when calling an undefined method on a component via `TComponent::__call()` or `TComponent::__callStatic()`. Extends `TSystemException`.

## Hierarchy

```
TUnknownMethodException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

This exception is typically thrown by `TComponent::__call()` when:

- A method starting with `get`, `set`, or `on` doesn't exist
- No behavior handles the dynamic event/method

```php
// In TComponent::__call()
throw new TUnknownMethodException('unknown_method', $class, $method);
```

## See Also

- `TComponent` - Base component class
- `[TSystemException](./TSystemException.md)` - System-level exceptions
