# PHPStan/TComponentCanSetPropertyTypeSpecifyingExtension

### Directories
[framework](../INDEX.md) / [PHPStan](./INDEX.md) / **`TComponentCanSetPropertyTypeSpecifyingExtension`**

## Class Info
**Location:** `framework/PHPStan/TComponentCanSetPropertyTypeSpecifyingExtension.php`
**Namespace:** `Prado\PHPStan`
**Since:** 4.3.3

## Overview
PHPStan instance-method type-specifying extension for `TComponent::canSetProperty()`. When `$obj->canSetProperty('Foo')` is true inside a guarded branch, narrows the type of `$obj` to include both `HasMethodType('setFoo')` and `HasPropertyType('foo')`, preventing false "method not found" and "undefined property" errors for the setter call and virtual property write forms.

**This class is `final`** and cannot be extended.

## Problem Solved

```php
// Without extension, PHPStan reports errors in the guarded block
if ($obj->canSetProperty('Text')) {
    $obj->setText($value);  // PHPStan: method not found
    $obj->text = $value;    // PHPStan: undefined property
}

// With extension, both write forms are accepted
```

## How It Works

Implements `MethodTypeSpecifyingExtension` and `TypeSpecifierAwareExtension`:

- `getClass()` returns `TComponent::class` — extension applies only to `TComponent` and its subclasses.
- `isMethodSupported()` activates when the method is `canSetProperty`, the first argument slot is present, and the context is `true()`.
- `specifyTypes()` extracts the constant string property name from the first argument. It then narrows the caller's type to `OriginalType & HasMethodType('set' . $name) & HasPropertyType(lcfirst($name))`.

The dual narrowing covers both PRADO write forms:
1. **`$obj->setFoo($v)`** — covered by `HasMethodType('setFoo')`.
2. **`$obj->foo = $v`** — covered by `HasPropertyType('foo')` (lowercase-first, matching PRADO convention).

## Requirements

- Method must be `canSetProperty`
- Caller must resolve to an object type
- First argument must be a single constant string (dynamic names are not narrowed)
- Context must be `true`

## Usage

Add to `phpstan.neon`:
```neon
services:
    -
        class: Prado\PHPStan\TComponentCanSetPropertyTypeSpecifyingExtension
        tags:
            - phpstan.typeSpecifier.methodTypeSpecifyingExtension
```

## See Also

- [TComponentCanGetPropertyTypeSpecifyingExtension](./TComponentCanGetPropertyTypeSpecifyingExtension.md) — equivalent narrowing for `canGetProperty()`
- [TComponentHasMethodTypeSpecifyingExtension](./TComponentHasMethodTypeSpecifyingExtension.md) — narrowing for `hasMethod()`
- [TComponentPropertiesReflectionExtension](./TComponentPropertiesReflectionExtension.md) — teaches PHPStan about virtual properties via reflection
- [TComponent](../TComponent.md)::canSetProperty()
