# PHPStan/TComponentIsaTypeSpecifyingExtension

### Directories
[framework](../INDEX.md) / [PHPStan](./INDEX.md) / **`TComponentIsaTypeSpecifyingExtension`**

## Class Info
**Location:** `framework/PHPStan/TComponentIsaTypeSpecifyingExtension.php`
**Namespace:** `Prado\PHPStan`

## Overview
PHPStan extension that makes `$component->isa(MyClass::class)` behave like `$component instanceof MyClass` for type specification.

**This class is `final`** and cannot be extended.

## Problem Solved

```php
// Without extension, PHPStan doesn't know the type narrowed by isa()
if ($component->isa(MyClass::class)) {
    $component->someMethod();  // PHPStan: method not found
}

// With extension, PHPStan knows $component is MyClass
```

## Usage

Add to `phpstan.neon`:
```neon
services:
    -
        class: Prado\PHPStan\TComponentIsaTypeSpecifyingExtension
        tags:
            - phpstan.typeSpecifier.methodTypeSpecifyingExtension
```

## Requirements

- Method must be `isa`
- First argument must be a class name constant
- Class must be a subclass of [TComponent](../TComponent.md)

## See Also

- [TComponent](../TComponent.md)::isa() - PRADO method for duck-typing with behaviors
