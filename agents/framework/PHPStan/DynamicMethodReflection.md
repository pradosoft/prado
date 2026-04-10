# PHPStan/DynamicMethodReflection

### Directories
[framework](./INDEX.md) / [PHPStan](./PHPStan/INDEX.md) / **`DynamicMethodReflection`**

**Location:** `framework/PHPStan/DynamicMethodReflection.php`
**Namespace:** `Prado\PHPStan`

## Overview
Implements `MethodReflection` for PRADO's dynamic methods (`dy*`) and global events (`fx*`). Used by [DynamicMethodsClassReflectionExtension](./DynamicMethodsClassReflectionExtension.md).

## Reflection Qualities

| Property | Value |
|----------|-------|
| `isStatic()` | `false` |
| `isPrivate()` | `false` |
| `isPublic()` | `true` |
| `getDocComment()` | `null` |
| `isDeprecated()` | `TrinaryLogic::createNo()` |
| `isFinal()` | `TrinaryLogic::createNo()` |
| `isInternal()` | `TrinaryLogic::createNo()` |
| `getThrowType()` | `null` |
| `hasSideEffects()` | `TrinaryLogic::createMaybe()` |

## Variants

Returns a single variant accepting any parameters and returning `mixed`:
```php
new FunctionVariant(
    TemplateTypeMap::createEmpty(),
    TemplateTypeMap::createEmpty(),
    [],  // parameters
    true,  // variadic
    new MixedType()
)
```

## See Also

- [DynamicMethodsClassReflectionExtension](./DynamicMethodsClassReflectionExtension.md) - Extension that uses this
- [TComponent](../TComponent.md) - Where dynamic methods are defined
