# PHPStan/TComponentCanGetPropertyTypeSpecifyingExtension

### Directories
[framework](../INDEX.md) / [PHPStan](./INDEX.md) / **`TComponentCanGetPropertyTypeSpecifyingExtension`**

## Class Info
**Location:** `framework/PHPStan/TComponentCanGetPropertyTypeSpecifyingExtension.php`
**Namespace:** `Prado\PHPStan`
**Since:** 4.3.3

## Overview
PHPStan instance-method type-specifying extension for `TComponent::canGetProperty()`. When `$obj->canGetProperty('Foo')` is true inside a guarded branch, narrows the type of `$obj` to include both `HasMethodType('getFoo')` and `HasPropertyType('foo')`, preventing false "method not found" and "undefined property" errors for the getter call and virtual property access forms.

**This class is `final`** and cannot be extended.

## Problem Solved

```php
// Without extension, PHPStan reports errors in the guarded block
if ($obj->canGetProperty('Text')) {
    $value = $obj->getText();  // PHPStan: method not found
    $value = $obj->text;       // PHPStan: undefined property
}

// With extension, both access forms are accepted
```

## How It Works

Implements `MethodTypeSpecifyingExtension` and `TypeSpecifierAwareExtension`:

- `getClass()` returns `TComponent::class` — extension applies only to `TComponent` and its subclasses.
- `isMethodSupported()` activates when the method is `canGetProperty`, the first argument slot is present, and the context is `true()`.
- `specifyTypes()` extracts the constant string property name from the first argument. It then narrows the caller's type to `OriginalType & HasMethodType('get' . $name) & HasPropertyType(lcfirst($name))`.

The dual narrowing covers both PRADO access forms:
1. **`$obj->getFoo()`** — covered by `HasMethodType('getFoo')`.
2. **`$obj->foo`** — covered by `HasPropertyType('foo')` (lowercase-first, matching PRADO convention).

## Requirements

- Method must be `canGetProperty`
- Caller must resolve to an object type
- First argument must be a single constant string (dynamic names are not narrowed)
- Context must be `true`

## Usage

Add to `phpstan.neon`:
```neon
services:
    -
        class: Prado\PHPStan\TComponentCanGetPropertyTypeSpecifyingExtension
        tags:
            - phpstan.typeSpecifier.methodTypeSpecifyingExtension
```

## See Also

- [TComponentCanSetPropertyTypeSpecifyingExtension](./TComponentCanSetPropertyTypeSpecifyingExtension.md) — equivalent narrowing for `canSetProperty()`
- [TComponentHasMethodTypeSpecifyingExtension](./TComponentHasMethodTypeSpecifyingExtension.md) — narrowing for `hasMethod()`
- [TComponentPropertiesReflectionExtension](./TComponentPropertiesReflectionExtension.md) — teaches PHPStan about virtual properties via reflection
- [TComponent](../TComponent.md)::canGetProperty()
