# Util/IInstanceCheck

### Directories
[framework](./INDEX.md) / [Util](./Util/INDEX.md) / **`IInstanceCheck`**

**Location:** `framework/Util/IInstanceCheck.php`
**Namespace:** `Prado\Util`

## Overview
Interface allowing objects to control their `instanceof` results when used with `[TComponent](../TComponent.md)::isa()`. Useful for behaviors that want to masquerade as specific objects.

## Key Methods

| Method | Description |
|--------|-------------|
| `isinstanceof(string $class, object $instance = null): ?bool` | Returns true/false if object is instanceof class, null to use default |

## See Also

- `[TComponent](../TComponent.md)::isa()` - Uses this interface
