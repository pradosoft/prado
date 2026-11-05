# PHPStan/TComponentPropertyReflection

### Directories
[framework](../INDEX.md) / [PHPStan](./INDEX.md) / **`TComponentPropertyReflection`**

## Class Info
**Location:** `framework/PHPStan/TComponentPropertyReflection.php`
**Namespace:** `Prado\PHPStan`
**Since:** 4.3.3

## Overview
Implements PHPStan's `PropertyReflection` interface for PRADO virtual properties — properties backed by `get{Name}()` / `set{Name}()` method pairs rather than a real PHP property declaration. Constructed and returned by [TComponentPropertiesReflectionExtension](./TComponentPropertiesReflectionExtension.md).

## Constructor

```php
public function __construct(
    private ClassReflection $declaringClass,
    private ?MethodReflection $getter,
    private ?MethodReflection $setter
)
```

Either `$getter` or `$setter` may be `null` for write-only or read-only virtual properties respectively.

## Key Behaviours

| Method | Returns |
|---|---|
| `isReadable()` | `true` when a getter `MethodReflection` was provided |
| `isWritable()` | `true` when a setter `MethodReflection` was provided |
| `getReadableType()` | Getter's declared return type, or `MixedType` if unavailable |
| `getWritableType()` | Setter's first parameter type, or `MixedType` if unavailable |
| `canChangeTypeAfterAssignment()` | Always `false` — method-hook semantics prevent safe narrowing after writes |
| `isStatic()` | Always `false` |
| `isPrivate()` | Always `false` |
| `isPublic()` | Always `true` |
| `isDeprecated()` | `TrinaryLogic::createNo()` |
| `isInternal()` | `TrinaryLogic::createNo()` |
| `getDocComment()` | Getter's doc comment if present, then setter's, otherwise `null` |

## Design Notes

- `canChangeTypeAfterAssignment()` returns `false` because getter/setter method pairs may apply custom logic that prevents PHPStan from safely narrowing the type after an assignment (unlike plain property writes).
- `MixedType` is the fallback when no getter/setter is found or when the method has no variants, ensuring PHPStan never hard-errors on an untyped virtual property.

## See Also

- [TComponentPropertiesReflectionExtension](./TComponentPropertiesReflectionExtension.md) — the extension that constructs and returns instances of this class
- [DynamicMethodReflection](./DynamicMethodReflection.md) — analogous reflection for dynamic `dy*`/`fx*` methods
