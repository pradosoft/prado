# Util/IDynamicMethods

### Directories
[framework](./INDEX.md) / [Util](./Util/INDEX.md) / **`IDynamicMethods`**

**Location:** `framework/Util/IDynamicMethods.php`
**Namespace:** `Prado\Util`

## Overview
Interface for objects that receive undefined global or dynamic events via `__dycall`.

## Key Methods

| Method | Description |
|--------|-------------|
| `__dycall($method, $args)` | Handles undefined method calls for dynamic events |

## See Also

- `TPermissionsBehavior` - Implements this for permission enforcement
