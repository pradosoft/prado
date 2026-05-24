# PHPStan/PradoMethodVisibleStaticMethodTypeSpecifyingExtension

### Directories
[framework](../INDEX.md) / [PHPStan](./INDEX.md) / **`PradoMethodVisibleStaticMethodTypeSpecifyingExtension`**

## Class Info
**Location:** `framework/PHPStan/PradoMethodVisibleStaticMethodTypeSpecifyingExtension.php`
**Namespace:** `Prado\PHPStan`
**Since:** 4.3.3

## Overview
PHPStan static-method type-specifying extension for `Prado::method_visible()`. Narrows the type of the first argument object to include `HasMethodType` for the named method when the call returns `true`, eliminating false "method not found" errors inside guarded branches.

**This class is `final`** and cannot be extended.

## Problem Solved

```php
// Without extension, PHPStan doesn't know the method exists
if (Prado::method_visible($obj, 'someMethod')) {
    $obj->someMethod();  // PHPStan: method not found
}

// With extension, PHPStan knows $obj has someMethod()
```

`Prado::method_visible()` extends `method_exists()` with PHP visibility checks. From PHPStan's perspective this narrows the subject type exactly as `method_exists()` would.

## How It Works

Implements `StaticMethodTypeSpecifyingExtension` and `TypeSpecifierAwareExtension`:

- `getClass()` returns `Prado::class` — extension applies only to `Prado` static calls.
- `isStaticMethodSupported()` activates when the method is `method_visible`, both argument slots are present, and the context is `true()` (inside a truthy branch).
- `specifyTypes()` extracts the first argument's object type and the second argument's constant string value (the method name). If exactly one constant string is found, it narrows the first argument's type to `OriginalType & HasMethodType($methodName)`.

## Requirements

- Static method must be `method_visible`
- First argument must resolve to an object type
- Second argument must be a single constant string (dynamic method names are not narrowed)
- Context must be `true` (only narrows in the truthy branch)

## Usage

Add to `phpstan.neon`:
```neon
services:
    -
        class: Prado\PHPStan\PradoMethodVisibleStaticMethodTypeSpecifyingExtension
        tags:
            - phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension
```

## See Also

- [TComponentHasMethodTypeSpecifyingExtension](./TComponentHasMethodTypeSpecifyingExtension.md) — equivalent narrowing for the instance-method `TComponent::hasMethod()`
- `Prado::method_visible()` — runtime visibility-aware method existence check
