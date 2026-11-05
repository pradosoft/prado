# Util/TDataFieldAccessor

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TDataFieldAccessor`**

## Class Info
**Location:** `framework/Util/TDataFieldAccessor.php`
**Namespace:** `Prado\Util`
**Since:** 3.0

## Overview
`TDataFieldAccessor` is a static utility class that evaluates a data value at a specified field path. It supports arrays (including nested traversal via dot-notation), `TMap`, `TList`, and objects with getter methods, providing uniform field access across the heterogeneous data types that Prado data-bound controls typically receive.

## Methods

| Method | Description |
|--------|-------------|
| `static getDataFieldValue(mixed $data, mixed $field): mixed` | Returns the value at `$field` within `$data`. Dot notation (e.g. `'address.city'`) traverses nested arrays or objects. |

## See Also

- [`TList`](../Collections/TList.md) — supported container type
- [`TMap`](../Collections/TMap.md) — supported container type
