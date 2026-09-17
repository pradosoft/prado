# PHPStan/TComponentHasMethodTypeSpecifyingExtension

### Directories
[framework](../INDEX.md) / [PHPStan](./INDEX.md) / **`TComponentHasMethodTypeSpecifyingExtension`**

## Class Info
**Location:** `framework/PHPStan/TComponentHasMethodTypeSpecifyingExtension.php`
**Namespace:** `Prado\PHPStan`
**Since:** 4.3.3

## Overview
PHPStan instance-method type-specifying extension for `TComponent::hasMethod()`. When `$obj->hasMethod('foo')` is true inside a guarded branch, narrows the type of `$obj` to include `HasMethodType('foo')`, making the call `$obj->foo()` valid. If the method name follows PRADO's virtual-property convention (`get{Name}`, `set{Name}`, `getjs{Name}`, `setjs{Name}`), the corresponding virtual property is also narrowed via `HasPropertyType`.

**This class is `final`** and cannot be extended.

## Problem Solved

```php
// Without extension, PHPStan reports errors in the guarded block
if ($obj->hasMethod('someMethod')) {
    $obj->someMethod();  // PHPStan: method not found
}

// With extension, PHPStan knows $obj has someMethod()

// Property-convention methods also narrow the virtual property:
if ($obj->hasMethod('getText')) {
    $value = $obj->getText();  // accepted
    $value = $obj->text;       // also accepted (HasPropertyType narrowed)
}
```

## How It Works

Implements `MethodTypeSpecifyingExtension` and `TypeSpecifierAwareExtension`:

- `getClass()` returns `TComponent::class` — extension applies only to `TComponent` and its subclasses.
- `isMethodSupported()` activates when the method is `hasMethod`, the first argument slot is present, and the context is `true()`.
- `specifyTypes()` extracts the constant string method name. It always adds `HasMethodType($methodName)`. When the method name starts with `get`, `set`, `getjs` or `setjs` (case-insensitive), `propertyNameFromAccessor()` derives the property name from the suffix and the extension adds `HasPropertyType` for **both** that spelling and its lowercase-first form.

PHPStan asks for a property with the spelling written in the source. PRADO writes `Title` in templates and in code, and `title` reaches the same accessor because PHP method names are case-insensitive, so registering only one spelling left the other reported as an undefined property.

## Requirements

- Method must be `hasMethod`
- Caller must resolve to an object type
- First argument must be a single constant string (dynamic names are not narrowed)
- Context must be `true`
- Subject type must have **exactly one** object class name. PHPStan hands a method call to a `MethodTypeSpecifyingExtension` only in that case (`MethodCallHandler::specifyTypes()`), so an intersection (`IService&TComponent`) or a union (`TControl|TStyle`) subject never reaches this extension and `isMethodSupported()` is never called. Use [Prado::method_visible()](./PradoMethodVisibleStaticMethodTypeSpecifyingExtension.md) on those subjects; being a static method extension, it narrows an argument and is unaffected.

## Usage

Add to `phpstan.neon`:
```neon
services:
    -
        class: Prado\PHPStan\TComponentHasMethodTypeSpecifyingExtension
        tags:
            - phpstan.typeSpecifier.methodTypeSpecifyingExtension
```

## See Also

- [TComponentCanGetPropertyTypeSpecifyingExtension](./TComponentCanGetPropertyTypeSpecifyingExtension.md) — narrowing for `canGetProperty()`
- [TComponentCanSetPropertyTypeSpecifyingExtension](./TComponentCanSetPropertyTypeSpecifyingExtension.md) — narrowing for `canSetProperty()`
- [PradoMethodVisibleStaticMethodTypeSpecifyingExtension](./PradoMethodVisibleStaticMethodTypeSpecifyingExtension.md) — equivalent narrowing for the static `Prado::method_visible()`
- [TComponent](../TComponent.md)::hasMethod()
