# Util/TRpcClientTypesEnumerable

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TRpcClientTypesEnumerable`**

## Class Info
**Location:** `framework/Util/TRpcClientTypesEnumerable.php`
**Namespace:** `Prado\Util`
**Extends:** [`TEnumerable`](../TEnumerable.md)
**Since:** 3.2

## Overview
`TRpcClientTypesEnumerable` is the enumeration registry mapping short type tokens to their concrete client class names. `TRpcClient::create()` resolves the `$type` argument against these constants.

## Constants

| Constant | Value |
|----------|-------|
| `JSON` | `'TJsonRpcClient'` |
| `XML` | `'TXmlRpcClient'` |

## See Also

- [`TRpcClient`](./TRpcClient.md) — uses these constants in `create()`
- [`TJsonRpcClient`](./TJsonRpcClient.md)
- [`TXmlRpcClient`](./TXmlRpcClient.md)
