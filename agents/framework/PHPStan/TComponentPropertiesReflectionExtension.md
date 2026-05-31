# PHPStan/TComponentPropertiesReflectionExtension

### Directories
[framework](../INDEX.md) / [PHPStan](./INDEX.md) / **`TComponentPropertiesReflectionExtension`**

## Class Info
**Location:** `framework/PHPStan/TComponentPropertiesReflectionExtension.php`
**Namespace:** `Prado\PHPStan`
**Since:** 4.3.3

## Overview
PHPStan `PropertiesClassReflectionExtension` that maps PRADO's virtual property convention (`get{Name}()` / `set{Name}()` method pairs) to readable and writable properties on any [TComponent](../TComponent.md) subclass. Without this extension, every `$obj->Text` access on a `TComponent` subclass produces a false "Access to an undefined property" PHPStan error.

## Problem Solved

```php
// Without extension
$label->Text = 'hello';   // PHPStan: Access to an undefined property
$text = $label->Text;     // PHPStan: Access to an undefined property

// With extension, PHPStan resolves types through getText()/setText()
```

## How It Works

Implements `PropertiesClassReflectionExtension`:

- **`hasProperty(ClassReflection $class, string $name)`** — returns `true` when the class is a `TComponent` subclass and has any of `get{Name}`, `set{Name}`, `getjs{Name}`, or `setjs{Name}` methods. Lookup is case-insensitive, matching PHP's own method resolution.
- **`getProperty(ClassReflection $class, string $name)`** — builds a [TComponentPropertyReflection](./TComponentPropertyReflection.md):
  - Getter: prefers `get{Name}` over `getjs{Name}`.
  - Setter: prefers `set{Name}` over `setjs{Name}`.
  - Either may be `null` for read-only or write-only virtual properties.

The returned `TComponentPropertyReflection` exposes the getter's return type as the readable type and the setter's first parameter type as the writable type.

## Usage

Add to `phpstan.neon`:
```neon
services:
    -
        class: Prado\PHPStan\TComponentPropertiesReflectionExtension
        tags:
            - phpstan.broker.propertiesClassReflectionExtension
```

## Gotchas

- PHPStan passes the property name exactly as written in source (`$obj->Text` → `hasProperty('Text')`). The extension then looks for `getText` case-insensitively.
- Only applies to `TComponent` subclasses — plain PHP classes are unaffected.
- Read-only properties (getter only) have `isWritable() = false`; write-only properties (setter only) have `isReadable() = false`.

## See Also

- [TComponentPropertyReflection](./TComponentPropertyReflection.md) — the `PropertyReflection` implementation returned by this extension
- [TComponent](../TComponent.md) — base class whose `__get`/`__set` implement the virtual property system at runtime
