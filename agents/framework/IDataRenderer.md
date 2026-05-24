# IDataRenderer

### Directories
[framework](./INDEX.md) / **`IDataRenderer`**

## Interface Info
**Location:** `framework/IDataRenderer.php`
**Namespace:** `Prado`
**Since:** 3.1

## Overview
`IDataRenderer` must be implemented by any control that serves as a renderer for a data-bound parent control. It defines a uniform `Data` property so data-bound containers can assign the bound data item to the renderer without knowing its concrete type.

## Interface Methods

| Method | Description |
|--------|-------------|
| `getData(): mixed` | Returns the data bound to this object. |
| `setData(mixed $value)` | Sets the data to be bound to this object. |

## See Also

- [`IItemDataRenderer`](./Web/UI/WebControls/IItemDataRenderer.md) — extends this with `ItemIndex` and `ItemType`
- [`TDataRenderer`](./Web/UI/WebControls/TDataRenderer.md) — base class implementing this interface
