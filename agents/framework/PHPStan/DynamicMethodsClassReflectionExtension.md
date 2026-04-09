# PHPStan / DynamicMethodsClassReflectionExtension

[./](../INDEX.md) > [PHPStan](./INDEX.md) > [DynamicMethodsClassReflectionExtension](./DynamicMethodsClassReflectionExtension.md)

**Location:** `framework/PHPStan/DynamicMethodsClassReflectionExtension.php`
**Namespace:** `Prado\PHPStan`

## Overview

PHPStan extension that recognizes `dy*` and `fx*` method prefixes as valid dynamic methods on [TComponent](../TComponent.md).

## Problem Solved

PHPStan normally complains about unknown methods like `$component->dyValidate(...)` or `$component->fxOnSave(...)` because they don't exist in the class definition. This extension tells PHPStan these dynamic event prefixes are always valid.

## Usage

Add to `phpstan.neon`:
```neon
services:
    -
        class: Prado\PHPStan\DynamicMethodsClassReflectionExtension
        tags:
            - phpstan.broker.methodsClassReflectionExtension
```

## How It Works

The extension implements `MethodsClassReflectionExtension`:
- `hasMethod()` returns `true` for any method starting with `dy` or `fx`
- `getMethod()` returns a [DynamicMethodReflection](./DynamicMethodReflection.md) with standard dynamic method attributes

## See Also

- [DynamicMethodReflection](./DynamicMethodReflection.md) - The reflection returned for dynamic methods
- [TComponent](../TComponent.md) - Class where these dynamic events are defined
