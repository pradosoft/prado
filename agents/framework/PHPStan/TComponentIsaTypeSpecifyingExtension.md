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

## How It Works

Implements `MethodTypeSpecifyingExtension` and `TypeSpecifierAwareExtension`. Injects `ReflectionProvider` via constructor to validate that each class name constant actually exists before building a type. When multiple constant strings are provided, the narrowed type is a `UnionType` over all valid class names; when only one is provided, it is a plain `ObjectType`. Unknown class names are skipped. This correctly handles interfaces and abstract classes in addition to concrete classes.

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
- Caller must resolve to an object type
- First argument must resolve to one or more constant class-name strings known to the `ReflectionProvider`

## See Also

- [TComponent](../TComponent.md)::isa() — PRADO method for duck-typing with behaviors and interfaces
- [TComponentHasMethodTypeSpecifyingExtension](./TComponentHasMethodTypeSpecifyingExtension.md) — sibling narrowing extension for `hasMethod()`
